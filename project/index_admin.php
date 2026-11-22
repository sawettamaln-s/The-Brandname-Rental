<?php
require_once 'db_connect.php';
require_admin();

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

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
    <title>แผงควบคุมผู้ดูแล</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ecf0f1;
            margin: 0;
            padding: 0;
        }

        .menu-icon {
            cursor: pointer;
            display: block;
            width: 90px;
            font-size: 24px;
            color: white;
            background-color: #2c3e50;
            padding: 15px;
            border-radius: 50px;
            text-align: center;
            transition: background-color 0.3s;
            position: absolute;
            left: 30px;
            top: 30px;
        }

        .menu-icon:hover {
            background-color: #1a252f;
        }

        .menu {
            position: absolute;
            top: 60px;
            left: -250px;
            width: 220px;
            background: #34495e;
            padding: 15px;
            transition: left 0.4s ease-in-out;
            box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }

        .menu.show {
            left: 10px;
            top: 80px;
            width: 250px;
            height: 270px;

        }

        .menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .menu a:hover {
            background: #1a252f;
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>ADMIN PANEL - THE BRANDNAME RENTAL</h1>
        <div class="menu-icon" onclick="toggleMenu()">☰</div>
        <div class="menu" id="menu">
            <a href="admin_profile.php">ข้อมูลพนักงาน</a>
            <a href="add_product.php">เพิ่มสินค้า</a>
            <a href="admin_orders.php">จัดการคำสั่งซื้อ</a>
            <a href="admin_return.php">จัดการสถานะการคืน</a>
            <a href="admin_members.php">อนุมัติสมาชิก</a>
            <a href="logout.php">ออกจากระบบ</a>
        </div>
    </div>
</header>

<script>
    function toggleMenu() {
        let menu = document.getElementById("menu");
        if (menu.classList.contains("show")) {
            menu.classList.remove("show");
            menu.style.top = "90px";
        } else {
            menu.classList.add("show");
            menu.style.top = "130px";
        }
    }
</script>

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
    <form action="index_admin.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">ค้นหา</button>
    </form>

    <main>
        <div class="container">
            <div class="product-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <div class="product-item">
                            <img src="images/<?php echo e($product['product_id']); ?>.png" alt="<?php echo e($product['brand']); ?>">
                            <h3><?php echo e($product['brand']); ?></h3>
                            <p><?php echo e($product['details']); ?></p>
                            <p><strong>ราคาเช่า:</strong> <?php echo number_format($product['daily_rental_price'], 2); ?> บาท / วัน</p>
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
            <p>&copy; 2025 ระบบเช่าผู้ดูแล</p>
        </div>
    </footer>
</body>
</html>
