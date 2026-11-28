<?php
require_once '../includes/db.php';
require_admin();

// ขั้นตอนของร้าน: ตรวจสลิปทั้งคำสั่ง (Pending → Paid หรือ Cancelled) → จัดส่งทีละใบพร้อมเลขพัสดุ (Paid → Shipped)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $action = input('action');

    if ($action === 'approve' || $action === 'reject') {
        $order_id = input('order_id');
        $new_status = $action === 'approve' ? 'Paid' : 'Cancelled';
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE rentals SET rental_status = ? WHERE order_id = ? AND rental_status = 'Pending'");
            $stmt->bind_param("ss", $new_status, $order_id);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();

            if ($changed > 0) {
                $stmt = $conn->prepare("UPDATE orders SET reviewed_at = NOW() WHERE order_id = ?");
                $stmt->bind_param("s", $order_id);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
            if ($changed > 0) {
                flash($action === 'approve' ? "อนุมัติคำสั่ง $order_id แล้ว ย้ายไปรอจัดส่ง" : "ปฏิเสธคำสั่ง $order_id แล้ว กระเป๋ากลับมาว่าง");
            } else {
                flash("คำสั่งนี้ถูกตรวจไปแล้ว", "error");
            }
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('admin orders review failed: ' . $e->getMessage());
            flash("บันทึกไม่สำเร็จ กรุณาลองใหม่", "error");
        }
    } elseif ($action === 'ship') {
        $rental_id = input('rental_id');
        $track = strtoupper(input('track_rentals'));
        $employee_id = input('employees_id');

        $stmt = $conn->prepare("SELECT 1 FROM employees WHERE employees_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $employee_ok = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        if (!preg_match('/^[A-Z0-9&\-]{8,50}$/', $track)) {
            flash("เลขพัสดุไม่ถูกต้อง (ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว)", "error");
        } elseif (!$employee_ok) {
            flash("กรุณาเลือกพนักงานที่จัดส่ง", "error");
        } else {
            $stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Shipped', track_rentals = ?, employees_id = ?, shipped_date = CURDATE()
                WHERE rental_id = ? AND rental_status = 'Paid'");
            $stmt->bind_param("sss", $track, $employee_id, $rental_id);
            $stmt->execute();
            $shipped = $stmt->affected_rows > 0;
            $stmt->close();
            flash($shipped ? "บันทึกการจัดส่ง $rental_id แล้ว" : "รายการนี้ไม่อยู่ในสถานะรอจัดส่ง", $shipped ? 'success' : 'error');
        }
    }
    redirect("orders.php");
}

$rental_columns = "r.rental_id, r.order_id, r.product_id, p.brand, r.rental_date, r.rental_days, r.due_date,
    r.daily_price, r.deposit_fee, r.track_rentals, r.shipped_date, r.rental_status, r.employees_id,
    o.customers_id, o.slip_file, o.created_at, c.customers_firstname, c.customers_lastname, c.address, c.postal_code, c.phonenumber";
$rental_from = "FROM rentals r
    JOIN orders o ON o.order_id = r.order_id
    JOIN customers c ON c.customers_id = o.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id";

// 1) คำสั่งที่รอตรวจสลิป จัดกลุ่มตามคำสั่ง
$pending_rows = $conn->query("SELECT $rental_columns $rental_from WHERE r.rental_status = 'Pending' ORDER BY o.created_at, r.rental_id")->fetch_all(MYSQLI_ASSOC);
$pending_orders = [];
foreach ($pending_rows as $row) {
    $pending_orders[$row['order_id']][] = $row;
}

// 2) รอจัดส่ง เรียงตามวันเริ่มเช่าที่ใกล้ที่สุดก่อน
$to_ship = $conn->query("SELECT $rental_columns $rental_from WHERE r.rental_status = 'Paid' ORDER BY r.rental_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);

// 3) อยู่ระหว่างเช่า เรียงตามวันครบกำหนดคืน รายการที่เกินกำหนดจึงขึ้นก่อน
$on_rent = $conn->query("SELECT $rental_columns, e.employees_firstname $rental_from
    LEFT JOIN employees e ON e.employees_id = r.employees_id
    WHERE r.rental_status = 'Shipped' ORDER BY r.due_date, r.rental_id")->fetch_all(MYSQLI_ASSOC);

$employees = $conn->query("SELECT employees_id, employees_firstname FROM employees ORDER BY employees_id")->fetch_all(MYSQLI_ASSOC);
$customer_name = fn(array $row) => $row['customers_id'] . ' ' . $row['customers_firstname'] . ' ' . $row['customers_lastname'];

page_start('คำสั่งเช่า', 'admin', 'orders');
?>
<h1>คำสั่งเช่า</h1>
<?php render_alert(take_flash()); ?>

<div class="stack">
    <section class="card">
        <h2>1. รอตรวจสลิป (<?php echo count($pending_orders); ?>)</h2>
        <p class="muted">เปิดสลิปแล้วเทียบกับยอดที่ต้องได้รับ อนุมัติหรือปฏิเสธทั้งคำสั่ง เพราะลูกค้าโอนมาครั้งเดียว</p>
        <?php if (!$pending_orders): ?>
            <p class="muted">ไม่มีคำสั่งที่รอตรวจสลิป</p>
        <?php endif; ?>
        <?php foreach ($pending_orders as $order_id => $items):
            $first = $items[0];
            $order_total = array_sum(array_map(fn($i) => $i['daily_price'] * $i['rental_days'] + $i['deposit_fee'], $items));
        ?>
            <div class="order-card">
                <div class="order-head">
                    <div>
                        <h3><?php echo e($order_id); ?> · <?php echo e($customer_name($first)); ?></h3>
                        <div class="hint">สั่งเมื่อ <?php echo e($first['created_at']); ?></div>
                    </div>
                    <div class="text-right">
                        <div>ยอดที่ต้องได้รับ <strong><?php echo baht($order_total); ?></strong></div>
                        <?php if ($first['slip_file']): ?>
                            <a href="slip.php?order=<?php echo urlencode($order_id); ?>" target="_blank" rel="noopener">เปิดดูสลิป</a>
                        <?php else: ?>
                            <span class="hint">ไม่มีไฟล์สลิป</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รหัสการเช่า</th>
                                <th>สินค้า</th>
                                <th>วันเริ่มเช่า</th>
                                <th>ส่งคืนภายใน</th>
                                <th class="num">ค่าเช่า</th>
                                <th class="num">ค่ามัดจำ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo e($item['rental_id']); ?></td>
                                    <td><?php echo e($item['product_id'] . ' ' . $item['brand']); ?></td>
                                    <td><?php echo e($item['rental_date']); ?></td>
                                    <td><?php echo e($item['due_date']); ?></td>
                                    <td class="num"><?php echo number_format($item['daily_price'] * $item['rental_days']); ?><div class="hint"><?php echo number_format($item['daily_price']); ?> × <?php echo e($item['rental_days']); ?> วัน</div></td>
                                    <td class="num"><?php echo number_format($item['deposit_fee']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="actions" style="justify-content: flex-end; margin-top: 12px;">
                    <form method="POST" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo e($order_id); ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">ยอดถูกต้อง อนุมัติ</button>
                    </form>
                    <form method="POST" class="inline-form" onsubmit="return confirm('ปฏิเสธคำสั่งนี้? กระเป๋าทุกใบในคำสั่งจะกลับมาว่าง');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?php echo e($order_id); ?>">
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">ปฏิเสธ</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2>2. รอจัดส่ง (<?php echo count($to_ship); ?>)</h2>
        <p class="muted">ส่งให้ถึงก่อนวันเริ่มเช่า แล้วกรอกเลขพัสดุและพนักงานที่จัดส่ง</p>
        <?php if ($to_ship): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>สินค้า</th>
                            <th>วันเริ่มเช่า</th>
                            <th>ส่งถึง</th>
                            <th class="text-right">บันทึกการจัดส่ง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($to_ship as $row): ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?><div class="hint"><?php echo e($row['order_id']); ?></div></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td><?php echo e($row['rental_date']); ?></td>
                                <td><?php echo e($customer_name($row)); ?><div class="hint"><?php echo e($row['address'] . ' ' . $row['postal_code'] . ' · ' . $row['phonenumber']); ?></div></td>
                                <td>
                                    <form method="POST" class="ship-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="rental_id" value="<?php echo e($row['rental_id']); ?>">
                                        <input type="text" name="track_rentals" placeholder="เลขพัสดุ" maxlength="50" pattern="[A-Za-z0-9&\-]{8,50}" title="ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว" aria-label="เลขพัสดุ" required>
                                        <select name="employees_id" aria-label="พนักงานที่จัดส่ง" required>
                                            <option value="">พนักงาน</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?php echo e($employee['employees_id']); ?>"><?php echo e($employee['employees_id'] . ' ' . $employee['employees_firstname']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="action" value="ship" class="btn btn-success btn-sm">จัดส่งแล้ว</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ไม่มีรายการที่รอจัดส่ง</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>3. อยู่ระหว่างเช่า (<?php echo count($on_rent); ?>)</h2>
        <p class="muted">เมื่อลูกค้าแจ้งเลขพัสดุส่งคืน รายการจะย้ายไปหน้า "การคืน"</p>
        <?php if ($on_rent): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>รหัสการเช่า</th>
                            <th>ลูกค้า</th>
                            <th>สินค้า</th>
                            <th>เลขพัสดุจัดส่ง</th>
                            <th>ส่งคืนภายใน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($on_rent as $row): ?>
                            <tr>
                                <td><?php echo e($row['rental_id']); ?></td>
                                <td><?php echo e($customer_name($row)); ?><div class="hint"><?php echo e($row['phonenumber']); ?></div></td>
                                <td><?php echo e($row['product_id'] . ' ' . $row['brand']); ?></td>
                                <td><?php echo e($row['track_rentals']); ?><div class="hint">ส่งเมื่อ <?php echo e($row['shipped_date']); ?> โดย <?php echo e($row['employees_id'] . ' ' . $row['employees_firstname']); ?></div></td>
                                <td><?php echo e($row['due_date']); ?></td>
                                <td><?php echo is_overdue($row) ? '<span class="badge badge-danger">เกินกำหนดคืน</span>' : status_badge('Shipped'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">ไม่มีกระเป๋าที่อยู่ระหว่างเช่า</p>
        <?php endif; ?>
    </section>
</div>
<?php page_end(); ?>
