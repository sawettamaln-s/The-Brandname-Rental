<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

// สรุปการเช่าที่ยังไม่จบ แสดงบนโปรไฟล์ รายละเอียดอยู่ในหน้าการเช่าของฉัน
$stmt = $conn->prepare("SELECT COUNT(*) AS active, SUM(r.rental_status = 'Shipped' AND r.due_date < CURDATE()) AS overdue
    FROM rentals r JOIN orders o ON o.order_id = r.order_id
    WHERE o.customers_id = ? AND r.rental_status NOT IN ('Completed', 'Deducted', 'Cancelled')");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$request_stmt = $conn->prepare("SELECT status FROM membership_requests WHERE customers_id = ? ORDER BY request_id DESC LIMIT 1");
$request_stmt->bind_param("s", $customer_id);
$request_stmt->execute();
$last_request = $request_stmt->get_result()->fetch_assoc();
$request_stmt->close();
$member_pending = !is_member($customer) && ($last_request['status'] ?? '') === 'Pending';

page_start('โปรไฟล์ของฉัน', 'shop', 'profile');
?>
<h1>โปรไฟล์ของฉัน</h1>
<?php render_alert(take_flash()); ?>

<div class="stack">
    <div class="card">
        <dl class="info-list">
            <dt>รหัสลูกค้า</dt><dd><?php echo e($customer['customers_id']); ?></dd>
            <dt>ชื่อ</dt><dd><?php echo e($customer['customers_firstname'] . ' ' . $customer['customers_lastname']); ?></dd>
            <dt>ประเภทสมาชิก</dt>
            <dd>
                <?php if (is_member($customer)): ?>
                    <span class="badge badge-success">สมาชิก</span><?php echo $customer['member_until'] ? ' ถึง ' . e(thai_date($customer['member_until'])) : ''; ?>
                <?php elseif ($member_pending): ?>
                    ทั่วไป · <span class="badge badge-pending">คำขอสมัครสมาชิกรอร้านตรวจสอบ</span>
                <?php else: ?>
                    ทั่วไป
                <?php endif; ?>
            </dd>
            <dt>อีเมล</dt><dd><?php echo e($customer['email']); ?></dd>
            <dt>เบอร์โทร</dt><dd><?php echo e($customer['phonenumber']); ?></dd>
            <dt>ที่อยู่</dt><dd><?php echo e($customer['address'] . ' ' . $customer['postal_code']); ?></dd>
        </dl>
        <div class="actions">
            <a href="edit_profile.php" class="btn">แก้ไขข้อมูล</a>
            <?php if (!is_member($customer) && !$member_pending): ?>
                <a href="member.php" class="btn btn-outline">สมัครสมาชิก (ไม่ต้องวางมัดจำ)</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>การเช่าของฉัน</h2>
        <p>
            <?php if ((int) $summary['active'] > 0): ?>
                กำลังดำเนินการ <?php echo (int) $summary['active']; ?> ใบ
                <?php if ((int) $summary['overdue'] > 0): ?> · <span class="badge badge-danger">เกินกำหนดคืน <?php echo (int) $summary['overdue']; ?> ใบ</span><?php endif; ?>
            <?php else: ?>
                ไม่มีการเช่าที่กำลังดำเนินการ
            <?php endif; ?>
        </p>
        <a href="rentals.php" class="btn btn-outline">ดูสถานะ แจ้งส่งคืน และประวัติการเช่า</a>
    </div>
</div>
<?php page_end(); ?>
