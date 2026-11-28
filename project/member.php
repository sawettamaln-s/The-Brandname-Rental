<?php
require_once 'includes/db.php';

$user = current_customer($conn);
$customer_id = $user['customers_id'];

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
        flash("ได้รับสลิปแล้ว ร้านจะตรวจและเปิดสิทธิ์สมาชิกให้เร็ว ๆ นี้ค่ะ");
        redirect("profile.php");
    }
}

page_start('สมัครสมาชิก', 'shop', 'profile');
?>
<div class="page-narrow">
    <div class="card payment">
        <div class="auth-kicker">Member</div>
        <h1>สมัครสมาชิก</h1>
        <p class="muted">เช่าได้ทุกใบโดยไม่ต้องวางมัดจำ <?php echo number_format(DEPOSIT_FEE); ?> บาท · อายุ 1 ปีนับจากวันที่ร้านอนุมัติ</p>

        <?php if ($already_member): ?>
            <div class="alert alert-success">คุณเป็นสมาชิกอยู่แล้วค่ะ<?php echo $user['member_until'] ? ' (ถึง ' . e(thai_date($user['member_until'])) . ')' : ''; ?></div>
        <?php elseif ($pending): ?>
            <div class="alert alert-info">ได้รับสลิปแล้ว ร้านจะตรวจและเปิดสิทธิ์ให้เร็ว ๆ นี้ค่ะ</div>
        <?php else: ?>
            <img src="assets/images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
            <div class="total" style="margin-bottom: 16px;"><?php echo baht(MEMBER_FEE); ?></div>
            <?php error_alert($upload_error); ?>
            <form action="member.php" method="post" enctype="multipart/form-data" class="form-grid" style="text-align: left;">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="payment_slip">แนบสลิปการโอน</label>
                    <input type="file" id="payment_slip" name="payment_slip" accept="image/jpeg,image/png,image/webp" required>
                    <div class="hint">JPG, PNG หรือ WEBP ไม่เกิน 2 MB</div>
                </div>
                <button type="submit" class="btn btn-block">ส่งสลิป</button>
            </form>
        <?php endif; ?>
        <div class="actions" style="justify-content: center;">
            <a href="profile.php" class="btn btn-outline">กลับไปบัญชีของฉัน</a>
        </div>
    </div>
</div>
<?php page_end(); ?>
