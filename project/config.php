<?php
// ค่าการเชื่อมต่อฐานข้อมูล
// ถ้าเครื่องหรือโฮสต์ใช้ค่าต่างจากนี้ ให้สร้างไฟล์ config.local.php ที่ return array แบบเดียวกัน
// (config.local.php ไม่ถูกเก็บใน git จึงใส่รหัสผ่านจริงได้)
$config = [
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'project',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
