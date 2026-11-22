<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php");
    exit();
}

$customer_id = $_SESSION["customers_id"];

$stmt = $conn->prepare("SELECT customers_firstname, customers_lastname, gender, customers_member, phonenumber, email, address, postal_code FROM customers WHERE customers_id = ?");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $customer = $result->fetch_assoc();
} else {
    echo "ไม่พบข้อมูลลูกค้า";
    exit();
}
$stmt->close();

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['product_price'] * $item['quantity'];
}

if ($customer['customers_member'] === 'no') {
    $total += 900;
}
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
                <a href="confirm_order.php" class="confirm-btn">ยืนยันการชำระเงิน</a>
            </div>
        </div>
    </main>
    <a href="cart.php" class="back-btn">ย้อนกลับสู่หน้าหลัก</a>
</body>
</html>
