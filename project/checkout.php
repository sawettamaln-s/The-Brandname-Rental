<?php
require_once 'db_connect.php';

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันคำสั่งซื้อ - ระบบเช่า</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="checkout_styles.css">
<style>
        .back-btn {
            display: block;
            text-align: center;
            margin: 20px auto;
            padding: 12px;
            background-color: #7f8c8d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            width: 220px;
            font-size: 16px;
        }
        .back-btn:hover {
            background-color: #636e72;
        }
</style>
</head>
<body>
    <header>
        THE BRANDNAME RENTAL
    </header>

    <main>
        <div class="container">
            <div class="card payment-info">
                <h2>สแกนเพื่อชำระเงิน</h2>
                <img src="images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
                <div class="total">ยอดรวม: <?php echo number_format($total, 2); ?> บาท</div>
                <form action="confirm_order.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="confirm-btn" style="border: none; cursor: pointer; font: inherit;">ยืนยันการชำระเงิน</button>
                </form>
            </div>
        </div>
    </main>
    <a href="cart.php" class="back-btn">ย้อนกลับสู่หน้าหลัก</a>
</body>
</html>
