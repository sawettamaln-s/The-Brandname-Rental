<?php
require_once '../includes/db.php';
require_admin();

// ภาพรวมของร้าน: งานที่ต้องทำ ตัวเลขสำคัญ และรายการที่ต้องรีบดูใน 3 วันข้างหน้า
const SOON_DAYS = 3;

$todo = $conn->query("SELECT
    (SELECT COUNT(DISTINCT order_id) FROM rentals WHERE rental_status = 'Pending') AS orders,
    (SELECT COUNT(*) FROM rentals WHERE rental_status IN ('Accepted', 'Preparing')) AS to_ship,
    (SELECT COUNT(*) FROM rentals WHERE rental_status = 'Shipped' AND due_date < CURDATE()) AS overdue,
    (SELECT COUNT(*) FROM rentals WHERE rental_status = 'Returning') AS incoming,
    (SELECT COUNT(*) FROM rentals WHERE rental_status = 'Received') AS inspect,
    (SELECT COUNT(*) FROM membership_requests WHERE status = 'Pending') AS members")->fetch_assoc();

// ค่าเช่าเดือนนี้นับการเช่าที่ร้านรับเรื่องแล้วและเริ่มเช่าในเดือนนี้ มัดจำที่ถืออยู่คือมัดจำของการเช่าที่ยังไม่ปิดงาน
$figures = $conn->query("SELECT
    (SELECT COUNT(*) FROM rentals WHERE rental_status = 'Shipped') AS on_rent,
    (SELECT COALESCE(SUM(daily_price * rental_days), 0) FROM rentals
        WHERE rental_status NOT IN ('Pending', 'Cancelled')
          AND rental_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND rental_date < DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH) AS month_rent,
    (SELECT COALESCE(SUM(deposit_fee), 0) FROM rentals WHERE rental_status IN ('Accepted', 'Preparing', 'Shipped', 'Returning', 'Received')) AS deposits_held,
    (SELECT COUNT(*) FROM customers WHERE customers_member = 'yes' AND (member_until IS NULL OR member_until >= CURDATE())) AS members")->fetch_assoc();

$soon = SOON_DAYS;
$base = "SELECT r.rental_id, r.product_id, p.brand, r.rental_date, r.due_date, r.rental_status,
    o.customers_id, c.customers_firstname, c.customers_lastname, c.phonenumber
    FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    JOIN customers c ON c.customers_id = o.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id";
$ship_soon = $conn->query("$base WHERE r.rental_status IN ('Accepted', 'Preparing') AND r.rental_date <= CURDATE() + INTERVAL $soon DAY
    ORDER BY r.rental_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);
$due_soon = $conn->query("$base WHERE r.rental_status = 'Shipped' AND r.due_date <= CURDATE() + INTERVAL $soon DAY
    ORDER BY r.due_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);

$cards = [
    ['orders', 'คำสั่งรอตรวจสลิป', 'orders.php', 'todo'],
    ['to_ship', 'รอเตรียมและจัดส่ง', 'orders.php', 'todo'],
    ['overdue', 'เกินกำหนดคืน', 'orders.php', 'urgent'],
    ['incoming', 'กำลังส่งคืน', 'returns.php', 'todo'],
    ['inspect', 'รอตรวจสภาพ', 'returns.php', 'todo'],
    ['members', 'คำขอสมาชิก', 'members.php', 'todo'],
];
$customer_name = fn(array $row) => $row['customers_firstname'] . ' ' . $row['customers_lastname'];

page_start('ภาพรวม', 'admin', 'dashboard');
?>
<div class="page-head">
    <h1>ภาพรวม</h1>
    <?php if (demo_mode()): ?>
        <form action="demo_reset.php" method="POST" class="inline-form" onsubmit="return confirm('ล้างข้อมูลทั้งหมดกลับเป็นค่าตั้งต้นของเดโม?');">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-outline">รีเซ็ตข้อมูลเดโม</button>
        </form>
    <?php endif; ?>
</div>
<?php render_alert(take_flash()); ?>

<h2>งานที่ต้องทำ</h2>
<div class="stat-grid">
    <?php foreach ($cards as [$key, $label, $href, $tone]): $count = (int) $todo[$key]; ?>
        <a class="stat-card <?php echo $count > 0 ? $tone : 'idle'; ?>" href="<?php echo $href; ?>">
            <div class="stat-num"><?php echo $count; ?></div>
            <div class="stat-label"><?php echo e($label); ?></div>
        </a>
    <?php endforeach; ?>
</div>

<h2>ตัวเลขของร้าน</h2>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-num"><?php echo (int) $figures['on_rent']; ?></div><div class="stat-label">ใบที่อยู่กับลูกค้า</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo number_format($figures['month_rent']); ?></div><div class="stat-label">ค่าเช่าเดือนนี้ (บาท)</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo number_format($figures['deposits_held']); ?></div><div class="stat-label">มัดจำที่ถืออยู่ (บาท)</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo (int) $figures['members']; ?></div><div class="stat-label">สมาชิกที่ยังไม่หมดอายุ</div></div>
</div>

<div class="stack">
    <section class="card">
        <h2>ต้องจัดส่งภายใน <?php echo SOON_DAYS; ?> วัน</h2>
        <?php if ($ship_soon): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>รหัสการเช่า</th><th>สินค้า</th><th>ลูกค้า</th><th>วันเริ่มเช่า</th><th>สถานะ</th></tr></thead>
                    <tbody>
                        <?php foreach ($ship_soon as $row): ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td><?php echo e($customer_name($row)); ?></td>
                                <td><?php echo e(thai_date($row['rental_date'])); ?><?php if ($row['rental_date'] < date('Y-m-d')): ?> <span class="badge badge-danger">เลยวันเริ่มเช่า</span><?php endif; ?></td>
                                <td><?php echo status_badge($row); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="orders.php" class="btn btn-outline btn-sm" style="margin-top: 12px;">ไปที่คำสั่งเช่า</a>
        <?php else: ?>
            <p class="muted">ไม่มีกระเป๋าที่ต้องส่งในช่วงนี้</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>ครบกำหนดคืนภายใน <?php echo SOON_DAYS; ?> วัน และเกินกำหนด</h2>
        <?php if ($due_soon): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>รหัสการเช่า</th><th>สินค้า</th><th>ลูกค้า</th><th>ส่งคืนภายใน</th><th>สถานะ</th></tr></thead>
                    <tbody>
                        <?php foreach ($due_soon as $row): ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td><?php echo e($customer_name($row)); ?><div class="hint"><?php echo e($row['phonenumber']); ?></div></td>
                                <td><?php echo e(thai_date($row['due_date'])); ?></td>
                                <td><?php echo status_badge($row); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ไม่มีกระเป๋าที่ใกล้ครบกำหนดคืน</p>
        <?php endif; ?>
    </section>
</div>
<?php page_end(); ?>
