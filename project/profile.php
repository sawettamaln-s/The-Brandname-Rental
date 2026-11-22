<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

$employee_stmt = $conn->prepare("SELECT employees_id FROM employees ORDER BY RAND() LIMIT 1");
$employee_stmt->execute();
$employee_result = $employee_stmt->get_result();
$employee_stmt->close();

$customer_id = $_SESSION["customers_id"];


$stmt = $conn->prepare("SELECT customers_id, customers_firstname, customers_lastname, gender, customers_member,
phonenumber, email, address, postal_code FROM customers WHERE customers_id = ?");
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

$rental_stmt = $conn->prepare("SELECT rental_id, customers_id, product_id, employees_id, track_rentals,
rental_date, rental_days, deposit_fee, rental_status FROM rentals WHERE customers_id = ?");
$rental_stmt->bind_param("s", $customer_id);
$rental_stmt->execute();
$rental_result = $rental_stmt->get_result();
$rental_stmt->close();

$rental_stmt = $conn->prepare("
    SELECT t.rental_id, t.track_return, t.returnl_status, rs.return_date, 
    c.customers_firstname, c.customers_lastname
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    JOIN customers c ON r.customers_id = c.customers_id
    LEFT JOIN returnss rs ON t.rental_id = rs.rental_id  -- เชื่อมกับ returnss
    WHERE r.customers_id = ?
");


?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน</title>
</head>
<body>
<style>
    body {
        font-family: 'Prompt', sans-serif;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        margin: 0;
        padding: 0;
    }

    header {
        background-color: #34495e;
        color: #fff;
        padding: 30px 0;
        text-align: center;
        font-size: 1.8em;
        font-weight: bold;
        letter-spacing: 1px;
        margin-bottom: 30px;
    }

    .container {
        width: 85%;
        max-width: 1000px;
        margin: 30px auto;
        padding: 30px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin-bottom: 40px;
    }

    h2 {
        font-size: 1.8em;
        color: #333;
        margin-bottom: 30px;
    }

    .profile-box {
        font-size: 1.2em;
        text-align: left;
        margin-bottom: 30px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    .profile-box p {
        color: #555;
        margin: 10px 0;
    }

    .profile-box p strong {
        color: #222;
    }

    .btn {
        display: inline-block;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 1.1em;
        text-decoration: none;
        font-weight: bold;
        margin-top: 15px;
        transition: background-color 0.3s;
    }

    .btn-edit {
        background-color: #34495e;
        color: white;
    }

    .btn-edit:hover {
        background-color: #0056b3;
    }

    .btn-back {
        background-color: #34495e;
        color: white;
    }

    .btn-back:hover {
        background-color:  #0056b3;
    }

    .rental-status {
        margin-top: 30px;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    .rental-status table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .rental-status th,
    .rental-status td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    .rental-status th {
        background-color: #34495e;
        color: white;
    }

    .rental-status tr:nth-child(even) {
        background-color: #f1f1f1;
    }

    .rental-status tr:hover {
        background-color: #e9ecef;
    }

    .form-container {
        margin-top: 30px;
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
    }

    .form-container h3 {
        margin-bottom: 20px;
        color: #333;
    }

    .form-container form {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }

    .form-container label {
        font-size: 1.2em;
        color: #333;
        margin-bottom: 5px;
    }

    .form-container input {
        padding: 10px;
        font-size: 1.1em;
        border: 1px solid #ccc;
        border-radius: 8px;
        width: 100%;
        color: #333;
        flex: 2;
        margin-bottom: 15px;
    }

    .form-container button {
        padding: 12px 20px;
        font-size: 1.1em;
        background-color: #34495e;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-top: 15px;
    }

    .form-container button:hover {
        background-color: #0056b3;
    }

    footer {
        background-color: #34495e;
        color: #fff;
        text-align: center;
        padding: 20px 0;
        font-size: 1.1em;
        margin-top: 80px;
    }

</style>


<header>
    THE BRANDNAME RENTAL
</header>

<main>
    <div class="container">
        <h2>โปรไฟล์ของฉัน</h2>
        <div class="profile-box">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($customer['customers_id']); ?></p>
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($customer['customers_firstname'] . " " . $customer['customers_lastname']); ?></p>
            <p><strong>เพศ:</strong> <?php echo htmlspecialchars($customer['gender']); ?></p>
            <p><strong>ประเภทสมาชิก:</strong> <?php echo htmlspecialchars($customer['customers_member']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($customer['phonenumber']); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
            <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
            <p><strong>รหัสไปรษณีย์:</strong> <?php echo htmlspecialchars($customer['postal_code']); ?></p>

            <a href="edit_profile.php" class="btn btn-edit">แก้ไขข้อมูล</a>
            <a href="member.php" class="btn btn-edit">สมัคร member</a>
        </div>

        <div class="rental-status">
    <h3>สถานะการเช่า</h3>
    <?php if ($rental_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>รหัสการเช่า</th>
                <th>รหัสลูกค้า</th>
                <th>สินค้า</th>
                <th>พนักงาน</th>
                <th>รหัสส่งสินค้า</th>
                <th>วันที่เช่า</th>
                <th>จำนวนวัน</th>
                <th>ค่ามัดจำ</th>
                <th>สถานะ</th>
            </tr>
            <?php while ($rental = $rental_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rental['rental_id']; ?></td>
                    <td><?php echo $rental['customers_id']; ?></td>
                    <td><?php echo $rental['product_id']; ?></td>
                    <td><?php echo $rental['employees_id']; ?></td>
                    <td><?php echo htmlspecialchars($rental['track_rentals']); ?></td>
                    <td><?php echo htmlspecialchars($rental['rental_date']); ?></td>
                    <td><?php echo htmlspecialchars($rental['rental_days']); ?> วัน</td>
                    <td><?php echo htmlspecialchars($rental['deposit_fee']); ?></td>
                    <td>
                        <?php
                        if ($rental['rental_status'] == 'Pending') {
                            echo "<span style='color: orange;'>รอการยืนยัน</span>";
                        } elseif ($rental['rental_status'] == 'Complete') {
                            echo "<span style='color: green;'>สำเร็จ</span>";
                        } else {
                            echo "<span style='color: red;'>ถูกยกเลิก</span>";
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>ยังไม่มีการเช่าผลิตภัณฑ์ในปัจจุบัน</p>
    <?php endif; ?>
</div>

<div class="rental-status">
    <h3>สถานะการคืน</h3>
    <?php
    if (isset($_SESSION["customers_id"])) {
        $customer_id = $_SESSION["customers_id"];
    }

    $rental_stmt = $conn->prepare("
    SELECT t.rental_id, t.track_return, t.returnl_status, rs.return_date, 
    c.customers_firstname, c.customers_lastname
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    JOIN customers c ON r.customers_id = c.customers_id
    LEFT JOIN returnss rs ON t.rental_id = rs.rental_id  -- เชื่อมกับ returnss
    WHERE r.customers_id = ?
");

    $rental_stmt->bind_param("s", $customer_id);
    $rental_stmt->execute();
    $rental_result = $rental_stmt->get_result();
    ?>

    <?php if ($rental_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>รหัสการเช่า</th>
                <th>สถานะการคืน</th>
                <th>วันที่คืน</th>
                <th>ค่ามัดจำ</th>
            </tr>
            <?php while ($rental = $rental_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rental['rental_id']; ?></td>
                    <td><?php echo htmlspecialchars($rental['track_return']); ?></td>
                    <td><?php echo isset($rental['return_date']) ? $rental['return_date'] : '-'; ?></td>
                    <td>
                        <?php
                        if ($rental['returnl_status'] == 'Pending') {
                            echo "<span style='color: orange;'>รอการยืนยัน</span>";
                        } elseif ($rental['returnl_status'] == 'Complete') {
                            echo "<span style='color: green;'>สำเร็จ</span>";
                        } else {
                            echo "<span style='color: red;'>ถูกยกเลิก</span>";
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>ยังไม่มีการคืนสินค้าของคุณในขณะนี้</p>
    <?php endif; ?>
</div>

<div class="form-container">
    <h3>กรอกข้อมูลการคืนสินค้า</h3>
    <form action="process_return.php" method="POST">
        <label for="rental_id">รหัสการเช่า:</label>
        <input type="text" id="rental_id" name="rental_id" required><br>

        <label for="track_return">รหัสส่งสินค้า:</label>
        <input type="text" id="track_return" name="track_return" required><br>

        <label for="track_return">วันที่คืน:</label>
        <input type="text" id="return_date" name="return_date" required><br>

        <button type="submit" class="btn btn-edit">บันทึกการคืนสินค้า</button>
    </form>
</div>

    <a href="index.php" class="btn btn-back">ย้อนกลับสู่หน้าหลัก</a>
</main>

<footer>
    &copy; 2025 ระบบเช่า
</footer>
</body>
</html>