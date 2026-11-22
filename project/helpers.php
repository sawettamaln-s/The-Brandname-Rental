<?php
// ฟังก์ชันส่วนกลาง: session, การแสดงข้อความอย่างปลอดภัย, CSRF, การตรวจสิทธิ์, ค่ามัดจำ, การสร้างรหัส และการอัปโหลดสลิป

const DEPOSIT_FEE = 900;        // ค่ามัดจำต่อกระเป๋าหนึ่งใบสำหรับผู้ที่ไม่เป็นสมาชิก
const MEMBER_FEE = 3000;        // ค่าสมัครสมาชิก 1 ปี
const MAX_RENTAL_DAYS = 30;     // จำนวนวันเช่าสูงสุดต่อรายการ
const SLIP_MAX_BYTES = 2 * 1024 * 1024;
const SLIP_DIR = __DIR__ . '/uploads/slips';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/** แปลงข้อความให้แสดงใน HTML ได้อย่างปลอดภัย (กัน XSS) */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** ค่าข้อความจากฟอร์ม POST (ตัดช่องว่างหัวท้าย) ถ้าไม่ใช่ข้อความจะได้ค่าว่าง */
function input(string $key, bool $trim = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return $trim ? trim($value) : $value;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

// ---------- CSRF: ทุกฟอร์ม POST ต้องส่ง token นี้มาด้วย ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        die('คำขอไม่ถูกต้อง กรุณากลับไปหน้าเดิมแล้วลองใหม่');
    }
}

// ---------- การตรวจสิทธิ์ ----------
function require_customer(): string
{
    if (empty($_SESSION['customers_id'])) {
        redirect('login.php');
    }
    return $_SESSION['customers_id'];
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('admin_login.php');
    }
}

/** เริ่ม session ใหม่หลังเข้าสู่ระบบ เพื่อกัน session fixation */
function start_login_session(): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
}

// ---------- ข้อความแจ้งผลครั้งเดียว ----------
function flash(string $message): void
{
    $_SESSION['message'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    return $message;
}

// ---------- สมาชิกและค่ามัดจำ ----------
/** เป็นสมาชิกเมื่อ customers_member = yes และยังไม่หมดอายุ (ไม่มีวันหมดอายุถือว่ายังเป็นสมาชิก) */
function is_member(array $customer): bool
{
    if (($customer['customers_member'] ?? 'no') !== 'yes') {
        return false;
    }
    $until = $customer['member_until'] ?? null;
    return $until === null || $until >= date('Y-m-d');
}

function deposit_for(array $customer): int
{
    return is_member($customer) ? 0 : DEPOSIT_FEE;
}

function find_customer(mysqli $conn, string $customer_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM customers WHERE customers_id = ?');
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ---------- ตะกร้า ----------
/**
 * รายการในตะกร้าพร้อมข้อมูลสินค้าและราคาปัจจุบันจากฐานข้อมูล
 * ค่ามัดจำคิดต่อกระเป๋าหนึ่งใบตามสถานะสมาชิกของลูกค้า
 */
function cart_lines(mysqli $conn, array $customer): array
{
    $lines = [];
    $deposit = deposit_for($customer);
    $stmt = $conn->prepare('SELECT product_id, brand, details, daily_rental_price FROM products WHERE product_id = ?');
    foreach ($_SESSION['cart'] ?? [] as $item) {
        if (!isset($item['product_id'], $item['days'], $item['rental_date'])) {
            continue;
        }
        $stmt->bind_param('s', $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if (!$product) {
            continue;
        }
        $rent = (int) $product['daily_rental_price'] * (int) $item['days'];
        $lines[] = $product + [
            'days' => (int) $item['days'],
            'rental_date' => $item['rental_date'],
            'rent' => $rent,
            'deposit' => $deposit,
            'subtotal' => $rent + $deposit,
        ];
    }
    $stmt->close();
    return $lines;
}

// ---------- การสร้างรหัส ----------
/**
 * รหัสถัดไปของตาราง เช่น C011, PD0011, Ren1121
 * แปลงส่วนตัวเลขเป็นตัวเลขก่อนหาค่ามากสุด จึงเรียงถูกแม้จำนวนหลักเพิ่มขึ้น
 * $table และ $column มาจากโค้ดเท่านั้น ไม่ได้มาจากผู้ใช้
 */
function next_id(mysqli $conn, string $table, string $column, string $prefix, int $pad): string
{
    $start = strlen($prefix) + 1;
    $sql = "SELECT MAX(CAST(SUBSTRING($column, $start) AS UNSIGNED)) AS max_num FROM $table WHERE $column LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $max = (int) ($stmt->get_result()->fetch_assoc()['max_num'] ?? 0);
    $stmt->close();
    return $prefix . str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT);
}

function generate_tracking_number(): string
{
    $letters = chr(random_int(65, 90)) . chr(random_int(65, 90));
    $numbers = '';
    for ($i = 0; $i < 9; $i++) {
        $numbers .= random_int(0, 9);
    }
    return $letters . $numbers . 'TH';
}

function random_employee_id(mysqli $conn): ?string
{
    $row = $conn->query('SELECT employees_id FROM employees ORDER BY RAND() LIMIT 1')->fetch_assoc();
    return $row['employees_id'] ?? null;
}

// ---------- อัปโหลดสลิป ----------
/**
 * ตรวจและบันทึกสลิปที่อัปโหลด รับเฉพาะรูป JPG/PNG/WEBP ไม่เกิน 2 MB
 * ตั้งชื่อไฟล์ใหม่แบบสุ่ม (ไม่ใช้ชื่อที่ผู้ใช้ส่งมา) และตรวจชนิดไฟล์จากเนื้อไฟล์จริง
 * คืนค่า ['file' => ชื่อไฟล์] เมื่อสำเร็จ หรือ ['error' => ข้อความ] เมื่อไม่ผ่าน
 */
function save_slip(?array $upload): array
{
    if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => 'กรุณาเลือกไฟล์สลิปก่อนอัปโหลด'];
    }
    if ($upload['size'] > SLIP_MAX_BYTES) {
        return ['error' => 'ไฟล์ใหญ่เกิน 2 MB'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset($allowed[$mime]) || getimagesize($upload['tmp_name']) === false) {
        return ['error' => 'รองรับเฉพาะไฟล์รูป JPG, PNG หรือ WEBP'];
    }

    if (!is_dir(SLIP_DIR)) {
        mkdir(SLIP_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($upload['tmp_name'], SLIP_DIR . '/' . $name)) {
        return ['error' => 'บันทึกไฟล์ไม่สำเร็จ กรุณาลองใหม่'];
    }
    return ['file' => $name];
}
