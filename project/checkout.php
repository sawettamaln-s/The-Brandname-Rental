<?php
require_once 'includes/db.php';

$customer_id = require_customer();
$customer = find_customer($conn, $customer_id);
if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}

$lines = cart_lines($conn, $customer);
if (count($lines) === 0) {
    redirect("cart.php");
}
$total = array_sum(array_column($lines, 'subtotal'));

page_start('ชำระเงิน', 'shop', 'cart');
?>
<div class="page-narrow">
    <div class="card payment">
        <h1>สแกนเพื่อชำระเงิน</h1>
        <img src="assets/images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
        <div class="total">ยอดรวม <?php echo baht($total); ?></div>
        <p class="muted">ค่าเช่า <?php echo count($lines); ?> รายการ รวมค่ามัดจำ ร้านจะตรวจยอดโอนแล้วยืนยันคำสั่งเช่าให้</p>
        <form action="confirm_order.php" method="POST" class="actions" style="justify-content: center;">
            <?php echo csrf_field(); ?>
            <a href="cart.php" class="btn btn-outline">กลับไปตะกร้า</a>
            <button type="submit" class="btn btn-success">ยืนยันการชำระเงิน</button>
        </form>
    </div>
</div>
<?php page_end(); ?>
