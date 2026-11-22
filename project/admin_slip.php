<?php
// แสดงสลิปให้แอดมินดู โฟลเดอร์ uploads ถูกปิดไม่ให้เปิดผ่านเว็บโดยตรง
require_once 'db_connect.php';
require_admin();

$request_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$stmt = $conn->prepare("SELECT slip_file FROM membership_requests WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ชื่อไฟล์ถูกสร้างโดยระบบ แต่ใช้ basename กันไว้อีกชั้นไม่ให้อ่านไฟล์นอกโฟลเดอร์
$path = $row ? SLIP_DIR . '/' . basename($row['slip_file']) : null;
if (!$path || !is_file($path)) {
    http_response_code(404);
    die('ไม่พบไฟล์สลิป');
}

header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
