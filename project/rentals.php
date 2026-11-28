<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

// การเช่าทุกใบของลูกค้า (ลูกค้าเป็นเจ้าของผ่านคำสั่งเช่า orders) พร้อมข้อมูลการคืน
$stmt = $conn->prepare("SELECT r.rental_id, r.order_id, o.created_at, o.cancelled_by, p.product_id, p.brand, p.details, p.image,
    r.track_rentals, r.rental_date, r.rental_days, r.due_date, r.daily_price, r.deposit_fee, r.rental_status,
    t.track_return, rs.return_date, rs.deduction, rs.deduction_note
    FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN products p ON p.product_id = r.product_id
    LEFT JOIN track_return t ON t.rental_id = r.rental_id
    LEFT JOIN returnss rs ON rs.rental_id = r.rental_id
    WHERE o.customers_id = ? ORDER BY o.created_at DESC, r.rental_id");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// จัดกลุ่มตามคำสั่ง แล้วแยกคำสั่งที่ยังไม่จบออกจากประวัติ
const FINAL_STATUSES = ['Completed', 'Deducted', 'Cancelled'];
$orders = [];
foreach ($rows as $row) {
    $orders[$row['order_id']][] = $row;
}
$active = [];
$history = [];
foreach ($orders as $order_id => $items) {
    $done = !array_filter($items, fn($i) => !in_array($i['rental_status'], FINAL_STATUSES, true));
    if ($done) {
        $history[$order_id] = $items;
    } else {
        $active[$order_id] = $items;
    }
}

/** การ์ดหนึ่งคำสั่ง: หัวการ์ดบอกยอดที่โอน รายการกระเป๋าแต่ละใบมีสถานะ คำอธิบาย และปุ่มที่ทำได้ในขั้นนั้น */
function order_card(string $order_id, array $items): void
{
    $first = $items[0];
    $total = array_sum(array_map(fn($i) => $i['daily_price'] * $i['rental_days'] + $i['deposit_fee'], $items));
    $cancellable = !array_filter($items, fn($i) => $i['rental_status'] !== 'Pending');
    ?>
    <div class="card order-card">
        <div class="order-head">
            <div>
                <h3>คำสั่ง <?php echo e($order_id); ?></h3>
                <div class="hint"><?php echo e(thai_date($first['created_at'])); ?> · <?php echo count($items); ?> ใบ · ยอดโอน <?php echo baht($total); ?></div>
            </div>
            <?php if ($cancellable): ?>
                <form action="cancel_order.php" method="POST" class="inline-form" onsubmit="return confirm('ต้องการยกเลิกคำสั่ง <?php echo e($order_id); ?> ใช่ไหมคะ');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo e($order_id); ?>">
                    <button type="submit" class="btn btn-outline btn-sm">ยกเลิกคำสั่ง</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>กระเป๋า</th>
                        <th>ช่วงเช่า</th>
                        <th class="num">ค่าเช่า</th>
                        <th class="num">มัดจำ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="rental-product">
                                    <img src="<?php echo e(product_image_url($item)); ?>" alt="" loading="lazy">
                                    <div>
                                        <?php echo e($item['brand'] . ' ' . $item['details']); ?>
                                        <div class="hint"><?php echo e($item['rental_id']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="nowrap">
                                <?php echo e(thai_date($item['rental_date'])); ?> · <?php echo e($item['rental_days']); ?> วัน
                                <div class="hint">คืนภายใน <?php echo e(thai_date($item['due_date'])); ?></div>
                            </td>
                            <td class="num"><?php echo baht($item['daily_price'] * $item['rental_days']); ?></td>
                            <td class="num">
                                <?php echo baht($item['deposit_fee']); ?>
                                <?php $refund = deposit_refund($item); if ($refund !== null && $item['deposit_fee'] > 0): ?>
                                    <div class="hint">ได้คืน <?php echo baht($refund); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="status-cell">
                                <?php echo status_badge($item); ?>
                                <?php if ($hint = status_hint($item)): ?><div class="hint"><?php echo e($hint); ?></div><?php endif; ?>
                                <?php if ($item['rental_status'] === 'Shipped'): ?>
                                    <form action="process_return.php" method="POST" class="return-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="rental_id" value="<?php echo e($item['rental_id']); ?>">
                                        <input type="text" name="track_return" placeholder="เลขพัสดุส่งคืน" maxlength="50" pattern="[A-Za-z0-9&\-]{8,50}" title="ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว" aria-label="เลขพัสดุส่งคืน <?php echo e($item['rental_id']); ?>" required>
                                        <button type="submit" class="btn btn-sm"><?php echo icon('box-heart'); ?>แจ้งส่งคืน</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

page_start('การเช่าของฉัน', 'shop', 'rentals');
?>
<h1>การเช่าของฉัน</h1>
<?php render_alert(take_flash()); ?>

<?php if (!$orders): ?>
    <div class="card empty">
        <?php echo icon('bag'); ?>
        <p>คุณยังไม่มีการเช่าค่ะ</p>
        <a href="index.php" class="btn">เลือกกระเป๋า</a>
    </div>
<?php endif; ?>

<?php if ($active): ?>
    <h2 id="return-form">กำลังดำเนินการ</h2>
    <div class="stack">
        <?php foreach ($active as $order_id => $items) { order_card($order_id, $items); } ?>
    </div>
<?php endif; ?>

<?php if ($history): ?>
    <h2 style="margin-top: 32px;">ประวัติ</h2>
    <div class="stack">
        <?php foreach ($history as $order_id => $items) { order_card($order_id, $items); } ?>
    </div>
<?php endif; ?>
<?php page_end(); ?>
