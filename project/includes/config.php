<?php
// ค่าการเชื่อมต่อฐานข้อมูลและโหมดการทำงาน
// ถ้าเครื่องหรือโฮสต์ใช้ค่าต่างจากนี้ ให้สร้างไฟล์ config.local.php ที่ return array แบบเดียวกัน (ใส่เฉพาะค่าที่ต่างก็ได้)
// (config.local.php ไม่ถูกเก็บใน git จึงใส่รหัสผ่านจริงได้)
$config = [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'project',

    // โหมดเดโมสำหรับเว็บที่เปิดให้คนทั่วไปลองเล่น
    // - ข้อมูลกลับเป็นค่าตั้งต้นจาก schema.sql อัตโนมัติเมื่อครบ demo_reset_hours ชั่วโมง
    // - แสดงแถบบอกบัญชีทดลองทุกหน้า
    // - ไม่เก็บไฟล์ที่ผู้ใช้อัปโหลดจริง (สลิปใช้รูปตัวอย่าง สินค้าใหม่ใช้รูปตัวแทน)
    'demo_mode' => false,
    'demo_reset_hours' => 24,
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
