<?php
require_once '../includes/db.php';
require_admin();

// การคืนของมี 2 ขั้น:
// 1) ของถึงร้าน: Returning → Received และบันทึกวันที่ได้รับของในตาราง returnss
// 2) ตรวจสภาพ: Received → Completed (คืนมัดจำเต็ม) หรือ Deducted (หักมัดจำตามยอดที่กรอก พร้อมหมายเหตุ)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $action = input('action');

    if ($action === 'receive') {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Received' WHERE rental_id = ? AND rental_status = 'Returning'");
            $stmt->bind_param("s", $rental_id);
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
            flash($changed ? "บันทึกว่าได้รับ $rental_id แล้ว ตรวจสภาพได้ที่ส่วนที่ 2" : "รายการนี้ไม่อยู่ในสถานะกำลังส่งคืน", $changed ? 'success' : 'error');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('admin returns receive failed: ' . $e->getMessage());
            flash("บันทึกไม่สำเร็จ กรุณาลองใหม่", "error");
        }
    } elseif ($action === 'complete' || $action === 'deduct') {
        $stmt = $conn->prepare("SELECT deposit_fee FROM rentals WHERE rental_id = ? AND rental_status = 'Received'");
        $stmt->bind_param("s", $rental_id);
        $stmt->execute();
        $rental = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $note = input('deduction_note');
        $deduction = $action === 'complete' ? 0 : filter_var($_POST['deduction'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$rental) {
            flash("รายการนี้ไม่อยู่ในสถานะรอตรวจสภาพ", "error");
        } elseif ($deduction === false || $deduction > (int) $rental['deposit_fee']) {
            flash("ยอดหักต้องเป็นจำนวนเต็มตั้งแต่ 1 ถึง " . number_format($rental['deposit_fee']) . " บาท (ไม่เกินค่ามัดจำ)", "error");
        } elseif ($action === 'deduct' && $note === '') {
            flash("กรุณาระบุหมายเหตุว่าหักค่าอะไร ลูกค้าจะเห็นข้อความนี้", "error");
        } elseif (mb_strlen($note) > 255) {
            flash("หมายเหตุยาวเกิน 255 ตัวอักษร", "error");
        } else {
            $status = $deduction > 0 ? 'Deducted' : 'Completed';
            $note_value = $note === '' ? null : $note;
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE returnss SET deduction = ?, deduction_note = ? WHERE rental_id = ?");
                $stmt->bind_param("iss", $deduction, $note_value, $rental_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE rentals SET rental_status = ? WHERE rental_id = ? AND rental_status = 'Received'");
                $stmt->bind_param("ss", $status, $rental_id);
                $stmt->execute();
                $stmt->close();
                $conn->commit();

                $refund = (int) $rental['deposit_fee'] - $deduction;
                flash($deduction > 0
                    ? "บันทึก $rental_id: หักมัดจำ " . baht($deduction) . " คืนลูกค้า " . baht($refund)
                    : "บันทึก $rental_id: เสร็จสมบูรณ์ คืนมัดจำเต็ม " . baht($refund));
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                error_log('admin returns inspect failed: ' . $e->getMessage());
                flash("บันทึกไม่สำเร็จ กรุณาลองใหม่", "error");
            }
        }
    }
    redirect("returns.php");
}

$columns = "r.rental_id, o.customers_id, r.product_id, p.brand, r.deposit_fee, r.due_date, t.track_return, rs.return_date,
    c.customers_firstname, c.customers_lastname";
$from = "FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    JOIN customers c ON c.customers_id = o.customers_id
    JOIN track_return t ON t.rental_id = r.rental_id
    LEFT JOIN returnss rs ON rs.rental_id = r.rental_id
    LEFT JOIN products p ON p.product_id = r.product_id";
$incoming = $conn->query("SELECT $columns $from WHERE r.rental_status = 'Returning' ORDER BY r.due_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);
$to_inspect = $conn->query("SELECT $columns $from WHERE r.rental_status = 'Received' ORDER BY rs.return_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);
$customer_name = fn(array $row) => $row['customers_id'] . ' ' . $row['customers_firstname'] . ' ' . $row['customers_lastname'];

page_start('การคืนสินค้า', 'admin', 'returns');
?>
<h1>การคืนสินค้า</h1>
<?php render_alert(take_flash()); ?>

<div class="stack">
    <section class="card">
        <h2>1. กำลังส่งคืน (<?php echo count($incoming); ?>)</h2>
        <p class="muted">ลูกค้าแจ้งเลขพัสดุส่งคืนแล้ว เมื่อของถึงร้านให้กด "ได้รับของแล้ว" ระบบบันทึกวันนี้เป็นวันที่ได้รับของ</p>
        <?php if ($incoming): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>ลูกค้า</th>
                            <th>สินค้า</th>
                            <th>เลขพัสดุที่ส่งคืน</th>
                            <th>กำหนดคืน</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incoming as $row): ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?></td>
                                <td><?php echo e($customer_name($row)); ?></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td><?php echo e($row['track_return']); ?></td>
                                <td><?php echo e($row['due_date']); ?></td>
                                <td class="text-right">
                                    <form method="POST" class="inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="rental_id" value="<?php echo e($row['rental_id']); ?>">
                                        <button type="submit" name="action" value="receive" class="btn btn-success btn-sm">ได้รับของแล้ว</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ไม่มีกระเป๋าที่กำลังส่งคืน</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>2. ตรวจสภาพ (<?php echo count($to_inspect); ?>)</h2>
        <p class="muted">สภาพดีกด "คืนมัดจำเต็ม" ถ้าเสียหาย กรอกยอดที่หัก (ไม่เกินค่ามัดจำ) และหมายเหตุว่าหักค่าอะไร แล้วกด "หักมัดจำ" ลูกค้าจะเห็นยอดและหมายเหตุในหน้าโปรไฟล์</p>
        <?php if ($to_inspect): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>ลูกค้า</th>
                            <th>สินค้า</th>
                            <th>วันที่ได้รับ</th>
                            <th class="num">ค่ามัดจำ</th>
                            <th>ผลการตรวจ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($to_inspect as $row): $deposit = (int) $row['deposit_fee']; ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?></td>
                                <td><?php echo e($customer_name($row)); ?></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td class="nowrap"><?php echo e($row['return_date']); ?></td>
                                <td class="num"><?php echo baht($deposit); ?></td>
                                <td>
                                    <form method="POST" class="inspect-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="rental_id" value="<?php echo e($row['rental_id']); ?>">
                                        <?php if ($deposit > 0): ?>
                                            <input type="number" name="deduction" min="1" max="<?php echo $deposit; ?>" step="1" placeholder="ยอดหัก บาท" aria-label="ยอดที่หักจากมัดจำ">
                                            <input type="text" name="deduction_note" maxlength="255" placeholder="หมายเหตุ เช่น สายขาด" aria-label="หมายเหตุการหักมัดจำ">
                                        <?php else: ?>
                                            <input type="text" name="deduction_note" maxlength="255" placeholder="หมายเหตุ (ถ้ามี)" aria-label="หมายเหตุถึงลูกค้า">
                                        <?php endif; ?>
                                        <div class="inspect-actions">
                                            <?php if ($deposit > 0): ?>
                                                <button type="submit" name="action" value="deduct" class="btn btn-danger btn-sm">หักมัดจำ</button>
                                            <?php endif; ?>
                                            <button type="submit" name="action" value="complete" class="btn btn-success btn-sm"><?php echo $deposit > 0 ? 'คืนมัดจำเต็ม' : 'เสร็จสมบูรณ์'; ?></button>
                                        </div>
                                    </form>
                                    <?php if ($deposit === 0): ?>
                                        <div class="hint text-right">ลูกค้าสมาชิกไม่มีมัดจำ ถ้าเสียหายให้เขียนหมายเหตุแล้วติดต่อลูกค้าเรียกเก็บแยก</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ไม่มีกระเป๋าที่รอตรวจสภาพ</p>
        <?php endif; ?>
    </section>
</div>
<?php page_end(); ?>
