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

page_start('ตะกร้า', 'shop', 'cart');
?>
<h1>ตะกร้า</h1>
<?php render_alert(take_flash()); ?>

<?php if ($lines): ?>
    <div class="stack">
        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>กระเป๋า</th>
                        <th>ช่วงเช่า</th>
                        <th class="num">ค่าเช่า</th>
                        <th class="num">มัดจำ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): $problem = cart_problem($line); ?>
                        <tr<?php echo $problem ? ' class="row-problem"' : ''; ?>>
                            <td>
                                <div class="rental-product">
                                    <img src="<?php echo e(product_image_url($line)); ?>" alt="" loading="lazy">
                                    <div>
                                        <?php echo e($line['brand'] . ' ' . $line['details']); ?>
                                        <?php if ($problem): ?><div class="problem"><?php echo e($problem); ?></div><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="nowrap">
                                <?php echo e(thai_date($line['rental_date'])); ?> · <?php echo $line['days']; ?> วัน
                                <div class="hint">คืนภายใน <?php echo e(thai_date($line['due_date'])); ?></div>
                            </td>
                            <td class="num"><?php echo baht($line['rent']); ?><div class="hint"><?php echo number_format($line['daily_rental_price']); ?> × <?php echo $line['days']; ?></div></td>
                            <td class="num"><?php echo baht($line['deposit']); ?></td>
                            <td class="text-right">
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" name="remove" value="<?php echo e($line['product_id']); ?>" class="btn btn-outline btn-sm">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right">ยอดโอนรวม</td>
                        <td class="num" colspan="2"><?php echo baht($total); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="card">
            <div class="order-head" style="margin: 0;">
                <div>
                    <strong>จัดส่งถึง คุณ <?php echo e($customer['customers_firstname'] . ' ' . $customer['customers_lastname']); ?></strong>
                    <div class="hint"><?php echo e($customer['address'] . ' ' . $customer['postal_code']); ?> · <?php echo e($customer['phonenumber']); ?></div>
                    <?php if (is_member($customer)): ?><div class="hint">คุณเป็นสมาชิก ไม่ต้องวางมัดจำค่ะ</div><?php endif; ?>
                </div>
                <a href="edit_profile.php" class="btn btn-outline btn-sm">แก้ไขที่อยู่</a>
            </div>
        </div>

        <div class="actions" style="justify-content: flex-end;">
            <a href="index.php" class="btn btn-outline">เลือกกระเป๋าเพิ่ม</a>
            <?php if ($has_problem): ?>
                <button type="button" class="btn" disabled>ชำระเงิน</button>
            <?php else: ?>
                <a href="checkout.php" class="btn">ชำระเงิน</a>
            <?php endif; ?>
        </div>
        <?php if ($has_problem): ?>
            <p class="problem text-right">กรุณาลบรายการที่เช่าไม่ได้ก่อนชำระเงินนะคะ</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card empty">
        <?php echo icon('cart'); ?>
        <p>ยังไม่มีกระเป๋าในตะกร้าค่ะ</p>
        <a href="index.php" class="btn">เลือกกระเป๋า</a>
    </div>
<?php endif; ?>
<?php page_end(); ?>
