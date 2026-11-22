<?php
require_once '../includes/db.php';
require_admin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $new_status = isset($_POST['confirm']) ? 'Complete' : (isset($_POST['cancel']) ? 'Cancel' : null);

    if ($new_status !== null) {
        $update_stmt = $conn->prepare("UPDATE rentals SET rental_status = ? WHERE rental_id = ? AND rental_status = 'Pending'");
        $update_stmt->bind_param("ss", $new_status, $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            flash($new_status === 'Complete' ? "ยืนยันการเช่า $rental_id เรียบร้อยแล้ว" : "ยกเลิกการเช่า $rental_id เรียบร้อยแล้ว");
        } else {
            flash("ไม่สามารถเปลี่ยนสถานะได้ หรือคำสั่งเช่าไม่อยู่ในสถานะรอยืนยัน", "error");
        }
        $update_stmt->close();
    }
    redirect("orders.php");
}

$stmt = $conn->prepare("SELECT r.rental_id, r.customers_id, c.customers_firstname, c.customers_lastname,
    r.product_id, p.brand, p.daily_rental_price, r.rental_date, r.rental_days, r.deposit_fee
    FROM rentals r
    JOIN customers c ON c.customers_id = r.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.rental_status = 'Pending'
    ORDER BY r.rental_id");
$stmt->execute();
$rentals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_start('คำสั่งเช่า', 'admin', 'orders');
?>
<h1>คำสั่งเช่าที่รอยืนยัน</h1>
<p class="muted">ตรวจยอดโอนของลูกค้าก่อนกดยืนยัน ยอดที่ต้องได้รับ = ค่าเช่า + ค่ามัดจำ</p>
<?php render_alert(take_flash()); ?>

<div class="card table-wrap">
    <?php if ($rentals): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>รหัสการเช่า</th>
                    <th>ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>วันที่เช่า</th>
                    <th class="num">จำนวนวัน</th>
                    <th class="num">ค่าเช่า</th>
                    <th class="num">ค่ามัดจำ</th>
                    <th class="num">ยอดรวม</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $rental):
                    $rent = (int) $rental['daily_rental_price'] * (int) $rental['rental_days'];
                ?>
                    <tr>
                        <td><?php echo e($rental['rental_id']); ?></td>
                        <td><?php echo e($rental['customers_id'] . ' ' . $rental['customers_firstname'] . ' ' . $rental['customers_lastname']); ?></td>
                        <td><?php echo e($rental['product_id'] . ' ' . $rental['brand']); ?></td>
                        <td><?php echo e($rental['rental_date']); ?></td>
                        <td class="num"><?php echo e($rental['rental_days']); ?></td>
                        <td class="num"><?php echo number_format($rent); ?></td>
                        <td class="num"><?php echo number_format((int) $rental['deposit_fee']); ?></td>
                        <td class="num"><strong><?php echo baht($rent + (int) $rental['deposit_fee']); ?></strong></td>
                        <td class="text-right" style="white-space: nowrap;">
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="confirm" class="btn btn-success btn-sm">ยืนยัน</button>
                            </form>
                            <form method="POST" class="inline-form" onsubmit="return confirm('ยกเลิกคำสั่งเช่านี้?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="cancel" class="btn btn-danger btn-sm">ยกเลิก</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted">ไม่มีคำสั่งเช่าที่รอการยืนยัน</p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
