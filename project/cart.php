<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customers_id"];
$stmt = $conn->prepare("SELECT customers_id, customers_firstname, customers_lastname,
gender, customers_member, phonenumber, email, address, postal_code FROM customers WHERE customers_id = ?");
$stmt->bind_param("s", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $customer = $result->fetch_assoc();
} else {
    echo "ไม่พบข้อมูลลูกค้า";
    exit();
}
$stmt->close();

if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $remove_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }

    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ระบบเช่า</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="cart_styles.css">
    <style>
        h3 {
            color: #2c3e50;
            text-align: center;
            margin-top: 10px;
            font-size: 28px;
            font-weight: bold;
        }
        h2 {
            color: #2c3e50;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #34495e;
            color: white;
        }
        .remove-btn {
            display: inline-block;
            padding: 6px 12px;
            color: white;
            background-color: #e74c3c;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        .remove-btn:hover {
            background-color: #c0392b;
        }
        .checkout-btn {
            display: block;
            width: 15%;
            text-align: center;
            padding: 12px;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: 0.3s;
        }
        .checkout-btn:hover {
            background-color: #219150;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin: 20px auto;
            padding: 10px;
            background-color: #7f8c8d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            width: 200px;
        }
        .back-btn:hover {
            background-color: #636e72;
        }
        footer {
            background-color:#34495e;
            color: #fff;
            text-align: center;
            padding: 10px 10px;
            margin-top: 10px;
        }

        footer p {
            margin: 0;
            font-size: 1.1em;
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
        <h3>ตะกร้าสินค้า</h3>

        <div class="card customer-info">
                <h2>ข้อมูลลูกค้า</h2>
                <p><strong>ID:</strong> <?php echo htmlspecialchars($customer['customers_id']); ?></p>
                <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($customer['customers_firstname'] . " " . $customer['customers_lastname']); ?></p>
                <p><strong>เพศ:</strong> <?php echo htmlspecialchars($customer['gender']); ?></p>
                <p><strong>ประเภทสมาชิก:</strong> <?php echo htmlspecialchars($customer['customers_member']); ?></p>
                <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($customer['phonenumber']); ?></p>
                <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
                <p><strong>รหัสไปรษณีย์:</strong> <?php echo htmlspecialchars($customer['postal_code']); ?></p>
            </div>

            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <table>
                    <tr>
                        <th>สินค้า</th>
                        <th>จำนวน</th>
                        <th>วันเช่า</th>
                        <th>ราคา</th>
                        <th>รวม</th>
                        <th>ลบ</th>
                    </tr>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $item):
                        $total += $item['product_price'] * $item['quantity'];
                    ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['rental_date']; ?></td>
                            <td><?php echo number_format($item['product_price'], 2); ?> บาท</td>
                            <td><?php echo number_format($item['product_price'] * $item['quantity'], 2); ?> บาท</td>
                            <td>
                                <a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="remove-btn">ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    if ($customer['customers_member'] === 'no') {
                        echo "<tr><td colspan='5' style='text-align:right;'>ค่ามัดจำ: -900 บาท</td></tr>";
                        $total += 900;
                    }
                    ?>

                    <tr>
                        <td colspan="3" style="text-align:right; font-weight: bold;">รวมทั้งหมด:</td>
                        <td colspan="2" style="text-align:right;"><?php echo number_format($total, 2); ?> บาท</td>
                    </tr>
                </table>

                <div class="total">
                    <div class="checkout-container">
                        <a href="checkout.php" class="checkout-btn">ชำระเงิน</a>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <p>ตะกร้าสินค้าของคุณว่างเปล่า</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <a href="index.php" class="back-btn">ย้อนกลับสู่หน้าหลัก</a>

    <footer>
        <div class="container">
            <p>&copy; 2025 ระบบเช่า</p>
        </div>
    </footer>
</body>
</html>
