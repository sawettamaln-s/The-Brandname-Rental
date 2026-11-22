<?php
require_once 'includes/db.php';

$customer_id = require_customer();
$user = find_customer($conn, $customer_id);
if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}

function has_pending_request(mysqli $conn, string $customer_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM membership_requests WHERE customers_id = ? AND status = 'Pending'");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $pending = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $pending;
}

$already_member = is_member($user);
$pending = has_pending_request($conn, $customer_id);
$upload_error = '';

// ลูกค้าแนบสลิปแล้วระบบสร้างคำขอรออนุมัติ สถานะสมาชิกเปลี่ยนเมื่อแอดมินตรวจสลิปและอนุมัติเท่านั้น
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_member && !$pending) {
    verify_csrf();
    $saved = save_image($_FILES['payment_slip'] ?? null, SLIP_DIR);
    if (isset($saved['error'])) {
        $upload_error = $saved['error'];
    } else {
        $stmt = $conn->prepare("INSERT INTO membership_requests (customers_id, slip_file) VALUES (?, ?)");
        $stmt->bind_param("ss", $customer_id, $saved['file']);
        $stmt->execute();
        $stmt->close();
        flash("ส่งสลิปสมัครสมาชิกแล้ว ร้านจะตรวจสอบและอนุมัติให้ค่ะ");
        redirect("profile.php");
    }
}

page_start('สมัครสมาชิก', 'shop', 'profile');
?>
<div class="page-narrow">
    <div class="card payment">
        <h1>สมัครสมาชิก 1 ปี</h1>
        <p>ค่าสมัคร <?php echo number_format(MEMBER_FEE); ?> บาท · สมาชิกเช่ากระเป๋าได้โดยไม่ต้องวางมัดจำ (ปกติ <?php echo number_format(DEPOSIT_FEE); ?> บาทต่อใบ)</p>
        <p class="muted">อายุสมาชิก 1 ปีนับจากวันที่ร้านอนุมัติ</p>

        <?php if ($already_member): ?>
            <div class="alert alert-success">คุณเป็นสมาชิกอยู่แล้ว<?php echo $user['member_until'] ? ' (ถึง ' . e($user['member_until']) . ')' : ''; ?></div>
        <?php elseif ($pending): ?>
            <div class="alert alert-info">ส่งสลิปแล้ว รอร้านตรวจสอบและอนุมัติค่ะ</div>
        <?php else: ?>
            <img src="assets/images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
            <div class="total">ยอดชำระ <?php echo baht(MEMBER_FEE); ?></div>
            <?php error_alert($upload_error); ?>
            <form action="member.php" method="post" enctype="multipart/form-data" class="form-grid" style="text-align: left;">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="payment_slip">แนบสลิปการโอน</label>
                    <input type="file" id="payment_slip" name="payment_slip" accept="image/jpeg,image/png,image/webp" required>
                    <div class="hint">ไฟล์ JPG, PNG หรือ WEBP ไม่เกิน 2 MB</div>
                </div>
                <button type="submit" class="btn btn-success btn-block">ส่งสลิปเพื่อสมัครสมาชิก</button>
            </form>
        <?php endif; ?>
        <div class="actions" style="justify-content: center;">
            <a href="profile.php" class="btn btn-outline">กลับไปโปรไฟล์</a>
        </div>
    </div>
</div>
<?php page_end(); ?>
