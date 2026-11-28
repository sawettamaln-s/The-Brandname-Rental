<?php
require_once 'includes/db.php';

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

$stmt = $conn->prepare("SELECT * FROM products WHERE brand LIKE ? OR details LIKE ? ORDER BY product_id");
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_start('เช่ากระเป๋าแบรนด์เนม', 'shop', 'home');
?>
<section class="hero">
    <div class="hero-card">
        <div class="hero-kicker">Rent your dream bag</div>
        <h1>กระเป๋าใบโปรด<br>สำหรับวันพิเศษของคุณ</h1>
        <p>เช่ารายวัน เลือกวันเริ่มเช่าได้เอง ส่งถึงบ้านและส่งคืนทางพัสดุ</p>
        <form action="index.php#bags" method="GET" class="search">
            <?php echo icon('search'); ?>
            <input type="text" name="search" placeholder="ค้นหาแบรนด์หรือรุ่น" value="<?php echo e($search); ?>" aria-label="ค้นหากระเป๋า">
            <button type="submit">ค้นหา</button>
        </form>
    </div>
</section>

<?php if ($search === ''): ?>
    <section class="steps" aria-label="ขั้นตอนการเช่า">
        <div class="step"><div class="step-icon"><?php echo icon('bag'); ?></div><div class="step-title">เลือกกระเป๋า</div><p class="hint">เลือกวันเริ่มเช่าและจำนวนวัน</p></div>
        <div class="step"><div class="step-icon"><?php echo icon('card'); ?></div><div class="step-title">โอนและแนบสลิป</div><p class="hint">ร้านตรวจสลิปและรับเรื่อง</p></div>
        <div class="step"><div class="step-icon"><?php echo icon('truck'); ?></div><div class="step-title">รับกระเป๋า</div><p class="hint">จัดส่งให้ถึงก่อนวันเริ่มเช่า</p></div>
        <div class="step"><div class="step-icon"><?php echo icon('box-heart'); ?></div><div class="step-title">ส่งคืน</div><p class="hint">แจ้งเลขพัสดุ รับมัดจำคืน</p></div>
    </section>
<?php endif; ?>

<h2 class="section-title" id="bags"><?php echo icon('sparkle'); ?><?php echo $search === '' ? 'กระเป๋าทั้งหมด' : 'ผลการค้นหา “' . e($search) . '”'; ?><?php echo icon('sparkle'); ?></h2>

<?php if ($products): ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <a class="product-card" href="product_detail.php?product_id=<?php echo urlencode($product['product_id']); ?>">
                <div class="frame"><img src="<?php echo e(product_image_url($product)); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>" loading="lazy"></div>
                <h3><?php echo e($product['brand']); ?></h3>
                <p class="details"><?php echo e($product['details']); ?></p>
                <p class="price"><?php echo number_format($product['daily_rental_price']); ?> บาท <small>/ วัน</small></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card empty">
        <?php echo icon('search'); ?>
        <p>ไม่พบกระเป๋าที่ค้นหาค่ะ</p>
        <a href="index.php" class="btn btn-outline">ดูกระเป๋าทั้งหมด</a>
    </div>
<?php endif; ?>
<?php page_end(); ?>
