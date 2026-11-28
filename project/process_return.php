<?php
require_once 'includes/db.php';

$customer_id = require_customer();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("rentals.php");
}
verify_csrf();

$rental_id = input('rental_id');
$track_return = strtoupper(input('track_return'));

if (!preg_match('/^[A-Z0-9&\-]{8,50}$/', $track_return)) {
    flash("เลขพัสดุไม่ถูกต้อง (ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว)", "error");
    redirect("rentals.php");
}

// ส่งคืนได้เฉพาะรายการเช่าของตัวเองที่ร้านจัดส่งแล้ว (Shipped) และยังไม่ได้แจ้งคืน
$stmt = $conn->prepare("SELECT 1 FROM rentals r JOIN orders o ON o.order_id = r.order_id
    WHERE r.rental_id = ? AND o.customers_id = ? AND r.rental_status = 'Shipped'");
$stmt->bind_param("ss", $rental_id, $customer_id);
$stmt->execute();
$allowed = $stmt->get_result()->num_rows === 1;
$stmt->close();

if (!$allowed) {
    flash("ไม่สามารถแจ้งคืนรายการนี้ได้", "error");
    redirect("rentals.php");
}

$store = $conn->query("SELECT postal_code FROM stores LIMIT 1")->fetch_assoc();
$postal_code = $store['postal_code'] ?? '73000';

// บันทึกเลขพัสดุส่งคืนและเปลี่ยนสถานะเป็นรอตรวจสภาพ วันที่ได้รับของ (returnss) ร้านบันทึกตอนตรวจสภาพ
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO track_return (track_return, rental_id, postal_code) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $track_return, $rental_id, $postal_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Returning' WHERE rental_id = ? AND rental_status = 'Shipped'");
    $stmt->bind_param("s", $rental_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    flash("บันทึกการส่งคืนแล้ว ร้านจะตรวจสภาพสินค้าเมื่อได้รับของค่ะ");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // 1062 = ข้อมูลซ้ำ เลขพัสดุเป็น primary key ของตาราง track_return
    flash($e->getCode() === 1062 ? "เลขพัสดุนี้ถูกใช้แจ้งคืนไปแล้ว" : "บันทึกการคืนสินค้าไม่สำเร็จ กรุณาลองใหม่", "error");
    if ($e->getCode() !== 1062) {
        error_log('process_return failed: ' . $e->getMessage());
    }
}
redirect("rentals.php");
