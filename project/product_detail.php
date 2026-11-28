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
    die("ไม่พบกระเป๋าใบนี้ค่ะ");
}

$error = '';
$today = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $days = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => MAX_RENTAL_DAYS]]);
    $rental_date = input('rental_date');
    $date = DateTime::createFromFormat('!Y-m-d', $rental_date);

    if ($days === false) {
        $error = "เช่าได้ 1–" . MAX_RENTAL_DAYS . " วันค่ะ";
    } elseif (!$date || $date->format('Y-m-d') !== $rental_date || $rental_date < $today) {
        $error = "กรุณาเลือกวันเริ่มเช่าตั้งแต่วันนี้เป็นต้นไปค่ะ";
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

// มัดจำของผู้ที่กำลังดู: ลูกค้าที่ล็อกอินคิดตามสถานะสมาชิก ผู้ที่ยังไม่ล็อกอินแสดงมัดจำของลูกค้าทั่วไป
$viewer = !empty($_SESSION['customers_id']) ? find_customer($conn, $_SESSION['customers_id']) : null;
$deposit = $viewer ? deposit_for($viewer) : DEPOSIT_FEE;
$deposit_note = $viewer && is_member($viewer) ? 'คุณเป็นสมาชิก ไม่ต้องวางมัดจำค่ะ' : 'ได้รับมัดจำคืนเต็มจำนวนเมื่อคืนกระเป๋าในสภาพดีค่ะ';

page_start($product['brand'] . ' ' . $product['details'], 'shop', 'home');
?>
<div class="product-detail">
    <div class="photo">
        <div class="frame"><img src="<?php echo e(product_image_url($product)); ?>" alt="<?php echo e($product['brand'] . ' ' . $product['details']); ?>"></div>
    </div>
    <div class="card">
        <h1><?php echo e($product['brand']); ?></h1>
        <p><?php echo e($product['details']); ?></p>
        <p class="price"><?php echo number_format($product['daily_rental_price']); ?> บาท <small>/ วัน</small></p>

        <div class="booked">
            <span class="field-label"><?php echo icon('calendar'); ?>ช่วงที่ไม่ว่าง</span>
            <?php if ($booked): ?>
                <ul>
                    <?php foreach ($booked as $range): ?>
                        <li><?php echo e(thai_date($range['rental_date'])); ?> ถึง <?php echo e(thai_date($range['busy_until'])); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">ว่างทุกวันค่ะ</p>
            <?php endif; ?>
            <div class="hint">รวมวันขนส่งและทำความสะอาดหลังคืน <?php echo TURNAROUND_DAYS; ?> วัน</div>
        </div>

        <?php error_alert($error); ?>

        <form method="POST" class="form-grid two-cols">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="quantity">จำนวนวัน</label>
                <input type="number" name="quantity" id="quantity" min="1" max="<?php echo MAX_RENTAL_DAYS; ?>" value="<?php echo e(input('quantity') ?: 1); ?>" required>
            </div>
            <div class="field">
                <label for="rental_date">วันที่เริ่มเช่า</label>
                <input type="date" name="rental_date" id="rental_date" min="<?php echo $today; ?>" value="<?php echo e(input('rental_date')); ?>" required>
            </div>
            <div class="full quote" id="quote" data-price="<?php echo (int) $product['daily_rental_price']; ?>" data-deposit="<?php echo $deposit; ?>"
                 data-turnaround="<?php echo TURNAROUND_DAYS; ?>" data-booked="<?php echo e(json_encode(array_map(fn($r) => [$r['rental_date'], $r['busy_until']], $booked))); ?>">
                <div class="quote-row"><span>ส่งคืนภายใน</span><strong id="quote-due">เลือกวันที่เริ่มเช่า</strong></div>
                <div class="quote-row"><span>ค่าเช่า <span id="quote-days">1</span> วัน</span><span id="quote-rent"><?php echo baht($product['daily_rental_price']); ?></span></div>
                <div class="quote-row"><span>ค่ามัดจำ</span><span><?php echo baht($deposit); ?></span></div>
                <div class="quote-row quote-total"><span>รวมที่ต้องโอน</span><strong id="quote-total"><?php echo baht($product['daily_rental_price'] + $deposit); ?></strong></div>
                <div class="hint"><?php echo e($deposit_note); ?></div>
                <div class="problem" id="quote-conflict" hidden>ช่วงนี้กระเป๋าไม่ว่าง กรุณาเลือกวันอื่นนะคะ</div>
            </div>
            <div class="full actions">
                <button type="submit" class="btn"><?php echo icon('cart'); ?>เพิ่มลงตะกร้า</button>
                <a href="index.php" class="btn btn-outline">ย้อนกลับ</a>
            </div>
        </form>
    </div>
</div>
<script>
// คำนวณวันคืนและยอดรวมทันทีที่เปลี่ยนวันหรือจำนวนวัน และเตือนถ้าช่วงที่เลือกทับช่วงที่ไม่ว่าง (เซิร์ฟเวอร์ตรวจซ้ำตอนเพิ่มลงตะกร้า)
(function () {
    const quote = document.getElementById('quote');
    const daysInput = document.getElementById('quantity');
    const dateInput = document.getElementById('rental_date');
    const price = Number(quote.dataset.price);
    const deposit = Number(quote.dataset.deposit);
    const turnaround = Number(quote.dataset.turnaround);
    const booked = JSON.parse(quote.dataset.booked);
    const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const money = (n) => n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
    const parse = (iso) => { const [y, m, d] = iso.split('-').map(Number); return new Date(Date.UTC(y, m - 1, d)); };
    const addDays = (date, n) => new Date(date.getTime() + n * 86400000);
    const thai = (date) => date.getUTCDate() + ' ' + months[date.getUTCMonth()] + ' ' + (date.getUTCFullYear() + 543);

    function update() {
        const days = Math.min(Math.max(parseInt(daysInput.value, 10) || 0, 0), <?php echo MAX_RENTAL_DAYS; ?>);
        document.getElementById('quote-days').textContent = days;
        document.getElementById('quote-rent').textContent = money(price * days);
        document.getElementById('quote-total').textContent = money(price * days + deposit);
        let conflict = false;
        if (dateInput.value && days > 0) {
            const start = parse(dateInput.value);
            document.getElementById('quote-due').textContent = thai(addDays(start, days));
            const end = addDays(start, days + turnaround);  // ไม่รวมวันนี้ แบบเดียวกับ booking_conflicts()
            conflict = booked.some(([from, until]) => parse(from) < end && start < addDays(parse(until), 1));
        } else {
            document.getElementById('quote-due').textContent = 'เลือกวันที่เริ่มเช่า';
        }
        document.getElementById('quote-conflict').hidden = !conflict;
    }
    daysInput.addEventListener('input', update);
    dateInput.addEventListener('input', update);
    update();
})();
</script>
<?php page_end(); ?>
