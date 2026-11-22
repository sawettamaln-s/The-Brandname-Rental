<?php
require_once 'db_connect.php';

$customer_id = require_customer();
$user = find_customer($conn, $customer_id);
if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}

function has_pending_request(mysqli $conn, string $customer_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM membership_requests WHERE customers_id = ? AND status = 'Pending'");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $pending = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $pending;
}

$already_member = is_member($user);
$pending = has_pending_request($conn, $customer_id);
$upload_error = '';

// ลูกค้าแนบสลิปแล้วระบบสร้างคำขอรออนุมัติ สถานะสมาชิกเปลี่ยนเมื่อแอดมินตรวจสลิปและอนุมัติเท่านั้น
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_member && !$pending) {
    verify_csrf();
    $saved = save_slip($_FILES['payment_slip'] ?? null);
    if (isset($saved['error'])) {
        $upload_error = $saved['error'];
    } else {
        $stmt = $conn->prepare("INSERT INTO membership_requests (customers_id, slip_file) VALUES (?, ?)");
        $stmt->bind_param("ss", $customer_id, $saved['file']);
        $stmt->execute();
        $stmt->close();
        flash("ส่งสลิปสมัครสมาชิกแล้ว ร้านจะตรวจสอบและอนุมัติให้ค่ะ");
        redirect("profile.php");
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
                <h3>ค่าชำระ <?php echo number_format(MEMBER_FEE); ?> บาท</h3>
                <p>การสมัครสมาชิกจะทำให้คุณเป็นสมาชิก 1 ปีนับจากวันที่ร้านอนุมัติ</p>
                <p>สมาชิกเช่ากระเป๋าได้โดยไม่ต้องวางมัดจำ (ปกติ <?php echo number_format(DEPOSIT_FEE); ?> บาทต่อใบ)</p>

                <?php if ($already_member): ?>
                    <p style="color: green; font-weight: bold;">คุณเป็นสมาชิกอยู่แล้ว<?php echo $user['member_until'] ? ' (ถึง ' . e($user['member_until']) . ')' : ''; ?></p>
                <?php elseif ($pending): ?>
                    <p style="color: orange; font-weight: bold;">ส่งสลิปแล้ว รอร้านตรวจสอบและอนุมัติค่ะ</p>
                <?php else: ?>
                    <img src="images/qrcode.png" alt="QR Code สำหรับการชำระเงิน">
                    <div class="total">ยอดรวม: <?php echo number_format(MEMBER_FEE); ?> บาท</div>

                    <form action="member.php" method="post" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <p>แนบสลิปการโอน (JPG, PNG หรือ WEBP ไม่เกิน 2 MB)</p>
                        <input type="file" name="payment_slip" accept="image/jpeg,image/png,image/webp" required><br><br>
                        <button type="submit" class="upload-btn">ส่งสลิปเพื่อสมัครสมาชิก</button>
                    </form>

                    <?php if ($upload_error !== ''): ?>
                        <p style="color: red; font-weight: bold;"><?php echo e($upload_error); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="profile.php" class="back-btn">ย้อนกลับ</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; 2025 ระบบเช่า
    </footer>
</body>
</html>
