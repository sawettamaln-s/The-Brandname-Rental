<?php
require_once 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = input('email');
    $password = input('password', false);

    $stmt = $conn->prepare("SELECT customers_id, customers_firstname, customers_lastname, password FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // แจ้งข้อความเดียวกันทั้งกรณีไม่มีอีเมลและรหัสผิด เพื่อไม่ให้เดาได้ว่าอีเมลไหนมีในระบบ
    if ($user && password_verify($password, $user['password'])) {
        start_login_session();
        $_SESSION["customers_id"] = $user["customers_id"];
        $_SESSION["customers_name"] = $user["customers_firstname"] . " " . $user["customers_lastname"];
        redirect("index.php");
    }
    $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบเช่า</title>
    <link rel="stylesheet" href="styles.css">
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

.login-container {
    width: 100%;
    max-width: 400px;
    margin: 50px auto;
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
    color:  #34495e;
}

.input-group {
    margin-bottom: 18px;
}

.input-group label {
    font-size: 18px;
    color: #555;
    display: block;
    margin-bottom: 5px;
}

.input-group input {
    width: 100%;
    padding: 15px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    color: #333;
}

.input-group input:focus {
    border-color: #4caf50;
    outline: none;
    background-color: #fff;
}

button {
    width: 100%;
    padding: 15px;
    background-color: #34495e;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #45a049;
}

.error {
    color: red;
    text-align: center;
    margin-bottom: 15px;
}

.register-link {
    text-align: center;
    font-size: 18px;
    margin-top: 20px;
}

.register-link a {
    color: #34495e;
    text-decoration: none;
}


.register-link a:hover {
    text-decoration: underline;
}
</style>

</head>
<body>

    <div class="login-container">
        <h2>เข้าสู่ระบบ</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"> <?php echo e($error); ?> </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="input-group">
                <label for="email">อีเมล:</label>
                <input type="email" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน :</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <div class="register-link">
            <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
        </div>
    </div>
    
</body>
</html>
