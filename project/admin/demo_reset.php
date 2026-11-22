<?php
// ปุ่มรีเซ็ตข้อมูลเดโมทันที (ใช้ได้เฉพาะโหมดเดโม) เช่น ก่อนนำเสนองาน
require_once '../includes/db.php';
require_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !demo_mode()) {
    redirect("index.php");
}
verify_csrf();

if (reset_demo($conn)) {
    // ตาราง admins ถูกสร้างใหม่ด้วย ให้แอดมินล็อกอินใหม่ด้วยบัญชีทดลอง
    $_SESSION = [];
    session_regenerate_id(true);
    flash("รีเซ็ตข้อมูลเดโมเรียบร้อยแล้ว กรุณาเข้าสู่ระบบอีกครั้ง");
    redirect("login.php");
}
flash("รีเซ็ตไม่สำเร็จ กรุณาลองใหม่", "error");
redirect("index.php");
