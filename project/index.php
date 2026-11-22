<?php
require_once 'includes/db.php';

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

$stmt = $conn->prepare("SELECT * FROM products WHERE brand LIKE ? OR details LIKE ? ORDER BY product_id");
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_start('สินค้าให้เช่า', 'shop', 'home');
?>
<form action="index.php" method="GET" class="search">
    <input type="text" name="search" placeholder="ค้นหายี่ห้อหรือรุ่น..." value="<?php echo e($search); ?>">
    <button type="submit">ค้นหา</button>
</form>

<?php if ($products): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?php echo e(product_image_url($product)); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>" loading="lazy">
                <h3><?php echo e($product['brand']); ?></h3>
                <p class="details"><?php echo e($product['details']); ?></p>
                <p class="price"><?php echo baht($product['daily_rental_price']); ?> / วัน</p>
                <a href="product_detail.php?product_id=<?php echo urlencode($product['product_id']); ?>" class="btn">เช่าตอนนี้</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-center muted">ไม่พบสินค้าที่ค้นหา</p>
<?php endif; ?>
<?php page_end(); ?>
