<?php
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("checkout.php");
}
verify_csrf();

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

// ราคาและค่ามัดจำคำนวณใหม่จากฐานข้อมูล ไม่เชื่อค่าที่เก็บในตะกร้า
$lines = cart_lines($conn, $customer);
if (count($lines) === 0) {
    redirect("cart.php");
}
foreach ($lines as $line) {
    if (cart_problem($line) !== null) {
        flash("มีรายการในตะกร้าที่เช่าไม่ได้ กรุณาแก้ไขก่อนชำระเงินนะคะ", "error");
        redirect("cart.php");
    }
}

$saved = save_image($_FILES['payment_slip'] ?? null, SLIP_DIR);
if (isset($saved['error'])) {
    flash($saved['error'], "error");
    redirect("checkout.php");
}
$slip_file = $saved['file'];

// หนึ่งคำสั่งเช่า (orders) ต่อการโอนหนึ่งครั้ง กระเป๋าแต่ละใบเป็นหนึ่งแถวใน rentals ภายใน transaction เดียว
// ล็อกแถวสินค้าทุกใบก่อนตรวจการจองซ้อนอีกครั้ง สองคำสั่งที่จองใบเดียวกันพร้อมกันจึงต้องรอกัน คำสั่งที่มาทีหลังจะเห็นการจองของคำสั่งแรก
$conn->begin_transaction();
try {
    $product_ids = array_column($lines, 'product_id');
    sort($product_ids);
    $lock = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? FOR UPDATE");
    foreach ($product_ids as $product_id) {
        $lock->bind_param("s", $product_id);
        $lock->execute();
        $lock->get_result()->free();
    }
    $lock->close();

    foreach ($lines as $line) {
        if (booking_conflicts($conn, $line['product_id'], $line['rental_date'], $line['days'])) {
            $conn->rollback();
            @unlink(SLIP_DIR . '/' . $slip_file);
            flash("{$line['brand']} {$line['details']} ถูกจองช่วงนี้ไปก่อนแล้ว กรุณาเลือกวันใหม่นะคะ", "error");
            redirect("cart.php");
        }
    }

    $order_id = next_id($conn, 'orders', 'order_id', 'ORD', 4);
    $stmt = $conn->prepare("INSERT INTO orders (order_id, customers_id, slip_file) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $order_id, $customer_id, $slip_file);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO rentals (rental_id, order_id, product_id, rental_date, rental_days, daily_price, deposit_fee, rental_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    foreach ($lines as $line) {
        $rental_id = next_id($conn, 'rentals', 'rental_id', 'Ren', 4);
        $price = (int) $line['daily_rental_price'];
        $stmt->bind_param("ssssiii", $rental_id, $order_id, $line['product_id'], $line['rental_date'], $line['days'], $price, $line['deposit']);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    @unlink(SLIP_DIR . '/' . $slip_file);
    error_log('confirm_order failed: ' . $e->getMessage());
    flash("บันทึกคำสั่งเช่าไม่สำเร็จ กรุณาลองใหม่อีกครั้งค่ะ", "error");
    redirect("cart.php");
}

unset($_SESSION['cart']);
flash("ได้รับคำสั่งเช่า $order_id แล้ว ร้านจะตรวจสลิปและแจ้งสถานะในหน้านี้ค่ะ");
redirect("rentals.php");
