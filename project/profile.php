<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

$request_stmt = $conn->prepare("SELECT status FROM membership_requests WHERE customers_id = ? ORDER BY request_id DESC LIMIT 1");
$request_stmt->bind_param("s", $customer_id);
$request_stmt->execute();
$last_request = $request_stmt->get_result()->fetch_assoc();
$request_stmt->close();
$member_pending = !is_member($customer) && ($last_request['status'] ?? '') === 'Pending';

page_start('บัญชีของฉัน', 'shop', 'profile');
?>
<div class="page-narrow">
    <h1>บัญชีของฉัน</h1>
    <?php render_alert(take_flash()); ?>

    <div class="stack">
        <div class="card">
            <div class="page-head" style="margin-bottom: 16px;">
                <h2 style="margin: 0;">คุณ <?php echo e($customer['customers_firstname'] . ' ' . $customer['customers_lastname']); ?></h2>
                <a href="edit_profile.php" class="btn btn-outline btn-sm">แก้ไขข้อมูล</a>
            </div>
            <dl class="info-list">
                <dt>อีเมล</dt><dd><?php echo e($customer['email']); ?></dd>
                <dt>เบอร์โทร</dt><dd><?php echo e($customer['phonenumber']); ?></dd>
                <dt>ที่อยู่จัดส่ง</dt><dd><?php echo e($customer['address'] . ' ' . $customer['postal_code']); ?></dd>
            </dl>
        </div>

        <div class="card">
            <div class="rental-product">
                <span class="step-icon" style="margin: 0; width: 52px; height: 52px;"><?php echo icon('gift'); ?></span>
                <div style="flex: 1;">
                    <?php if (is_member($customer)): ?>
                        <strong>สมาชิก</strong> <span class="badge badge-success">ใช้งานอยู่</span>
                        <div class="hint">เช่าได้โดยไม่ต้องวางมัดจำ<?php echo $customer['member_until'] ? ' ถึง ' . e(thai_date($customer['member_until'])) : ''; ?></div>
                    <?php elseif ($member_pending): ?>
                        <strong>สมาชิก</strong> <span class="badge badge-pending">รอตรวจสลิป</span>
                        <div class="hint">ร้านจะตรวจสลิปและเปิดสิทธิ์ให้เร็ว ๆ นี้ค่ะ</div>
                    <?php else: ?>
                        <strong>สมัครสมาชิก <?php echo number_format(MEMBER_FEE); ?> บาท / ปี</strong>
                        <div class="hint">เช่าได้ทุกใบโดยไม่ต้องวางมัดจำ <?php echo number_format(DEPOSIT_FEE); ?> บาท</div>
                    <?php endif; ?>
                </div>
                <?php if (!is_member($customer) && !$member_pending): ?>
                    <a href="member.php" class="btn btn-sm">สมัคร</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php page_end(); ?>
