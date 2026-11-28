<?php
// ลูกค้ายกเลิกคำสั่งของตัวเองได้ระหว่างที่ร้านยังไม่ตรวจสลิป (ทุกใบในคำสั่งยังเป็น Pending)
// หลังร้านรับเรื่องแล้วต้องติดต่อร้าน เพราะร้านอาจเริ่มเตรียมของแล้ว
require_once 'includes/db.php';

$customer_id = require_customer();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("rentals.php");
}
verify_csrf();

$order_id = input('order_id');

$conn->begin_transaction();
try {
    // ล็อกแถวการเช่าของคำสั่งนี้ไว้ (FOR UPDATE) ถ้าแอดมินกดอนุมัติพร้อมกัน ฝั่งใดฝั่งหนึ่งต้องรออีกฝั่งเสร็จก่อน
    $stmt = $conn->prepare("SELECT r.rental_status FROM rentals r JOIN orders o ON o.order_id = r.order_id
        WHERE r.order_id = ? AND o.customers_id = ? FOR UPDATE");
    $stmt->bind_param("ss", $order_id, $customer_id);
    $stmt->execute();
    $statuses = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'rental_status');
    $stmt->close();

    if (!$statuses || array_diff($statuses, ['Pending'])) {
        $conn->rollback();
        flash(match (true) {
            !$statuses => "ไม่พบคำสั่งนี้",
            !array_diff($statuses, ['Cancelled']) => "คำสั่งนี้ถูกยกเลิกไปแล้ว",
            default => "ยกเลิกคำสั่งนี้ไม่ได้ ร้านรับเรื่องแล้ว กรุณาติดต่อร้าน",
        }, "error");
        redirect("rentals.php");
    }

    $stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Cancelled' WHERE order_id = ? AND rental_status = 'Pending'");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE orders SET cancelled_by = 'customer' WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    flash("ยกเลิกคำสั่ง $order_id แล้ว ถ้าโอนเงินไปแล้ว ร้านจะติดต่อคืนเงินให้ค่ะ");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('cancel_order failed: ' . $e->getMessage());
    flash("ยกเลิกไม่สำเร็จ กรุณาลองใหม่", "error");
}
redirect("rentals.php");
