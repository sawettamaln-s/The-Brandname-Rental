<?php
require_once 'db_connect.php';

$customer_id = require_customer();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("profile.php");
}
verify_csrf();

$rental_id = input('rental_id');
$track_return = strtoupper(input('track_return'));

if (!preg_match('/^[A-Z0-9&\-]{8,50}$/', $track_return)) {
    flash("เลขพัสดุไม่ถูกต้อง (ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว)");
    redirect("profile.php");
}

// ส่งคืนได้เฉพาะรายการเช่าของตัวเอง ที่ร้านยืนยันแล้ว และยังไม่เคยแจ้งคืน
$stmt = $conn->prepare("SELECT 1 FROM rentals r
    WHERE r.rental_id = ? AND r.customers_id = ? AND r.rental_status = 'Complete'
      AND NOT EXISTS (SELECT 1 FROM track_return t WHERE t.rental_id = r.rental_id)");
$stmt->bind_param("ss", $rental_id, $customer_id);
$stmt->execute();
$allowed = $stmt->get_result()->num_rows === 1;
$stmt->close();

if (!$allowed) {
    flash("ไม่สามารถแจ้งคืนรายการนี้ได้");
    redirect("profile.php");
}

$store = $conn->query("SELECT postal_code FROM stores LIMIT 1")->fetch_assoc();
$postal_code = $store['postal_code'] ?? '73000';
$return_date = date("Y-m-d");

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO track_return (track_return, rental_id, postal_code, returnl_status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("sss", $track_return, $rental_id, $postal_code);
    $stmt->execute();
    $stmt->close();

    $return_id = next_id($conn, 'returnss', 'return_id', 'Ret', 4);
    $stmt = $conn->prepare("INSERT INTO returnss (return_id, rental_id, return_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $return_id, $rental_id, $return_date);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    flash("บันทึกการส่งคืนแล้ว ร้านจะตรวจสภาพสินค้าเมื่อได้รับของค่ะ");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // 1062 = ข้อมูลซ้ำ เลขพัสดุเป็น primary key ของตาราง track_return
    flash($e->getCode() === 1062 ? "เลขพัสดุนี้ถูกใช้แจ้งคืนไปแล้ว" : "บันทึกการคืนสินค้าไม่สำเร็จ กรุณาลองใหม่");
    if ($e->getCode() !== 1062) {
        error_log('process_return failed: ' . $e->getMessage());
    }
}
redirect("profile.php");
