<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customers_id"];


$stmt = $conn->prepare("SELECT customers_id, customers_firstname, customers_lastname, gender, customers_member,
phonenumber, email, address, postal_code FROM customers WHERE customers_id = ?");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
} else {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit();
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $gender = $_POST['gender'];
    $phonenumber = $_POST['phonenumber'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $postal_code = $_POST['postal_code'];

    $update_stmt = $conn->prepare("UPDATE customers SET customers_firstname = ?, customers_lastname = ?,
    gender = ?, phonenumber = ?, email = ?, address = ?, postal_code = ? WHERE customers_id = ?");
    $update_stmt->bind_param("ssssssss", $firstname, $lastname, $gender, $phonenumber, $email, $address, $postal_code, $customer_id);

    if ($update_stmt->execute()) {
        $message = "ข้อมูลถูกอัพเดทเรียบร้อย";
    } else {
        $message = "ไม่สามารถอัพเดทข้อมูลได้";
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลโปรไฟล์</title>
    <style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f9;
}

header {
    background-color: #2c3e50;
    color: white;
    text-align: center;
    padding: 15px;
    font-size: 24px;
    font-weight: bold;
}

main {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.container {
    padding: 20px;
}

h2 {
    text-align: center;
    color: #333;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-size: 14px;
    color: #555;
    display: block;
    margin-bottom: 5px;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    color: #333;
}

.form-group input:focus, .form-group select:focus {
    border-color: #34495e;
    outline: none;
    background-color: #fff;
}

.edit-btn {
    width: 100%;
    padding: 12px;
    background-color: #34495e;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.edit-btn:hover {
    background-color: #45a049;
}

.message {
    color: green;
    text-align: center;
    margin-bottom: 20px;
    font-size: 16px;
}

a.back-btn {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #34495e;
    text-decoration: none;
    font-size: 14px;
}

a.back-btn:hover {
    text-decoration: underline;
}

footer {
    background-color: #34495e;
    color: white;
    text-align: center;
    padding: 10px;
    position: absolute;
    width: 100%;
    bottom: 0;
}

</style>
</head>
<body>

<header>
    THE BRANDNAME RENTAL
</header>
<main>
    <div class="container">
        <h2>แก้ไขข้อมูลโปรไฟล์</h2>

        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST">
            <div class="form-group">
                <label for="firstname">ชื่อ</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['customers_firstname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="lastname">นามสกุล</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['customers_lastname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">เพศ</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>ชาย</option>
                    <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>หญิง</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phonenumber">เบอร์โทร</label>
                <input type="text" id="phonenumber" name="phonenumber" value="<?php echo htmlspecialchars($user['phonenumber']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">ที่อยู่</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
            </div>
            <div class="form-group">
                <label for="postal_code">รหัสไปรษณีย์</label>
                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
            </div>
            <button type="submit" class="edit-btn">อัพเดทข้อมูล</button>
        </form>
        <a href="profile.php" class="back-btn">ย้อนกลับ</a>
    </div>
</main>

<footer>
    &copy; 2025 ระบบเช่า
</footer>

</body>
</html>
