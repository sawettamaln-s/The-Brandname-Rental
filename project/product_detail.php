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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
    $rental_date = isset($_POST['rental_date']) ? $_POST['rental_date'] : null;

    if ($rental_date) {
        $formatted_date = $rental_date;
    } else {
        echo "กรุณากรอกวันที่ให้ถูกต้อง";
        exit;
    }

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
            'quantity' => $quantity,
            'rental_date' => $formatted_date
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
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #34495e;
            color: #fff;
            padding: 20px 0;
            text-align: center;
        }

        header h1 {
            font-size: 2.5em;
        }
        main h2 {
            font-size: 32px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .container {
            width: 80%;
            margin: 0 auto;
        }

        .product-detail {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 20px;
        }

        .product-detail img {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 50px;
            box-shadow: 0 4px 8px rgba(95, 36, 36, 0.1);
}
        .info {
            max-width: 600px;
            padding: 20px;
        }

        .info h3 {
            font-size: 1.8em;
            color: #34495e;
            margin-bottom: 10px;
        }

        .info p {
            font-size: 1.2em;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .price {
            font-size: 1.5em;
            color:rgb(128, 56, 68);
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #34495e;
            color: white;
            font-size: 1.2em;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }

        form {
            margin-top: 20px;
        }

        label {
            font-size: 1.2em;
            margin-right: 10px;
        }

        input[type="number"] {
            padding: 8px;
            font-size: 1.2em;
            width: 60px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="date"] {
            padding: 8px;
            font-size: 1.2em;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #34495e;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1.2em;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 50px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #218838;
        }

        footer {
            background-color:#34495e;
            color: #fff;
            text-align: center;
            padding: 30px 10px;
            margin-top: 20px;
        }

        footer p {
            margin: 0;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .product-detail {
                flex-direction: column;
                align-items: center;
            }

            .product-detail img {
                max-width: 90%;
            }

            .info {
                width: 100%;
                padding: 15px;
            }

            .price {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>THE BRANDNAME RENTAL</h1>
        </div>
    </header>

    <main>
        <div class="container">
            <h2>รายละเอียดสินค้า</h2>

            <?php if (isset($product)): ?>
                <div class="product-detail">
                    <img src="images/<?php echo $product['product_id']; ?>.png" alt="<?php echo $product['brand']; ?>">
                    <div class="info">
                        <h3><?php echo $product['product_id']; ?></h3>
                        <h3><?php echo $product['brand']; ?></h3>
                        <p><?php echo $product['details']; ?></p>
                        <p class="price"><strong>ราคาเช่า:</strong> <?php echo number_format($product['daily_rental_price'], 2); ?> บาท / วัน</p>

                        <form method="POST">
                            <label for="quantity">จำนวนวัน:</label>
                            <input type="number" name="quantity" id="quantity" min="1" value="1" required>
                            
                            <label for="rental_date">วัน/เดือน/ปีที่เช่า:</label>
                            <input type="date" name="rental_date" id="rental_date" required>
                            
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
