<?php
require_once '../includes/db.php';
require_admin();

// สภาพดี (Complete) = คืนมัดจำครึ่งหนึ่ง, ชำรุด (Cancel) = ร้านยึดมัดจำทั้งหมด
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $new_status = isset($_POST['confirm']) ? 'Complete' : (isset($_POST['cancel']) ? 'Cancel' : null);

    if ($new_status !== null) {
        $update_stmt = $conn->prepare("UPDATE track_return SET returnl_status = ? WHERE rental_id = ? AND returnl_status = 'Pending'");
        $update_stmt->bind_param("ss", $new_status, $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            flash($new_status === 'Complete' ? "บันทึก $rental_id: สภาพดี คืนมัดจำครึ่งหนึ่ง" : "บันทึก $rental_id: ชำรุด ยึดมัดจำทั้งหมด");
        } else {
            flash("ไม่สามารถเปลี่ยนสถานะได้ หรือรายการไม่อยู่ในสถานะรอตรวจ", "error");
        }
        $update_stmt->close();
    }
    redirect("returns.php");
}

$stmt = $conn->prepare("SELECT t.rental_id, r.customers_id, r.product_id, p.brand, r.deposit_fee, t.track_return, rs.return_date,
    c.customers_firstname, c.customers_lastname
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    JOIN customers c ON r.customers_id = c.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id
    LEFT JOIN returnss rs ON rs.rental_id = t.rental_id
    WHERE t.returnl_status = 'Pending'
    ORDER BY t.rental_id");
$stmt->execute();
$returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_start('การคืนสินค้า', 'admin', 'returns');
?>
<h1>การคืนสินค้าที่รอตรวจ</h1>
<p class="muted">ตรวจสภาพกระเป๋าเมื่อได้รับของ สภาพดีกด "สภาพดี" (คืนมัดจำครึ่งหนึ่ง) ชำรุดกด "ชำรุด" (ยึดมัดจำทั้งหมด)</p>
<?php render_alert(take_flash()); ?>

<div class="card table-wrap">
    <?php if ($returns): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>รหัสการเช่า</th>
                    <th>ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>เลขพัสดุที่ส่งคืน</th>
                    <th>วันที่แจ้งส่งคืน</th>
                    <th class="num">ค่ามัดจำ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $return): ?>
                    <tr>
                        <td><?php echo e($return['rental_id']); ?></td>
                        <td><?php echo e($return['customers_id'] . ' ' . $return['customers_firstname'] . ' ' . $return['customers_lastname']); ?></td>
                        <td><?php echo e($return['product_id'] . ' ' . $return['brand']); ?></td>
                        <td><?php echo e($return['track_return']); ?></td>
                        <td><?php echo e($return['return_date'] ?? '-'); ?></td>
                        <td class="num"><?php echo baht($return['deposit_fee']); ?></td>
                        <td class="text-right" style="white-space: nowrap;">
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($return['rental_id']); ?>">
                                <button type="submit" name="confirm" class="btn btn-success btn-sm">สภาพดี</button>
                            </form>
                            <form method="POST" class="inline-form" onsubmit="return confirm('บันทึกว่าสินค้าชำรุดและยึดมัดจำทั้งหมด?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($return['rental_id']); ?>">
                                <button type="submit" name="cancel" class="btn btn-danger btn-sm">ชำรุด</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted">ไม่มีการคืนสินค้าที่รอตรวจ</p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
