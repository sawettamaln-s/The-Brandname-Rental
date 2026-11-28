<?php
require_once '../includes/db.php';
require_admin();

// อนุมัติ: ลูกค้าเป็นสมาชิก 1 ปีนับจากวันนี้, ปฏิเสธ: ลูกค้าส่งสลิปใหม่ได้
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $request_id = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    $approve = isset($_POST['approve']);
    $reject = isset($_POST['reject']);

    if ($request_id !== false && ($approve || $reject)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT customers_id FROM membership_requests WHERE request_id = ? AND status = 'Pending' FOR UPDATE");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$request) {
                $conn->rollback();
                flash("คำขอนี้ถูกตรวจไปแล้ว", "error");
                redirect("members.php");
            }

            $status = $approve ? 'Approved' : 'Rejected';
            $stmt = $conn->prepare("UPDATE membership_requests SET status = ?, reviewed_at = NOW() WHERE request_id = ?");
            $stmt->bind_param("si", $status, $request_id);
            $stmt->execute();
            $stmt->close();

            if ($approve) {
                $until = date('Y-m-d', strtotime('+1 year'));
                $stmt = $conn->prepare("UPDATE customers SET customers_member = 'yes', member_until = ? WHERE customers_id = ?");
                $stmt->bind_param("ss", $until, $request['customers_id']);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            flash($approve ? "อนุมัติสมาชิก {$request['customers_id']} ถึง " . thai_date($until) . " แล้ว" : "ปฏิเสธคำขอของ {$request['customers_id']} แล้ว");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('admin members failed: ' . $e->getMessage());
            flash("บันทึกไม่สำเร็จ กรุณาลองใหม่", "error");
        }
    }
    redirect("members.php");
}

$requests = $conn->query("SELECT m.request_id, m.customers_id, m.created_at, c.customers_firstname, c.customers_lastname, c.email
    FROM membership_requests m JOIN customers c ON c.customers_id = m.customers_id
    WHERE m.status = 'Pending' ORDER BY m.created_at")->fetch_all(MYSQLI_ASSOC);

page_start('อนุมัติสมาชิก', 'admin', 'members');
?>
<h1>คำขอสมัครสมาชิก</h1>
<p class="muted">ค่าสมัคร <?php echo number_format(MEMBER_FEE); ?> บาท · อายุ 1 ปีนับจากวันที่อนุมัติ</p>
<?php render_alert(take_flash()); ?>

<div class="card table-wrap">
    <?php if ($requests): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>วันที่ส่ง</th>
                    <th>ลูกค้า</th>
                    <th>อีเมล</th>
                    <th>สลิป</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $row): ?>
                    <tr>
                        <td><?php echo e(thai_date($row['created_at'])); ?></td>
                        <td><?php echo e($row['customers_id'] . ' ' . $row['customers_firstname'] . ' ' . $row['customers_lastname']); ?></td>
                        <td><?php echo e($row['email']); ?></td>
                        <td><a href="slip.php?id=<?php echo (int) $row['request_id']; ?>" target="_blank" rel="noopener">เปิดดูสลิป</a></td>
                        <td class="text-right" style="white-space: nowrap;">
                            <form method="POST" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>">
                                <button type="submit" name="approve" class="btn btn-success btn-sm">อนุมัติ</button>
                            </form>
                            <form method="POST" class="inline-form" onsubmit="return confirm('ปฏิเสธคำขอนี้?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>">
                                <button type="submit" name="reject" class="btn btn-danger btn-sm">ปฏิเสธ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted">ไม่มีคำขอสมัครสมาชิกที่รอตรวจ</p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
