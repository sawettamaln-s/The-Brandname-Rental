<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit();
}

function generateProductId($conn) {
    $sql = "SELECT product_id FROM products ORDER BY product_id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastId = intval(substr($row['product_id'], 2));
        return 'PD' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    } else {
        return 'PD0011';
    }
}

$product_id = generateProductId($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand = $_POST["brand"];
    $details = $_POST["details"];
    $daily_rental_price = $_POST["daily_rental_price"];

    $sql = "INSERT INTO products (product_id, brand, details, daily_rental_price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $product_id, $brand, $details, $daily_rental_price);

    if ($stmt->execute()) {
        $success = "เพิ่มสินค้าสำเร็จ!";
        $product_id = generateProductId($conn);
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสินค้า - Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 60vh;
    margin: 0;
}

.add-product-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    width: 500px;
    text-align: center;
    margin: 80px;
}

h2 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.8rem;
    font-weight: 600;
}

.input-group {
    margin-bottom: 20px;
    text-align: left;
    width: 100%;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

input[type="text"],
input[type="number"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
    margin-top: 5px;
}

.success {
    color: green;
    margin-bottom: 15px;
}

.error {
    color: red;
    margin-bottom: 15px;
}

button {
    background: #34495e;
    color: white;
    border: none;
    padding: 12px;
    width: 100%;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

button:hover {
    background:rgb(105, 121, 136);
}

.back-link {
    margin-top: 20px;
    display: block;
    text-decoration: none;
    color: #34495e;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.back-link:hover {
    color:rgb(105, 121, 136);
}

footer {
    background-color: #34495e;
    color: white;
    text-align: center;
    padding: 30px;
    width: 100%;
    position: fixed;
    bottom: 0;
    left: 0;
    font-size: 14px;
}

    </style>

<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>

<body>

    <div class="add-product-container">
        <h2>เพิ่มสินค้าใหม่</h2>

        <?php if (!empty($success)): ?>
            <div class="success"> <?php echo $success; ?> </div>
        <?php elseif (!empty($error)): ?>
            <div class="error"> <?php echo $error; ?> </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>รหัสสินค้า (อัตโนมัติ)</label>
                <input type="text" value="<?php echo $product_id; ?>" disabled>
            </div>
            <div class="input-group">
                <label>ยี่ห้อสินค้า:</label>
                <input type="text" name="brand" required>
            </div>
            <div class="input-group">
                <label>รายละเอียดสินค้า:</label>
                <input type="text" name="details" required>
            </div>
            <div class="input-group">
                <label>ราคาเช่ารายวัน (บาท):</label>
                <input type="number" name="daily_rental_price" required>
            </div>
            <button type="submit">เพิ่มสินค้า</button>
        </form>

        <a href="index_admin.php" class="back-link">กลับไปหน้าหลัก</a>
    </div>

    <footer>
    &copy; 2025 ระบบเช่า
</footer>

    
</body>
</html>
