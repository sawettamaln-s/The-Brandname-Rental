<?php
require_once '../includes/db.php';
require_admin();

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

$stmt = $conn->prepare("SELECT * FROM products WHERE brand LIKE ? OR details LIKE ? ORDER BY product_id");
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ตัวเลขงานที่รอทำ แสดงบนหน้าแรกของแอดมิน
$pending = $conn->query("SELECT
    (SELECT COUNT(*) FROM rentals WHERE rental_status = 'Pending') AS orders,
    (SELECT COUNT(*) FROM track_return WHERE returnl_status = 'Pending') AS returns,
    (SELECT COUNT(*) FROM membership_requests WHERE status = 'Pending') AS members")->fetch_assoc();

page_start('สินค้า', 'admin', 'products');
?>
<div class="page-head">
    <h1>สินค้า</h1>
    <div class="actions" style="margin: 0;">
        <?php if (demo_mode()): ?>
            <form action="demo_reset.php" method="POST" class="inline-form" onsubmit="return confirm('ล้างข้อมูลทั้งหมดกลับเป็นค่าตั้งต้นของเดโม?');">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-outline">รีเซ็ตข้อมูลเดโม</button>
            </form>
        <?php endif; ?>
        <a href="product_add.php" class="btn">+ เพิ่มสินค้า</a>
    </div>
</div>
<?php render_alert(take_flash()); ?>

<?php if ($pending['orders'] || $pending['returns'] || $pending['members']): ?>
    <div class="alert alert-info">
        งานที่รอตรวจ:
        <a href="orders.php">คำสั่งเช่า <?php echo (int) $pending['orders']; ?></a> ·
        <a href="returns.php">การคืน <?php echo (int) $pending['returns']; ?></a> ·
        <a href="members.php">คำขอสมาชิก <?php echo (int) $pending['members']; ?></a>
    </div>
<?php endif; ?>

<form action="index.php" method="GET" class="search">
    <input type="text" name="search" placeholder="ค้นหายี่ห้อหรือรุ่น..." value="<?php echo e($search); ?>">
    <button type="submit">ค้นหา</button>
</form>

<?php if ($products): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?php echo e(product_image_url($product, '../')); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>" loading="lazy">
                <p class="product-meta"><?php echo e($product['product_id']); ?></p>
                <h3><?php echo e($product['brand']); ?></h3>
                <p class="details"><?php echo e($product['details']); ?></p>
                <p class="price"><?php echo baht($product['daily_rental_price']); ?> / วัน</p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-center muted">ไม่พบสินค้าที่ค้นหา</p>
<?php endif; ?>
<?php page_end(); ?>
