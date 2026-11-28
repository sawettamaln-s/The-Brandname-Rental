<?php
require_once 'includes/db.php';

$product_id = isset($_GET['product_id']) && is_string($_GET['product_id']) ? $_GET['product_id'] : '';

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    die("ไม่พบข้อมูลสินค้า");
}

$error = '';
$today = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $days = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => MAX_RENTAL_DAYS]]);
    $rental_date = input('rental_date');
    $date = DateTime::createFromFormat('!Y-m-d', $rental_date);

    if ($days === false) {
        $error = "จำนวนวันเช่าต้องอยู่ระหว่าง 1 – " . MAX_RENTAL_DAYS . " วัน";
    } elseif (!$date || $date->format('Y-m-d') !== $rental_date || $rental_date < $today) {
        $error = "กรุณาเลือกวันที่เช่าตั้งแต่วันนี้เป็นต้นไป";
    } elseif ($conflicts = booking_conflicts($conn, $product['product_id'], $rental_date, $days)) {
        $error = conflict_message($conflicts);
    } else {
        // ตะกร้าเก็บเฉพาะรหัสสินค้า จำนวนวัน และวันที่เช่า ราคาดึงจากฐานข้อมูลทุกครั้งที่แสดงและตอนยืนยัน
        // กระเป๋าใบเดียวกันอยู่ในตะกร้าได้รายการเดียว เพิ่มซ้ำจะแทนที่จำนวนวันและวันที่เดิม
        $_SESSION['cart'][$product['product_id']] = [
            'product_id' => $product['product_id'],
            'days' => $days,
            'rental_date' => $rental_date,
        ];
        redirect("cart.php");
    }
}

$booked = booked_ranges($conn, $product['product_id']);

page_start($product['brand'] . ' ' . $product['details'], 'shop', 'home');
?>
<div class="product-detail">
    <div class="photo">
        <img src="<?php echo e(product_image_url($product)); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>">
    </div>
    <div class="card">
        <p class="product-meta">รหัสสินค้า <?php echo e($product['product_id']); ?></p>
        <h1><?php echo e($product['brand']); ?></h1>
        <p><?php echo e($product['details']); ?></p>
        <p class="price"><?php echo baht($product['daily_rental_price']); ?> / วัน</p>
        <p class="muted">ค่ามัดจำ <?php echo number_format(DEPOSIT_FEE); ?> บาทต่อใบ (สมาชิกไม่ต้องวางมัดจำ)</p>

        <div class="booked">
            <span class="field-label">ช่วงที่ไม่ว่าง</span>
            <?php if ($booked): ?>
                <ul>
                    <?php foreach ($booked as $range): ?>
                        <li><?php echo e($range['rental_date']); ?> ถึง <?php echo e($range['busy_until']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">ว่างทุกวัน</p>
            <?php endif; ?>
            <div class="hint">รวมวันพัก <?php echo TURNAROUND_DAYS; ?> วันหลังกำหนดคืน สำหรับขนส่งขากลับและทำความสะอาด</div>
        </div>

        <?php error_alert($error); ?>

        <form method="POST" class="form-grid two-cols">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="quantity">จำนวนวัน</label>
                <input type="number" name="quantity" id="quantity" min="1" max="<?php echo MAX_RENTAL_DAYS; ?>" value="<?php echo e($_POST['quantity'] ?? 1); ?>" required>
            </div>
            <div class="field">
                <label for="rental_date">วันที่เริ่มเช่า</label>
                <input type="date" name="rental_date" id="rental_date" min="<?php echo $today; ?>" value="<?php echo e(input('rental_date')); ?>" required>
            </div>
            <div class="hint full">ส่งคืนภายในวันที่ = วันเริ่มเช่า + จำนวนวัน เช่น เริ่ม 5 ธ.ค. เช่า 3 วัน ส่งคืนภายใน 8 ธ.ค.</div>
            <div class="full actions">
                <button type="submit" class="btn">เพิ่มลงตะกร้า</button>
                <a href="index.php" class="btn btn-outline">ย้อนกลับ</a>
            </div>
        </form>
    </div>
</div>
<?php page_end(); ?>
