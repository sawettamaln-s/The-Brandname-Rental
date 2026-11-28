<?php
require_once 'includes/db.php';

$customer = current_customer($conn);
$customer_id = $customer['customers_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove'])) {
    verify_csrf();
    unset($_SESSION['cart'][input('remove')]);
    redirect("cart.php");
}

$lines = cart_lines($conn, $customer);
$total = array_sum(array_column($lines, 'subtotal'));
$has_problem = (bool) array_filter($lines, fn($line) => cart_problem($line) !== null);

page_start('ตะกร้าสินค้า', 'shop', 'cart');
?>
<h1>ตะกร้าสินค้า</h1>
<?php render_alert(take_flash()); ?>

<div class="stack">
    <div class="card">
        <p>
            <strong><?php echo e($customer['customers_firstname'] . ' ' . $customer['customers_lastname']); ?></strong>
            · <?php echo is_member($customer) ? 'สมาชิก (ไม่ต้องวางมัดจำ)' : 'ลูกค้าทั่วไป (มัดจำ ' . number_format(DEPOSIT_FEE) . ' บาทต่อใบ)'; ?>
        </p>
        <p class="muted">จัดส่งที่: <?php echo e($customer['address'] . ' ' . $customer['postal_code']); ?> · โทร <?php echo e($customer['phonenumber']); ?></p>
    </div>

    <?php if ($lines): ?>
        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>วันเริ่มเช่า</th>
                        <th class="num">จำนวนวัน</th>
                        <th>ส่งคืนภายใน</th>
                        <th class="num">ราคา/วัน</th>
                        <th class="num">ค่าเช่า</th>
                        <th class="num">ค่ามัดจำ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): $problem = cart_problem($line); ?>
                        <tr<?php echo $problem ? ' class="row-problem"' : ''; ?>>
                            <td>
                                <?php echo e($line['brand'] . ' ' . $line['details']); ?>
                                <?php if ($problem): ?><div class="problem"><?php echo e($problem); ?></div><?php endif; ?>
                            </td>
                            <td><?php echo e($line['rental_date']); ?></td>
                            <td class="num"><?php echo $line['days']; ?></td>
                            <td><?php echo e($line['due_date']); ?></td>
                            <td class="num"><?php echo baht($line['daily_rental_price']); ?></td>
                            <td class="num"><?php echo baht($line['rent']); ?></td>
                            <td class="num"><?php echo baht($line['deposit']); ?></td>
                            <td class="text-right">
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" name="remove" value="<?php echo e($line['product_id']); ?>" class="btn btn-danger btn-sm">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right">รวมทั้งหมด (ค่าเช่า + ค่ามัดจำ)</td>
                        <td class="num" colspan="2"><?php echo baht($total); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="actions" style="justify-content: flex-end;">
            <a href="index.php" class="btn btn-outline">เลือกสินค้าเพิ่ม</a>
            <?php if ($has_problem): ?>
                <button type="button" class="btn btn-success" disabled title="ลบรายการที่มีปัญหาก่อน">ชำระเงิน</button>
            <?php else: ?>
                <a href="checkout.php" class="btn btn-success">ชำระเงิน</a>
            <?php endif; ?>
        </div>
        <?php if ($has_problem): ?>
            <p class="muted text-right">มีรายการที่เช่าไม่ได้ กรุณาลบรายการที่มีกรอบสีแดง แล้วเลือกวันใหม่จากหน้าสินค้า</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="card text-center">
            <p>ตะกร้าสินค้าของคุณว่างเปล่า</p>
            <a href="index.php" class="btn">เลือกสินค้า</a>
        </div>
    <?php endif; ?>
</div>
<?php page_end(); ?>
