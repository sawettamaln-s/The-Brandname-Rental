<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

// การเช่าทุกใบของลูกค้า (ลูกค้าเป็นเจ้าของผ่านคำสั่งเช่า orders)
$rental_stmt = $conn->prepare("SELECT r.rental_id, r.order_id, p.brand, p.details, r.track_rentals,
    r.rental_date, r.rental_days, r.due_date, r.daily_price, r.deposit_fee, r.rental_status,
    t.track_return, rs.return_date
    FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    LEFT JOIN products p ON p.product_id = r.product_id
    LEFT JOIN track_return t ON t.rental_id = r.rental_id
    LEFT JOIN returnss rs ON rs.rental_id = r.rental_id
    WHERE o.customers_id = ? ORDER BY r.rental_id DESC");
$rental_stmt->bind_param("s", $customer_id);
$rental_stmt->execute();
$rentals = $rental_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rental_stmt->close();

// รายการที่ส่งคืนได้: ร้านจัดส่งแล้วและยังไม่ได้แจ้งคืน
$returnable = array_values(array_filter($rentals, fn($r) => $r['rental_status'] === 'Shipped'));
// รายการที่แจ้งคืนแล้ว (รอตรวจหรือตรวจแล้ว)
$returns = array_values(array_filter($rentals, fn($r) => $r['track_return'] !== null));

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
                            <th>คำสั่ง / รหัสการเช่า</th>
                            <th>สินค้า</th>
                            <th>วันเริ่มเช่า</th>
                            <th>ส่งคืนภายใน</th>
                            <th class="num">ค่าเช่า</th>
                            <th class="num">ค่ามัดจำ</th>
                            <th>เลขพัสดุจัดส่ง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><?php echo e($rental['order_id']); ?><div class="hint"><?php echo e($rental['rental_id']); ?></div></td>
                                <td><?php echo e($rental['brand'] . ' ' . $rental['details']); ?></td>
                                <td><?php echo e($rental['rental_date']); ?></td>
                                <td><?php echo e($rental['due_date']); ?></td>
                                <td class="num"><?php echo baht($rental['daily_price'] * $rental['rental_days']); ?><div class="hint"><?php echo e($rental['rental_days']); ?> วัน</div></td>
                                <td class="num"><?php echo baht($rental['deposit_fee']); ?></td>
                                <td><?php echo e($rental['track_rentals'] ?? '-'); ?></td>
                                <td>
                                    <?php echo status_badge($rental['rental_status']); ?>
                                    <?php if (is_overdue($rental)): ?> <span class="badge badge-danger">เกินกำหนดคืน</span><?php endif; ?>
                                </td>
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
                            <th>สินค้า</th>
                            <th>เลขพัสดุที่ส่งคืน</th>
                            <th>วันที่ร้านได้รับ</th>
                            <th>สถานะ</th>
                            <th class="num">มัดจำที่ได้คืน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): $refund = deposit_refund($return['rental_status'], (int) $return['deposit_fee']); ?>
                            <tr>
                                <td><?php echo e($return['rental_id']); ?></td>
                                <td><?php echo e($return['brand'] . ' ' . $return['details']); ?></td>
                                <td><?php echo e($return['track_return']); ?></td>
                                <td><?php echo e($return['return_date'] ?? '-'); ?></td>
                                <td><?php echo status_badge($return['rental_status']); ?></td>
                                <td class="num"><?php echo $refund === null ? '-' : baht($refund); ?></td>
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
                            <option value="<?php echo e($item['rental_id']); ?>"><?php echo e($item['rental_id'] . ' · ' . $item['brand'] . ' ' . $item['details'] . ' · คืนภายใน ' . $item['due_date']); ?></option>
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
            <p class="muted">ยังไม่มีรายการที่ต้องส่งคืน (แจ้งส่งคืนได้เมื่อร้านจัดส่งกระเป๋าแล้ว)</p>
        <?php endif; ?>
    </div>
</div>
<?php page_end(); ?>
