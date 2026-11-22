<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === "admin" && $password === "admin123") {
        $_SESSION["admin_logged_in"] = true;
        header("Location: index_admin.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!";
    }
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
            <div class="error"> <?php echo $error; ?> </div>
        <?php endif; ?>
        
        <form method="POST">
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
