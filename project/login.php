<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['phonenumber'];

    $sql = "SELECT * FROM customers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION["customers_id"] = $user["customers_id"];
            $_SESSION["customers_name"] = $user["customers_firstname"] . " " . $user["customers_lastname"];
            header("Location: index.php");
            exit();
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $error = "อีเมลนี้ไม่มีอยู่ในระบบ!";
    }
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
            <div class="error"> <?php echo $error; ?> </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label for="email">อีเมล:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="phonenumber">รหัสผ่าน :</label>
                <input type="password" name="phonenumber" id="phonenumber" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <div class="register-link">
            <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
        </div>
    </div>
    
</body>
</html>
