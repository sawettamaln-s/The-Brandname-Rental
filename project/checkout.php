<?php
require_once 'includes/db.php';

$customer = current_customer($conn);

$lines = cart_lines($conn, $customer);
if (count($lines) === 0) {
    redirect("cart.php");
}
foreach ($lines as $line) {
    if (cart_problem($line) !== null) {
        flash("มีรายการในตะกร้าที่เช่าไม่ได้ กรุณาแก้ไขก่อนชำระเงิน", "error");
        redirect("cart.php");
    }
}
$total = array_sum(array_column($lines, 'subtotal'));
$deposit_total = array_sum(array_column($lines, 'deposit'));

page_start('ชำระเงิน', 'shop', 'cart');
?>
<div class="page-narrow">
    <div class="card payment">
        <h1>สแกนเพื่อชำระเงิน</h1>
        <?php render_alert(take_flash()); ?>
        <img src="assets/images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
        <div class="total">ยอดโอน <?php echo baht($total); ?></div>
        <p class="muted">
            ค่าเช่า <?php echo count($lines); ?> ใบ <?php echo baht($total - $deposit_total); ?>
            + ค่ามัดจำ <?php echo baht($deposit_total); ?>
        </p>
        <form action="confirm_order.php" method="POST" enctype="multipart/form-data" class="form-grid" style="text-align: left;">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="payment_slip">แนบสลิปการโอน</label>
                <input type="file" id="payment_slip" name="payment_slip" accept="image/jpeg,image/png,image/webp" required>
                <div class="hint">ไฟล์ JPG, PNG หรือ WEBP ไม่เกิน 2 MB · ร้านจะตรวจยอดโอนแล้วจัดส่งตามวันเริ่มเช่า</div>
            </div>
            <div class="actions" style="justify-content: center; margin-top: 0;">
                <a href="cart.php" class="btn btn-outline">กลับไปตะกร้า</a>
                <button type="submit" class="btn btn-success">ส่งสลิปและยืนยันคำสั่งเช่า</button>
            </div>
        </form>
    </div>
</div>
<?php page_end(); ?>
