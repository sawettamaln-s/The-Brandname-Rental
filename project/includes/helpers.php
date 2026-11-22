<?php
// ฟังก์ชันส่วนกลาง: session, การแสดงข้อความอย่างปลอดภัย, CSRF, การตรวจสิทธิ์, ค่ามัดจำ, การสร้างรหัส และการอัปโหลดรูป

const DEPOSIT_FEE = 900;        // ค่ามัดจำต่อกระเป๋าหนึ่งใบสำหรับผู้ที่ไม่เป็นสมาชิก
const MEMBER_FEE = 3000;        // ค่าสมัครสมาชิก 1 ปี
const MAX_RENTAL_DAYS = 30;     // จำนวนวันเช่าสูงสุดต่อรายการ
const IMAGE_MAX_BYTES = 2 * 1024 * 1024;
const APP_ROOT = __DIR__ . '/..';
const SLIP_DIR = APP_ROOT . '/uploads/slips';
const PRODUCT_IMAGE_DIR = APP_ROOT . '/assets/images/products';

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

function baht($amount): string
{
    return number_format((float) $amount, 2) . ' บาท';
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
// หน้าลูกค้าอยู่ที่โฟลเดอร์หลัก หน้าแอดมินอยู่ใน admin/ จึง redirect ไปหน้าเข้าสู่ระบบของโฟลเดอร์ตัวเอง
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
        redirect('login.php');
    }
}

/** เริ่ม session ใหม่หลังเข้าสู่ระบบ เพื่อกัน session fixation */
function start_login_session(): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
}

// ---------- ข้อความแจ้งผลครั้งเดียว ----------
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['text' => $message, 'type' => $type];
}

/** คืนค่า ['text' => ..., 'type' => success|error] หรือ null */
function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
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

/**
 * ลูกค้าที่ล็อกอินอยู่ ถ้ายังไม่ล็อกอินหรือไม่พบบัญชีแล้ว (เช่น ข้อมูลเดโมถูกรีเซ็ต) จะออกจากระบบและไปหน้าเข้าสู่ระบบ
 */
function current_customer(mysqli $conn): array
{
    $customer = find_customer($conn, require_customer());
    if (!$customer) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('ไม่พบบัญชีของคุณแล้ว กรุณาเข้าสู่ระบบใหม่', 'error');
        redirect('login.php');
    }
    return $customer;
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

// ---------- สินค้า ----------
/** URL รูปสินค้า (เทียบจากโฟลเดอร์ของหน้าที่เรียก) ถ้าไม่มีรูปจะได้รูปตัวแทน */
function product_image_url(array $product, string $base = ''): string
{
    $file = basename((string) ($product['image'] ?? ''));
    if ($file !== '' && is_file(PRODUCT_IMAGE_DIR . '/' . $file)) {
        return $base . 'assets/images/products/' . rawurlencode($file);
    }
    return $base . 'assets/images/no-image.svg';
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
    $stmt = $conn->prepare('SELECT product_id, brand, details, daily_rental_price, image FROM products WHERE product_id = ?');
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

function cart_count(): int
{
    return count($_SESSION['cart'] ?? []);
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

// ---------- อัปโหลดรูป ----------
/**
 * ตรวจและบันทึกรูปที่อัปโหลด รับเฉพาะ JPG/PNG/WEBP ไม่เกิน 2 MB
 * ตรวจชนิดไฟล์จากเนื้อไฟล์จริง และตั้งชื่อไฟล์เอง (ไม่ใช้ชื่อที่ผู้ใช้ส่งมา) นามสกุลมาจากชนิดไฟล์ที่ตรวจได้
 * $name คือชื่อไฟล์ไม่รวมนามสกุล ถ้าไม่ระบุจะสุ่มให้
 * คืนค่า ['file' => ชื่อไฟล์] เมื่อสำเร็จ หรือ ['error' => ข้อความ] เมื่อไม่ผ่าน
 * โหมดเดโม: ตรวจไฟล์ตามปกติแต่ไม่เก็บไฟล์ที่ส่งมา สลิปจะใช้รูปตัวอย่างแทน
 * ส่วนรูปที่ตั้งชื่อเอง (รูปสินค้า) คืนค่า ['file' => null, 'demo' => true] ให้หน้านั้นใช้รูปตัวแทน
 */
function save_image(?array $upload, string $dir, ?string $name = null): array
{
    if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'กรุณาเลือกไฟล์รูป'];
    }
    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > IMAGE_MAX_BYTES) {
        return ['error' => 'ไฟล์ใหญ่เกิน 2 MB หรืออัปโหลดไม่สำเร็จ'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset($allowed[$mime]) || getimagesize($upload['tmp_name']) === false) {
        return ['error' => 'รองรับเฉพาะไฟล์รูป JPG, PNG หรือ WEBP'];
    }

    if (demo_mode() && $name !== null) {
        return ['file' => null, 'demo' => true];
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (demo_mode()) {
        $file = bin2hex(random_bytes(16)) . '.png';
        return copy(DEMO_SLIP_SAMPLE, $dir . '/' . $file) ? ['file' => $file] : ['error' => 'บันทึกไฟล์ไม่สำเร็จ กรุณาลองใหม่'];
    }
    $file = ($name ?? bin2hex(random_bytes(16))) . '.' . $allowed[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $dir . '/' . $file)) {
        return ['error' => 'บันทึกไฟล์ไม่สำเร็จ กรุณาลองใหม่'];
    }
    return ['file' => $file];
}
