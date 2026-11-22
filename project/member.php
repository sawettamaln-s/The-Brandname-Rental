<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customers_id"];
$sql = "SELECT * FROM customers WHERE customers_id = '$customer_id'";
$result = $conn->query($sql);

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
} else {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit();
}

$subscription_end_date = date('Y-m-d', strtotime('+1 year'));

$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['payment_slip'])) {
    $upload_dir = 'uploads/';
    $upload_file = $upload_dir . basename($_FILES['payment_slip']['name']);
    $upload_ok = 1;
    $image_file_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));

    if (getimagesize($_FILES['payment_slip']['tmp_name']) === false) {
        $upload_message = "กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น";
        $upload_ok = 0;
    }

    if ($_FILES['payment_slip']['size'] > 500000) {
        $upload_message = "ขออภัย ไฟล์ของคุณใหญ่เกินไป";
        $upload_ok = 0;
    }

    if ($upload_ok == 0) {
        $upload_message = "ขออภัย ไม่สามารถอัปโหลดไฟล์ของคุณได้";
    } else {
        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $upload_file)) {

            $_SESSION['payment_slip_uploaded'] = true;
            $_SESSION['uploaded_file'] = $upload_file;
            $upload_message = "อัปโหลดสลิปการชำระเงินสำเร็จ!";
        } else {
            $upload_message = "ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบเช่า</title>
</head>
<body>
    <header>
        THE BRANDNAME RENTAL
    </header>
    <style>
body {
    font-family: 'Prompt', sans-serif;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    margin: 0;
    padding: 0;
}

header {
    background-color: #34495e;
    color: #fff;
    padding: 20px 0;
    text-align: center;
    font-size: 1.8em;
    font-weight: bold;
    letter-spacing: 1px;
}

.container {
    width: 85%;
    max-width: 1000px;
    margin: 30px auto;
    padding: 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
    font-size: 1.2em;
}

h2 {
    font-size: 1.8em;
    color: #333;
    margin-bottom: 30px;
}

.payment-info {
    padding: 20px;
    border-radius: 10px;
    background-color: #f8f9fa;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.payment-info h3 {
    font-size: 1.6em;
    color: #d9534f;
    margin-bottom: 15px;
}

.payment-info img {
    width: 200px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 2px solid #ddd;
}

.total {
    font-size: 1.5em;
    font-weight: bold;
    color: #d9534f;
    margin: 20px 0;
}

.confirm-btn,
.upload-btn {
    display: inline-block;
    padding: 12px 30px;
    background-color: #28a745;
    color: white;
    font-size: 1.1em;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    margin: 10px 0;
}

.confirm-btn:hover,
.upload-btn:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.upload-btn {
    background-color: #ffc107;
    color: black;
}

.confirm-btn[style*="pointer-events: none"] {
    background-color: #6c757d;
    opacity: 0.6;
}

footer {
    background-color: #34495e;
    color: #fff;
    text-align: center;
    padding: 20px 0;
    font-size: 1.1em;
    margin-top: 40px;
}

.back-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 25px;
    background-color: #007bff;
    color: white;
    font-size: 1.1em;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

.payment-info form {
    margin-top: 30px;
}

.payment-info input[type="file"] {
    display: block;
    margin: 10px auto;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #ddd;
    font-size: 1em;
}

.payment-info p {
    font-size: 1.2em;
    margin-top: 20px;
    color: #555;
}

.payment-info img {
    border-radius: 10px;
    margin-bottom: 20px;
}
</style>
    <main>
        <div class="container">
            <h2>สมัครสมาชิก</h2>
            <div class="payment-info">
                <h3>ค่าชำระ 3000 บาท</h3>
                <p>การสมัครสมาชิกจะทำให้คุณเป็นสมาชิก 1 ปีจากวันที่ชำระเงิน</p>
                
                <img src="images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
                <div class="total">ยอดรวม: 3000 บาท</div>

                <p>สมาชิกจะได้รับสิทธิประโยชน์ต่างๆ ตลอด 1 ปี</p>

                <form action="member.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="payment_slip" required><br><br>
                    <button type="submit" class="upload-btn">อัปโหลดสลิปการชำระเงิน</button>
                </form>

                <?php
                if ($upload_message != '') {
                    echo "<p style='color: green; font-weight: bold;'>$upload_message</p>";
                }
                ?>

                <?php
                if (isset($_SESSION['payment_slip_uploaded']) && $_SESSION['payment_slip_uploaded'] === true) {
                    echo '<a href="confirm_payment.php" class="confirm-btn">ยืนยันการชำระเงิน</a>';
                } else {
                    echo '<a href="#" class="confirm-btn" style="pointer-events: none; opacity: 0.5;">ยืนยันการชำระเงิน</a>';
                }
                ?>

                <a href="profile.php" class="back-btn">ย้อนกลับ</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; 2025 ระบบเช่า
    </footer>
</body>
</html>
