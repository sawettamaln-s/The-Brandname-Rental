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

page_start('สินค้า', 'admin', 'products');
?>
<div class="page-head">
    <h1>สินค้า</h1>
    <div class="actions" style="margin: 0;">
        <a href="product_add.php" class="btn">+ เพิ่มสินค้า</a>
    </div>
</div>
<?php render_alert(take_flash()); ?>

<form action="products.php" method="GET" class="search" style="margin-bottom: 28px;">
    <?php echo icon('search'); ?>
    <input type="text" name="search" placeholder="ค้นหายี่ห้อหรือรุ่น..." value="<?php echo e($search); ?>">
    <button type="submit">ค้นหา</button>
</form>

<?php if ($products): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="frame"><img src="<?php echo e(product_image_url($product, '../')); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>" loading="lazy"></div>
                <p class="product-meta"><?php echo e($product['product_id']); ?></p>
                <h3><?php echo e($product['brand']); ?></h3>
                <p class="details"><?php echo e($product['details']); ?></p>
                <p class="price"><?php echo number_format($product['daily_rental_price']); ?> บาท <small>/ วัน</small></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-center muted">ไม่พบสินค้าที่ค้นหา</p>
<?php endif; ?>
<?php page_end(); ?>
