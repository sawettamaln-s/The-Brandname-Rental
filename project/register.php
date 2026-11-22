<?php
require_once 'db_connect.php';

$error = "";
$genders = ['Male', 'Female', 'Other'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $firstname = input('firstname');
    $lastname = input('lastname');
    $gender = input('gender');
    $email = input('email');
    $phonenumber = input('phonenumber');
    $address = input('address');
    $postal_code = input('postal_code');
    $password = input('password', false);

    if ($firstname === '' || $lastname === '' || $address === '') {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } elseif (!in_array($gender, $genders, true)) {
        $error = "กรุณาเลือกเพศ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif (strlen($password) < 8) {
        $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
    } elseif (!preg_match('/^[0-9\-+ ]{9,15}$/', $phonenumber)) {
        $error = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง";
    } elseif (!preg_match('/^[0-9]{5}$/', $postal_code)) {
        $error = "รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก";
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = "อีเมลนี้มีในระบบแล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $new_id = next_id($conn, 'customers', 'customers_id', 'C', 3);
            $customers_member = 'no';

            $stmt = $conn->prepare("INSERT INTO customers (customers_id, customers_firstname, customers_lastname,
            gender, customers_member, email, phonenumber, address, postal_code, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $new_id, $firstname, $lastname, $gender, $customers_member, $email, $phonenumber, $address, $postal_code, $hashed_password);
            $stmt->execute();
            $stmt->close();
            redirect("login.php");
        }
    }
}
$old = fn($key) => e($_POST[$key] ?? '');
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
            <div class="error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="input-group">
                <label for="firstname">ชื่อ:</label>
                <input type="text" name="firstname" id="firstname" maxlength="50" value="<?php echo $old('firstname'); ?>" required>
                <label for="lastname">นามสกุล:</label>
                <input type="text" name="lastname" id="lastname" maxlength="50" value="<?php echo $old('lastname'); ?>" required>
            </div>
            <div class="gender-group">
                <label>เพศ:</label>
                <?php foreach (['Male' => 'male', 'Female' => 'female', 'Other' => 'other'] as $value => $label): ?>
                <label for="<?php echo $label; ?>">
                    <input type="radio" name="gender" id="<?php echo $label; ?>" value="<?php echo $value; ?>" <?php echo (input('gender')) === $value ? 'checked' : ''; ?> required> <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="input-group">
                <label for="email">อีเมล:</label>
                <input type="email" name="email" id="email" maxlength="100" value="<?php echo $old('email'); ?>" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร):</label>
                <input type="password" name="password" id="password" minlength="8" required>
            </div>
            <div class="input-group">
                <label for="phonenumber">เบอร์โทรศัพท์:</label>
                <input type="tel" name="phonenumber" id="phonenumber" maxlength="15" value="<?php echo $old('phonenumber'); ?>" required>
            </div>
            <div class="input-group">
                <label for="address">ที่อยู่:</label>
                <input type="text" name="address" id="address" maxlength="200" value="<?php echo $old('address'); ?>" required>
            </div>
            <div class="input-group">
                <label for="postal_code">รหัสไปรษณีย์:</label>
                <input type="text" name="postal_code" id="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="<?php echo $old('postal_code'); ?>" required>
            </div>
            <button type="submit">สมัครสมาชิก</button>
        </form>
        <div class="login-link">
            <p>มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
        </div>
    </div>
</body>
</html>
