<?php
require_once 'includes/db.php';

$customer_id = require_customer();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("checkout.php");
}
verify_csrf();

$customer = find_customer($conn, $customer_id);
if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}

// ราคาและค่ามัดจำคำนวณใหม่จากฐานข้อมูล ไม่เชื่อค่าที่เก็บในตะกร้า
$lines = cart_lines($conn, $customer);
if (count($lines) === 0) {
    redirect("cart.php");
}

$employee_id = random_employee_id($conn);
$track_rentals = generate_tracking_number();

// บันทึกกระเป๋าทุกใบในตะกร้าเป็นรายการเช่าแยกกัน (ใบละหนึ่ง rental_id) ภายใน transaction เดียว
// ถ้าบันทึกใบใดไม่สำเร็จ จะยกเลิกทั้งหมดและตะกร้ายังอยู่ครบ
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO rentals (rental_id, customers_id, product_id, rental_date, rental_days, deposit_fee, employees_id, track_rentals, rental_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    foreach ($lines as $line) {
        $rental_id = next_id($conn, 'rentals', 'rental_id', 'Ren', 4);
        $stmt->bind_param("ssssiiss", $rental_id, $customer_id, $line['product_id'], $line['rental_date'], $line['days'], $line['deposit'], $employee_id, $track_rentals);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('confirm_order failed: ' . $e->getMessage());
    flash("บันทึกคำสั่งเช่าไม่สำเร็จ กรุณาลองใหม่อีกครั้ง", "error");
    redirect("cart.php");
}

unset($_SESSION['cart']);
flash("ส่งคำสั่งเช่า " . count($lines) . " รายการแล้ว ร้านจะตรวจสอบการชำระเงินและยืนยันให้ค่ะ");
redirect("profile.php");
