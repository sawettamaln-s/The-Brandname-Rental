<?php
// เชื่อมต่อฐานข้อมูลและโหลดฟังก์ชันส่วนกลาง ทุกหน้าที่ include ไฟล์นี้จะได้ $conn, session และฟังก์ชันส่วนหัว/ท้ายหน้า
$config = require __DIR__ . '/config.php';

// ให้ mysqli โยน exception เมื่อคำสั่ง SQL ผิดพลาด แทนการเงียบแล้วทำงานต่อ
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], (int) $config['db_port']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่ภายหลัง');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';
