<?php
// โหมดเดโม: รีเซ็ตข้อมูลอัตโนมัติ แถบบัญชีทดลอง และการล็อกบัญชีทดลอง
// เปิดด้วย 'demo_mode' => true ใน config.local.php

const DEMO_STAMP_FILE = APP_ROOT . '/uploads/.demo-reset-at';
const DEMO_SLIP_SAMPLE = APP_ROOT . '/assets/images/demo-slip.png';

// บัญชีทดลองที่แสดงบนแถบเดโม (ต้องตรงกับข้อมูลใน schema.sql)
const DEMO_ACCOUNTS = [
    ['role' => 'ลูกค้าทั่วไป', 'user' => 'chaiya@gmail.com', 'pass' => 'demo1234'],
    ['role' => 'ลูกค้าสมาชิก', 'user' => 'thanawat@gmail.com', 'pass' => 'demo1234'],
    ['role' => 'แอดมิน', 'user' => 'admin', 'pass' => 'Admin@1234'],
];

function demo_mode(): bool
{
    global $config;
    return !empty($config['demo_mode']);
}

/** บัญชีลูกค้าตัวอย่างจาก schema.sql (C001–C010) ห้ามเปลี่ยนอีเมล เพื่อให้คนต่อไปยังล็อกอินด้วยบัญชีทดลองได้ */
function is_seed_account(string $customer_id): bool
{
    return demo_mode() && preg_match('/^C0(0[1-9]|10)$/', $customer_id) === 1;
}

/** ไฟล์ schema: บนโฮสต์อยู่ที่ includes/schema.sql ในเครื่องพัฒนาอยู่ที่ database/schema.sql */
function demo_seed_file(): ?string
{
    foreach ([__DIR__ . '/schema.sql', APP_ROOT . '/../database/schema.sql'] as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/**
 * รีเซ็ตเมื่อเวลาผ่านไปครบกำหนดนับจากการรีเซ็ตครั้งล่าสุด หรือเมื่อ schema.sql เปลี่ยน (อัปโหลดโค้ดเวอร์ชันใหม่ที่มีตารางต่างจากเดิม)
 * ไฟล์ stamp เก็บเวลาที่รีเซ็ตและ hash ของ schema.sql ที่ใช้ ครั้งแรกที่เปิดเว็บจะเริ่มนับเวลา ไม่ล้างข้อมูล
 */
function maybe_reset_demo(mysqli $conn): void
{
    global $config;
    $stamp = is_file(DEMO_STAMP_FILE) ? explode("\n", trim((string) file_get_contents(DEMO_STAMP_FILE))) : [];
    $last = (int) ($stamp[0] ?? 0);
    if ($last === 0) {
        demo_write_stamp();
        return;
    }
    $schema_changed = ($stamp[1] ?? '') !== demo_schema_hash();
    if ($schema_changed || time() - $last >= (int) $config['demo_reset_hours'] * 3600) {
        reset_demo($conn);
    }
}

function demo_schema_hash(): string
{
    $seed = demo_seed_file();
    return $seed === null ? '' : md5_file($seed);
}

function demo_write_stamp(): void
{
    if (!is_dir(dirname(DEMO_STAMP_FILE))) {
        mkdir(dirname(DEMO_STAMP_FILE), 0755, true);
    }
    file_put_contents(DEMO_STAMP_FILE, time() . "\n" . demo_schema_hash(), LOCK_EX);
}

/**
 * ล้างข้อมูลกลับเป็นค่าตั้งต้นจาก schema.sql และลบสลิปที่อัปโหลด
 * ใช้ไฟล์ล็อกกันสองคำขอรีเซ็ตพร้อมกัน คืนค่า false ถ้ามีคำขออื่นกำลังรีเซ็ตอยู่หรือหา schema ไม่เจอ
 */
function reset_demo(mysqli $conn): bool
{
    $seed = demo_seed_file();
    if ($seed === null) {
        error_log('demo reset skipped: schema.sql not found');
        return false;
    }

    $lock = fopen(DEMO_STAMP_FILE . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return false;
    }
    try {
        $conn->multi_query(file_get_contents($seed));
        // ต้องอ่านผลของทุกคำสั่งให้ครบ ไม่อย่างนั้นคำสั่งถัดไปของหน้าเว็บจะใช้การเชื่อมต่อไม่ได้
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        foreach (glob(SLIP_DIR . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        demo_write_stamp();
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log('demo reset failed: ' . $e->getMessage());
        return false;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** แถบเดโมใต้ส่วนหัว: บอกว่าเป็นเว็บทดลองและบัญชีที่ใช้ได้ */
function demo_banner(string $base): void
{
    if (!demo_mode()) {
        return;
    }
    $parts = [];
    foreach (DEMO_ACCOUNTS as $account) {
        $parts[] = e($account['role']) . ' <code>' . e($account['user']) . '</code> / <code>' . e($account['pass']) . '</code>';
    }
    echo '<div class="demo-bar"><div class="container">'
        . '<strong>เว็บเดโม</strong> ข้อมูลรีเซ็ตทุกวัน · ' . implode(' · ', $parts)
        . ' · <a href="' . e($base) . 'admin/login.php">หลังร้าน</a>'
        . '</div></div>';
}
