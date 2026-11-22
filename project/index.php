<?php
include 'db_connect.php';
session_start();

$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM products WHERE brand LIKE ? OR details LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก - ระบบเช่า</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
        .search-form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 40px;
            padding-top: 20px;
        }

        .search-form input[type="text"] {
            padding: 15px 20px;
            font-size: 1.2rem;
            border: 2px solid #34495e;
            border-radius: 25px 0 0 25px;
            width: 300px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-form input[type="text"]:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 5px rgba(44, 62, 80, 0.5);
        }

        .search-form button {
            padding: 15px 25px;
            font-size: 1.2rem;
            border: 2px solid #34495e;
            border-radius: 0 25px 25px 0;
            background-color: #34495e;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .search-form button:hover {
            background-color: #2c3e50;
            transform: scale(1.05);
        }

        .search-form button:focus {
            outline: none;
        }
        
    </style>
<body>
    <header>
        <div class="container">
            <h1>THE BRANDNAME RENTAL</h1>
            <div class="auth-buttons">
                <?php if (isset($_SESSION["customers_id"])): ?>
                    <a href="profile.php" class="cart-btn">โปรไฟล์</a>
                    <a href="cart.php" class="cart-btn">ตะกร้าสินค้า</a>
                    <a href="logout.php" class="cart-btn">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="cart-btn">เข้าสู่ระบบ</a>
                    <a href="cart.php" class="cart-btn">ตะกร้าสินค้า</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <form action="index.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">ค้นหา</button>
    </form>

    <main>
        <div class="container">
            <div class="product-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <div class="product-item">
                            <img src="images/<?php echo $product['product_id']; ?>.png" alt="<?php echo $product['brand']; ?>">
                            <h3><?php echo $product['brand']; ?></h3>
                            <p><?php echo $product['details']; ?></p>
                            <p><strong>ราคาเช่า:</strong> <?php echo number_format($product['daily_rental_price'], 2); ?> บาท / วัน</p>
                            <a href="product_detail.php?product_id=<?php echo $product['product_id']; ?>" class="rent-btn">เช่าตอนนี้</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>ไม่พบสินค้าที่ค้นหา</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; sumsumbag_shop
                    e-mail sumsum_bag_shop@gmail.com
                    </div>
                    address 6, Rajamankha Nai Rd.,Amphoe Muang, Nakhon Pathom
                    Phone 081-2345678
        </div>
    </footer>
</body>
</html>
