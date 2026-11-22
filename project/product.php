<?php
session_start();
include 'db_connect.php';

$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : null;

if ($product_id) {
    $sql = "SELECT * FROM products WHERE product_id = '$product_id'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        die("ไม่พบข้อมูลสินค้า");
    }
} else {
    die("ไม่มีข้อมูลสินค้า");
}

$user_profile = null;
if (isset($_SESSION["customers_id"])) {
    $customer_id = $_SESSION["customers_id"];
    $sql_user = "SELECT * FROM customers WHERE customers_id = '$customer_id'";
    $result_user = $conn->query($sql_user);

    if ($result_user->num_rows > 0) {
        $user_profile = $result_user->fetch_assoc();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'product_name' => $product['brand'],
            'product_price' => $product['daily_rental_price'],
            'quantity' => $quantity
        ];
    }

    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดสินค้า - ระบบเช่า</title>
    <link rel="stylesheet" href="product_styles.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>THE BRANDNAME RENTAL</h1>
            <div class="auth-buttons">
                <?php if (isset($_SESSION["customers_id"])): ?>
                    <button class="profile-btn" onclick="toggleProfile()">โปรไฟล์</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <h2>รายละเอียดสินค้า</h2>

            <?php if (isset($product)): ?>
                <div class="product-detail">
                    <img src="images/<?php echo $product['product_id']; ?>.jpg" alt="<?php echo $product['brand']; ?>">
                    <div class="info">
                        <h3><?php echo $product['brand']; ?></h3>
                        <p><?php echo $product['details']; ?></p>
                        <p class="price"><strong>ราคาเช่า:</strong> <?php echo number_format($product['daily_rental_price'], 2); ?> บาท / วัน</p>

                        <form method="POST">
                            <label for="quantity">จำนวนวัน:</label>
                            <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                            <button type="submit">เพิ่มลงตะกร้า</button>
                        </form>

                        <a href="index.php" class="back-btn">ย้อนกลับสู่หน้าหลัก</a>
                    </div>
                </div>
            <?php else: ?>
                <p>ไม่พบข้อมูลสินค้า</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 ระบบเช่า</p>
        </div>
    </footer>
</body>
</html>
