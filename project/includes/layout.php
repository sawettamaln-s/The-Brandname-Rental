<?php
// ส่วนหัวและส่วนท้ายที่ทุกหน้าใช้ร่วมกัน
// หน้าลูกค้าเรียก page_start('ชื่อหน้า') หน้าแอดมินเรียก page_start('ชื่อหน้า', 'admin')
// หน้าแอดมินอยู่ในโฟลเดอร์ admin/ จึงต้องอ้างไฟล์ใน assets/ ด้วย ../

const SHOP_NAME = 'THE BRANDNAME RENTAL';

function page_base(string $area): string
{
    return $area === 'admin' ? '../' : '';
}

/**
 * @param string $active  ชื่อเมนูที่กำลังเปิด ใช้ไฮไลต์เมนู เช่น 'home', 'cart', 'orders'
 */
function page_start(string $title, string $area = 'shop', string $active = ''): void
{
    $base = page_base($area);
    $nav = $area === 'admin' ? admin_nav() : shop_nav();
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> · <?php echo SHOP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body class="area-<?php echo e($area); ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php"><?php echo SHOP_NAME; ?><?php echo $area === 'admin' ? '<span class="brand-tag">ADMIN</span>' : ''; ?></a>
        <nav class="nav">
            <?php foreach ($nav as $item): ?>
                <?php if (isset($item['post'])): ?>
                    <form method="POST" action="<?php echo e($item['post']); ?>" class="nav-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="nav-link"><?php echo e($item['label']); ?></button>
                    </form>
                <?php else: ?>
                    <a class="nav-link<?php echo $item['key'] === $active ? ' active' : ''; ?>" href="<?php echo e($item['href']); ?>"><?php echo e($item['label']); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<?php demo_banner($base); ?>
<?php if ($area === 'shop') { reminder_banner(); } ?>
<main class="container page">
    <?php
}

function page_end(): void
{
    ?>
</main>
<footer class="site-footer">
    <div class="container">
        © <?php echo date('Y'); ?> <?php echo SHOP_NAME; ?> · โปรเจกต์วิชาฐานข้อมูล
        <div class="footer-note">ร้านสมมติเพื่อการศึกษา ไม่ได้เปิดให้เช่าจริง · ภาพและชื่อแบรนด์เป็นของเจ้าของลิขสิทธิ์ ใช้ประกอบการเรียนเท่านั้น</div>
    </div>
</footer>
</body>
</html>
    <?php
}

function shop_nav(): array
{
    $items = [['key' => 'home', 'label' => 'สินค้า', 'href' => 'index.php']];
    if (!empty($_SESSION['customers_id'])) {
        $count = cart_count();
        $items[] = ['key' => 'cart', 'label' => 'ตะกร้า' . ($count ? " ($count)" : ''), 'href' => 'cart.php'];
        $items[] = ['key' => 'rentals', 'label' => 'การเช่าของฉัน', 'href' => 'rentals.php'];
        $items[] = ['key' => 'profile', 'label' => 'โปรไฟล์', 'href' => 'profile.php'];
        $items[] = ['key' => 'logout', 'label' => 'ออกจากระบบ', 'post' => 'logout.php'];
    } else {
        $items[] = ['key' => 'login', 'label' => 'เข้าสู่ระบบ', 'href' => 'login.php'];
        $items[] = ['key' => 'register', 'label' => 'สร้างบัญชี', 'href' => 'register.php'];
    }
    return $items;
}

function admin_nav(): array
{
    if (empty($_SESSION['admin_id'])) {
        return [['key' => 'shop', 'label' => 'กลับหน้าร้าน', 'href' => '../index.php']];
    }
    return [
        ['key' => 'dashboard', 'label' => 'ภาพรวม', 'href' => 'index.php'],
        ['key' => 'products', 'label' => 'สินค้า', 'href' => 'products.php'],
        ['key' => 'orders', 'label' => 'คำสั่งเช่า', 'href' => 'orders.php'],
        ['key' => 'returns', 'label' => 'การคืน', 'href' => 'returns.php'],
        ['key' => 'members', 'label' => 'อนุมัติสมาชิก', 'href' => 'members.php'],
        ['key' => 'employees', 'label' => 'พนักงาน', 'href' => 'employees.php'],
        ['key' => 'logout', 'label' => 'ออกจากระบบ', 'post' => 'logout.php'],
    ];
}

/** แถบแจ้งเตือนวันคืนใต้ส่วนหัวของหน้าลูกค้า แสดงเมื่อมีกระเป๋าที่ครบกำหนดคืนพรุ่งนี้ วันนี้ หรือเกินกำหนด */
function reminder_banner(): void
{
    global $conn;
    if (empty($_SESSION['customers_id']) || !($conn instanceof mysqli)) {
        return;
    }
    $reminders = due_reminders($conn, $_SESSION['customers_id']);
    if (!$reminders) {
        return;
    }
    $urgent = (bool) array_filter($reminders, fn($r) => $r['urgent']);
    echo '<div class="reminder-bar' . ($urgent ? ' urgent' : '') . '"><div class="container">';
    echo '<strong>แจ้งเตือนวันคืน</strong> ';
    echo implode(' · ', array_map(fn($r) => e($r['text']), $reminders));
    echo ' · <a href="rentals.php#return-form">แจ้งเลขพัสดุส่งคืน</a>';
    echo '</div></div>';
}

/** กล่องข้อความแจ้งผลจาก flash() หรือข้อความที่ส่งเข้ามาโดยตรง */
function render_alert(?array $message): void
{
    if (!$message) {
        return;
    }
    $type = $message['type'] === 'error' ? 'error' : 'success';
    echo '<div class="alert alert-' . $type . '">' . e($message['text']) . '</div>';
}

function error_alert(string $text): void
{
    if ($text !== '') {
        render_alert(['text' => $text, 'type' => 'error']);
    }
}
