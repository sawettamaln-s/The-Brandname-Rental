<?php
require_once 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $username = input('username');
    $password = input('password', false);

    // บัญชีแอดมินเก็บในตาราง admins โดยรหัสผ่านเป็น hash
    $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        start_login_session();
        $_SESSION["admin_id"] = $admin['admin_id'];
        redirect("index_admin.php");
    }
    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแล</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        button {
            background: #34495e;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>เข้าสู่ระบบผู้ดูแล</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"> <?php echo e($error); ?> </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="input-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
    </div>
    
</body>
</html>
