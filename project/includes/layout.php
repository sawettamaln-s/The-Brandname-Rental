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
    $has_badge = (bool) array_filter($nav, fn($item) => !empty($item['count']));
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> · <?php echo SHOP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600&family=Noto+Serif+Thai:wght@500;600&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <?php // เลขเวอร์ชันจากเวลาที่แก้ไฟล์ล่าสุด: อัปโหลด CSS ใหม่แล้วเบราว์เซอร์โหลดใหม่เอง ไม่ใช้ไฟล์เก่าที่จำไว้ ?>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css?v=<?php echo (int) @filemtime(APP_ROOT . '/assets/css/style.css'); ?>">
</head>
<body class="area-<?php echo e($area); ?>">
<div class="top-strip">
    <div class="container"><?php echo $area === 'admin' ? 'ระบบหลังร้าน' : 'เช่ารายวัน 1–' . MAX_RENTAL_DAYS . ' วัน · สมาชิกไม่ต้องวางมัดจำ'; ?></div>
</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php">
            <span class="brand-name">The Brandname</span>
            <span class="brand-sub">Rental<?php echo $area === 'admin' ? ' · Admin' : ''; ?></span>
        </a>
        <button type="button" class="nav-toggle<?php echo $has_badge ? ' has-badge' : ''; ?>" aria-expanded="false" aria-controls="site-nav" aria-label="เปิดเมนู">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="site-nav">
            <?php foreach ($nav as $item): ?>
                <?php if (isset($item['post'])): ?>
                    <form method="POST" action="<?php echo e($item['post']); ?>" class="nav-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="nav-link"><?php echo icon($item['icon']); ?><span><?php echo e($item['label']); ?></span></button>
                    </form>
                <?php else: ?>
                    <a class="nav-link<?php echo $item['key'] === $active ? ' active' : ''; ?>" href="<?php echo e($item['href']); ?>">
                        <?php echo icon($item['icon']); ?><span><?php echo e($item['label']); ?></span>
                        <?php if (!empty($item['count'])): ?><span class="nav-count"><?php echo (int) $item['count']; ?></span><?php endif; ?>
                    </a>
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
    <div class="container footer-inner">
        <div>
            <div class="brand-name">The Brandname Rental</div>
            <div class="footer-tag">เช่ากระเป๋าแบรนด์เนมรายวัน</div>
        </div>
        <div class="footer-note">
            © <?php echo date('Y'); ?> · โปรเจกต์วิชาฐานข้อมูล ร้านสมมติเพื่อการศึกษา ไม่ได้เปิดให้เช่าจริง<br>
            ภาพและชื่อแบรนด์เป็นของเจ้าของลิขสิทธิ์ ใช้ประกอบการเรียนเท่านั้น
        </div>
    </div>
</footer>
<script>
// เมนู 3 ขีดบนจอเล็ก: กดเพื่อเปิด/ปิดรายการเมนู
(function () {
    const header = document.querySelector('.site-header');
    const toggle = header.querySelector('.nav-toggle');
    toggle.addEventListener('click', function () {
        const open = header.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'ปิดเมนู' : 'เปิดเมนู');
    });
})();

// ตารางบนจอเล็กแสดงเป็นการ์ด: ใส่ชื่อคอลัมน์ให้แต่ละช่อง (data-label) และห่อเนื้อหาในช่องไว้ใน div เดียว
// CSS จะเปลี่ยนรูปแบบเฉพาะตารางที่มีคลาส is-cards ถ้า JavaScript ไม่ทำงาน ตารางยังเลื่อนซ้ายขวาได้ตามเดิม
document.querySelectorAll('table.table').forEach(function (table) {
    const labels = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
    table.querySelectorAll('tbody tr, tfoot tr').forEach(function (row) {
        let col = 0;
        Array.from(row.children).forEach(function (cell) {
            if (row.parentElement.tagName === 'TBODY') {
                cell.dataset.label = labels[col] || '';
            }
            col += cell.colSpan || 1;
            const value = document.createElement('div');
            value.className = 'cell-value';
            while (cell.firstChild) value.appendChild(cell.firstChild);
            cell.appendChild(value);
        });
    });
    table.classList.add('is-cards');
});
</script>
</body>
</html>
    <?php
}

function shop_nav(): array
{
    $items = [['key' => 'home', 'label' => 'กระเป๋า', 'href' => 'index.php', 'icon' => 'store']];
    if (!empty($_SESSION['customers_id'])) {
        $items[] = ['key' => 'cart', 'label' => 'ตะกร้า', 'href' => 'cart.php', 'icon' => 'cart', 'count' => cart_count()];
        $items[] = ['key' => 'rentals', 'label' => 'การเช่าของฉัน', 'href' => 'rentals.php', 'icon' => 'bag'];
        $items[] = ['key' => 'profile', 'label' => 'บัญชี', 'href' => 'profile.php', 'icon' => 'user'];
        $items[] = ['key' => 'logout', 'label' => 'ออกจากระบบ', 'post' => 'logout.php', 'icon' => 'logout'];
    } else {
        $items[] = ['key' => 'login', 'label' => 'เข้าสู่ระบบ', 'href' => 'login.php', 'icon' => 'user'];
        $items[] = ['key' => 'register', 'label' => 'สร้างบัญชี', 'href' => 'register.php', 'icon' => 'heart'];
    }
    return $items;
}

function admin_nav(): array
{
    if (empty($_SESSION['admin_id'])) {
        return [['key' => 'shop', 'label' => 'กลับหน้าร้าน', 'href' => '../index.php', 'icon' => 'store']];
    }
    return [
        ['key' => 'dashboard', 'label' => 'ภาพรวม', 'href' => 'index.php', 'icon' => 'grid'],
        ['key' => 'products', 'label' => 'สินค้า', 'href' => 'products.php', 'icon' => 'tag'],
        ['key' => 'orders', 'label' => 'คำสั่งเช่า', 'href' => 'orders.php', 'icon' => 'truck'],
        ['key' => 'returns', 'label' => 'การคืน', 'href' => 'returns.php', 'icon' => 'box-heart'],
        ['key' => 'members', 'label' => 'สมาชิก', 'href' => 'members.php', 'icon' => 'card'],
        ['key' => 'employees', 'label' => 'พนักงาน', 'href' => 'employees.php', 'icon' => 'people'],
        ['key' => 'logout', 'label' => 'ออกจากระบบ', 'post' => 'logout.php', 'icon' => 'logout'],
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
    echo icon('calendar') . ' <span>' . implode(' · ', array_map(fn($r) => e($r['text']), $reminders)) . '</span>';
    echo ' <a href="rentals.php#return-form">แจ้งส่งคืน</a>';
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
