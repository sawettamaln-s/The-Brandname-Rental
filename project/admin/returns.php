<?php
require_once '../includes/db.php';
require_admin();

// ตรวจสภาพเมื่อได้รับของคืน: สภาพดี (Returned) คืนมัดจำครึ่งหนึ่ง, ชำรุด (Damaged) ร้านยึดมัดจำทั้งหมด
// บันทึกวันที่ร้านได้รับของในตาราง returnss พร้อมกันใน transaction เดียว
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $action = input('action');
    $new_status = $action === 'good' ? 'Returned' : ($action === 'damaged' ? 'Damaged' : null);

    if ($new_status !== null) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE rentals SET rental_status = ? WHERE rental_id = ? AND rental_status = 'Returning'");
            $stmt->bind_param("ss", $new_status, $rental_id);
            $stmt->execute();
            $changed = $stmt->affected_rows > 0;
            $stmt->close();

            if ($changed) {
                $return_id = next_id($conn, 'returnss', 'return_id', 'Ret', 4);
                $stmt = $conn->prepare("INSERT INTO returnss (return_id, rental_id, return_date) VALUES (?, ?, CURDATE())");
                $stmt->bind_param("ss", $return_id, $rental_id);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
            if ($changed) {
                flash($new_status === 'Returned' ? "บันทึก $rental_id: สภาพดี คืนมัดจำครึ่งหนึ่ง" : "บันทึก $rental_id: ชำรุด ยึดมัดจำทั้งหมด");
            } else {
                flash("รายการนี้ไม่อยู่ในสถานะรอตรวจสภาพ", "error");
            }
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('admin returns failed: ' . $e->getMessage());
            flash("บันทึกไม่สำเร็จ กรุณาลองใหม่", "error");
        }
    }
    redirect("returns.php");
}

$returns = $conn->query("SELECT r.rental_id, o.customers_id, r.product_id, p.brand, r.deposit_fee, r.due_date, t.track_return,
    c.customers_firstname, c.customers_lastname
    FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    JOIN customers c ON c.customers_id = o.customers_id
    JOIN track_return t ON t.rental_id = r.rental_id
    LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.rental_status = 'Returning'
    ORDER BY r.due_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);

page_start('การคืนสินค้า', 'admin', 'returns');
?>
<h1>การคืนสินค้าที่รอตรวจ</h1>
<p class="muted">ตรวจสภาพกระเป๋าเมื่อได้รับของ สภาพดีกด "สภาพดี" (คืนมัดจำครึ่งหนึ่ง) ชำรุดกด "ชำรุด" (ยึดมัดจำทั้งหมด) ระบบบันทึกวันนี้เป็นวันที่ได้รับของ</p>
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
                    <th>กำหนดคืน</th>
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
                        <td><?php echo e($return['due_date']); ?></td>
                        <td class="num"><?php echo baht($return['deposit_fee']); ?></td>
                        <td class="text-right" style="white-space: nowrap;">
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($return['rental_id']); ?>">
                                <button type="submit" name="action" value="good" class="btn btn-success btn-sm">สภาพดี</button>
                            </form>
                            <form method="POST" class="inline-form" onsubmit="return confirm('บันทึกว่าสินค้าชำรุดและยึดมัดจำทั้งหมด?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($return['rental_id']); ?>">
                                <button type="submit" name="action" value="damaged" class="btn btn-danger btn-sm">ชำรุด</button>
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
