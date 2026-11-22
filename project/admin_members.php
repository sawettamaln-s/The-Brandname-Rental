<?php
require_once 'db_connect.php';
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
                flash("คำขอนี้ถูกตรวจไปแล้ว");
                redirect("admin_members.php");
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
            flash($approve ? "อนุมัติสมาชิก {$request['customers_id']} ถึง $until แล้ว" : "ปฏิเสธคำขอของ {$request['customers_id']} แล้ว");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('admin_members failed: ' . $e->getMessage());
            flash("บันทึกไม่สำเร็จ กรุณาลองใหม่");
        }
    }
    redirect("admin_members.php");
}

$result = $conn->query("SELECT m.request_id, m.customers_id, m.created_at, c.customers_firstname, c.customers_lastname, c.email
    FROM membership_requests m JOIN customers c ON c.customers_id = m.customers_id
    WHERE m.status = 'Pending' ORDER BY m.created_at");

$message = take_flash();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติสมาชิก</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>

<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>

<main>
    <div class="container">
        <h2>อนุมัติสมาชิก</h2>
        <p>เปิดดูสลิปและตรวจยอดโอน <?php echo number_format(MEMBER_FEE); ?> บาท ก่อนกดอนุมัติ สมาชิกมีอายุ 1 ปีนับจากวันที่อนุมัติ</p>

        <?php if ($message): ?>
            <div class="alert"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>วันที่ส่ง</th>
                    <th>ลูกค้า</th>
                    <th>อีเมล</th>
                    <th>สลิป</th>
                    <th>จัดการ</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row['created_at']); ?></td>
                        <td><?php echo e($row['customers_id'] . ' ' . $row['customers_firstname'] . ' ' . $row['customers_lastname']); ?></td>
                        <td><?php echo e($row['email']); ?></td>
                        <td><a href="admin_slip.php?id=<?php echo (int) $row['request_id']; ?>" target="_blank" rel="noopener">เปิดดูสลิป</a></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>">
                                <button type="submit" name="approve" class="btn btn-confirm">อนุมัติ</button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('ปฏิเสธคำขอนี้?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>">
                                <button type="submit" name="reject" class="btn btn-cancel">ปฏิเสธ</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>ไม่มีคำขอสมัครสมาชิกที่รอตรวจ</p>
        <?php endif; ?>

        <a href="index_admin.php" class="btn btn-back">กลับหน้าแรก</a>
    </div>
</main>

</body>
</html>
