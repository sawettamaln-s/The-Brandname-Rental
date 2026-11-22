<?php
include 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phonenumber = $_POST['phonenumber'];
    $address = $_POST['address'];
    $postal_code = $_POST['postal_code'];
    $password = $_POST['password'];
    $customers_member = 'no';

    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "อีเมลนี้มีในระบบแล้ว!";
    } else {

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT MAX(SUBSTRING(customers_id, 2)) AS last_id FROM customers");
    $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $last_id = $row['last_id'] ? (int)$row['last_id'] : 11;
        $new_id = 'C' . str_pad($last_id + 1, 3, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO customers (customers_id, customers_firstname, customers_lastname,
        gender, customers_member, email, phonenumber, address, postal_code, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $new_id, $firstname, $lastname, $gender, $customers_member, $email, $phonenumber, $address, $postal_code, $hashed_password);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่อีกครั้ง";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบเช่า</title>
    <link rel="stylesheet" href="register_styles.css">
    
    <style>
body {
    font-family: 'Prompt', sans-serif;
    background-color: #ffffff;
    margin: 0;
    padding: 0;
    color: #333;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.register-container {
    width: 90%;
    max-width: 500px;
    padding: 40px;
    background: #fff;
    border: 3px solid #34495e;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    text-align: center;
}
h2 {
    color: #34495e;
    font-size: 2em;
    margin-bottom: 25px;
}
.error {
    color: red;
    font-weight: bold;
    background: #ffebeb;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.input-group {
    text-align: left;
    margin-bottom: 18px;
    margin: 15px 25px 20px 5px;
}
.input-group label {
    display: block;
    font-weight: bold;
    color: #34495e;
    margin-bottom: 5px;
}
.input-group input {
    width: 100%;
    padding: 12px;
    border: 2px solid #34495e;
    border-radius: 8px;
    font-size: 1em;
    outline: none;
    transition: 0.3s;
}
.input-group input:focus {
    border-color: #2980b9;
    box-shadow: 0 0 8px rgba(41, 128, 185, 0.5);
}
.gender-group {
    text-align: left;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.gender-group label {
    font-weight: bold;
    color: #34495e;
    display: flex;
    align-items: center;
    gap: 5px;
}
.gender-group input {
    margin-right: 5px;
}
button {
    width: 100%;
    padding: 14px;
    font-size: 1.2em;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    background-color: #34495e;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}
button:hover {
    background-color: #218838;
    transform: translateY(-2px);
}
.login-link {
    margin-top: 20px;
}
.login-link a {
    color: #34495e;
    font-weight: bold;
    text-decoration: none;
}
.login-link a:hover {
    text-decoration: underline;
}
</style>

</head>
<body>
    <div class="register-container">
        <h2>สมัครสมาชิก</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label for="firstname">ชื่อ:</label>
                <input type="text" name="firstname" id="firstname" required>
                <label for="lastname">นามสกุล:</label>
                <input type="text" name="lastname" id="lastname" required>
            </div>
            <div class="gender-group">
                <label>เพศ:</label>
                <label for="male">
                    <input type="radio" name="gender" id="male" value="Male" required> male
                </label>
                <label for="female">
                    <input type="radio" name="gender" id="female" value="Female" required> female
                </label>
                <label for="other">
                    <input type="radio" name="gender" id="other" value="Other" required> other
                </label>
            </div>
            <div class="input-group">
                <label for="email">อีเมล:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="input-group">
                <label for="phonenumber">เบอร์โทรศัพท์:</label>
                <input type="tel" name="phonenumber" id="phonenumber" required>
            </div>
            <div class="input-group">
                <label for="address">ที่อยู่:</label>
                <input type="text" name="address" id="address" required>
            </div>
            <div class="input-group">
                <label for="postal_code">รหัสไปรษณีย์:</label>
                <input type="text" name="postal_code" id="postal_code" required>
            </div>
            <button type="submit">สมัครสมาชิก</button>
        </form>
        <div class="login-link">
            <p>มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
        </div>
    </div>
</body>
</html>
