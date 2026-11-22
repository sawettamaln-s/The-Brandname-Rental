<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

$rental_stmt = $conn->prepare("SELECT r.rental_id, r.product_id, p.brand, p.details, r.employees_id, r.track_rentals,
    r.rental_date, r.rental_days, r.deposit_fee, r.rental_status
    FROM rentals r LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.customers_id = ? ORDER BY r.rental_id DESC");
$rental_stmt->bind_param("s", $customer_id);
$rental_stmt->execute();
$rentals = $rental_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rental_stmt->close();

$return_stmt = $conn->prepare("
    SELECT t.rental_id, t.track_return, t.returnl_status, rs.return_date, r.deposit_fee
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    LEFT JOIN returnss rs ON t.rental_id = rs.rental_id
    WHERE r.customers_id = ? ORDER BY t.rental_id DESC
");
$return_stmt->bind_param("s", $customer_id);
$return_stmt->execute();
$returns = $return_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$return_stmt->close();

// รายการที่ส่งคืนได้: ร้านยืนยันการเช่าแล้ว และยังไม่เคยแจ้งเลขพัสดุคืน
$returnable_stmt = $conn->prepare("
    SELECT r.rental_id, p.brand, p.details FROM rentals r
    LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.customers_id = ? AND r.rental_status = 'Complete'
      AND NOT EXISTS (SELECT 1 FROM track_return t WHERE t.rental_id = r.rental_id)
    ORDER BY r.rental_id
");
$returnable_stmt->bind_param("s", $customer_id);
$returnable_stmt->execute();
$returnable = $returnable_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$returnable_stmt->close();

$request_stmt = $conn->prepare("SELECT status FROM membership_requests WHERE customers_id = ? ORDER BY request_id DESC LIMIT 1");
$request_stmt->bind_param("s", $customer_id);
$request_stmt->execute();
$last_request = $request_stmt->get_result()->fetch_assoc();
$request_stmt->close();
$member_pending = !is_member($customer) && ($last_request['status'] ?? '') === 'Pending';

$rental_badges = ['Pending' => ['รอร้านยืนยัน', 'pending'], 'Complete' => ['ยืนยันแล้ว', 'success'], 'Cancel' => ['ยกเลิก', 'danger']];
$return_badges = ['Pending' => ['รอร้านตรวจสภาพ', 'pending'], 'Complete' => ['สภาพดี', 'success'], 'Cancel' => ['ชำรุด', 'danger']];
$badge = fn(array $map, string $status) => '<span class="badge badge-' . $map[$status][1] . '">' . $map[$status][0] . '</span>';

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
                    <span class="badge badge-success">สมาชิก</span><?php echo $customer['member_until'] ? ' ถึง ' . e($customer['member_until']) : ''; ?>
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
        <?php if ($rentals): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>สินค้า</th>
                            <th>วันที่เช่า</th>
                            <th class="num">จำนวนวัน</th>
                            <th class="num">ค่ามัดจำ</th>
                            <th>เลขพัสดุจัดส่ง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><?php echo e($rental['rental_id']); ?></td>
                                <td><?php echo e($rental['brand'] . ' ' . $rental['details']); ?></td>
                                <td><?php echo e($rental['rental_date']); ?></td>
                                <td class="num"><?php echo e($rental['rental_days']); ?></td>
                                <td class="num"><?php echo baht($rental['deposit_fee']); ?></td>
                                <td><?php echo e($rental['track_rentals']); ?></td>
                                <td><?php echo $badge($rental_badges, $rental['rental_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ยังไม่มีการเช่า</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>การคืนสินค้า</h2>
        <?php if ($returns): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>เลขพัสดุที่ส่งคืน</th>
                            <th>วันที่แจ้งส่งคืน</th>
                            <th>สถานะ</th>
                            <th class="num">มัดจำที่ได้คืน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                            <tr>
                                <td><?php echo e($return['rental_id']); ?></td>
                                <td><?php echo e($return['track_return']); ?></td>
                                <td><?php echo e($return['return_date'] ?? '-'); ?></td>
                                <td><?php echo $badge($return_badges, $return['returnl_status']); ?></td>
                                <td class="num">
                                    <?php
                                    // สภาพดี คืนมัดจำครึ่งหนึ่ง, ชำรุด ร้านยึดมัดจำทั้งหมด
                                    if ($return['returnl_status'] === 'Pending') {
                                        echo '-';
                                    } else {
                                        echo baht($return['returnl_status'] === 'Complete' ? intdiv((int) $return['deposit_fee'], 2) : 0);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ยังไม่มีการคืนสินค้า</p>
        <?php endif; ?>

        <h3 style="margin-top: 24px;">แจ้งส่งคืนสินค้า</h3>
        <?php if ($returnable): ?>
            <form action="process_return.php" method="POST" class="form-grid two-cols">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="rental_id">รายการเช่าที่จะคืน</label>
                    <select id="rental_id" name="rental_id" required>
                        <?php foreach ($returnable as $item): ?>
                            <option value="<?php echo e($item['rental_id']); ?>"><?php echo e($item['rental_id'] . ' · ' . $item['brand'] . ' ' . $item['details']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="track_return">เลขพัสดุที่ส่งคืน</label>
                    <input type="text" id="track_return" name="track_return" maxlength="50" pattern="[A-Za-z0-9&\-]{8,50}" title="ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว" required>
                </div>
                <div class="full">
                    <button type="submit" class="btn">บันทึกการส่งคืน</button>
                </div>
            </form>
        <?php else: ?>
            <p class="muted">ยังไม่มีรายการที่ต้องส่งคืน (ส่งคืนได้เมื่อร้านยืนยันการเช่าแล้ว)</p>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
