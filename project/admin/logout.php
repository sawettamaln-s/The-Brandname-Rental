<?php
// ออกจากระบบต้องส่งแบบ POST พร้อม CSRF token เพื่อไม่ให้เว็บอื่นสั่งออกจากระบบแทนผู้ใช้ได้
require_once '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
}
redirect("login.php");
