<?php
require_once 'db_connect.php';

$customer_id = require_customer();
$customer = find_customer($conn, $customer_id);
if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}

$rental_stmt = $conn->prepare("SELECT r.rental_id, r.product_id, p.brand, r.employees_id, r.track_rentals,
    r.rental_date, r.rental_days, r.deposit_fee, r.rental_status
    FROM rentals r LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.customers_id = ? ORDER BY r.rental_id DESC");
$rental_stmt->bind_param("s", $customer_id);
$rental_stmt->execute();
$rental_result = $rental_stmt->get_result();
$rental_stmt->close();

$return_stmt = $conn->prepare("
    SELECT t.rental_id, t.track_return, t.returnl_status, rs.return_date, r.deposit_fee
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    LEFT JOIN returnss rs ON t.rental_id = rs.rental_id
    WHERE r.customers_id = ? ORDER BY t.rental_id DESC
");
$return_stmt->bind_param("s", $customer_id);
$return_stmt->execute();
$return_result = $return_stmt->get_result();
$return_stmt->close();

// รายการที่ส่งคืนได้: ร้านยืนยันการเช่าแล้ว และยังไม่เคยแจ้งเลขพัสดุคืน
$returnable_stmt = $conn->prepare("
    SELECT r.rental_id, p.brand, p.details FROM rentals r
    LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.customers_id = ? AND r.rental_status = 'Complete'
      AND NOT EXISTS (SELECT 1 FROM track_return t WHERE t.rental_id = r.rental_id)
    ORDER BY r.rental_id
");
$returnable_stmt->bind_param("s", $customer_id);
$returnable_stmt->execute();
$returnable = $returnable_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$returnable_stmt->close();

$request_stmt = $conn->prepare("SELECT status FROM membership_requests WHERE customers_id = ? ORDER BY request_id DESC LIMIT 1");
$request_stmt->bind_param("s", $customer_id);
$request_stmt->execute();
$last_request = $request_stmt->get_result()->fetch_assoc();
$request_stmt->close();
$member_pending = !is_member($customer) && ($last_request['status'] ?? '') === 'Pending';

$message = take_flash();
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
        <?php if ($message): ?>
            <p style="background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 6px; font-weight: bold;"><?php echo e($message); ?></p>
        <?php endif; ?>
        <div class="profile-box">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($customer['customers_id']); ?></p>
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($customer['customers_firstname'] . " " . $customer['customers_lastname']); ?></p>
            <p><strong>เพศ:</strong> <?php echo htmlspecialchars($customer['gender']); ?></p>
            <p><strong>ประเภทสมาชิก:</strong>
                <?php if (is_member($customer)): ?>
                    สมาชิก<?php echo $customer['member_until'] ? ' (ถึง ' . e($customer['member_until']) . ')' : ''; ?>
                <?php elseif ($member_pending): ?>
                    ทั่วไป · คำขอสมัครสมาชิกรอร้านตรวจสอบ
                <?php else: ?>
                    ทั่วไป
                <?php endif; ?>
            </p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($customer['phonenumber']); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
            <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
            <p><strong>รหัสไปรษณีย์:</strong> <?php echo htmlspecialchars($customer['postal_code']); ?></p>

            <a href="edit_profile.php" class="btn btn-edit">แก้ไขข้อมูล</a>
            <?php if (!is_member($customer) && !$member_pending): ?>
                <a href="member.php" class="btn btn-edit">สมัคร member</a>
            <?php endif; ?>
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
                    <td><?php echo e($rental['rental_id']); ?></td>
                    <td><?php echo e($customer_id); ?></td>
                    <td><?php echo e($rental['product_id'] . ' ' . $rental['brand']); ?></td>
                    <td><?php echo e($rental['employees_id']); ?></td>
                    <td><?php echo e($rental['track_rentals']); ?></td>
                    <td><?php echo e($rental['rental_date']); ?></td>
                    <td><?php echo e($rental['rental_days']); ?> วัน</td>
                    <td><?php echo number_format((int) $rental['deposit_fee']); ?> บาท</td>
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
    <?php if ($return_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>รหัสการเช่า</th>
                <th>เลขพัสดุที่ส่งคืน</th>
                <th>วันที่แจ้งส่งคืน</th>
                <th>สถานะ</th>
                <th>มัดจำที่ได้คืน</th>
            </tr>
            <?php while ($return = $return_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo e($return['rental_id']); ?></td>
                    <td><?php echo e($return['track_return']); ?></td>
                    <td><?php echo e($return['return_date'] ?? '-'); ?></td>
                    <td>
                        <?php
                        if ($return['returnl_status'] == 'Pending') {
                            echo "<span style='color: orange;'>รอร้านตรวจสภาพ</span>";
                        } elseif ($return['returnl_status'] == 'Complete') {
                            echo "<span style='color: green;'>สภาพดี</span>";
                        } else {
                            echo "<span style='color: red;'>ชำรุด</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // สภาพดี คืนมัดจำครึ่งหนึ่ง, ชำรุด ร้านยึดมัดจำทั้งหมด
                        if ($return['returnl_status'] == 'Pending') {
                            echo '-';
                        } else {
                            $refund = $return['returnl_status'] == 'Complete' ? intdiv((int) $return['deposit_fee'], 2) : 0;
                            echo number_format($refund) . ' บาท';
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
    <h3>แจ้งส่งคืนสินค้า</h3>
    <?php if (count($returnable) > 0): ?>
    <form action="process_return.php" method="POST">
        <?php echo csrf_field(); ?>
        <label for="rental_id">รายการเช่าที่จะคืน:</label>
        <select id="rental_id" name="rental_id" required>
            <?php foreach ($returnable as $item): ?>
                <option value="<?php echo e($item['rental_id']); ?>"><?php echo e($item['rental_id'] . ' · ' . $item['brand'] . ' ' . $item['details']); ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="track_return">เลขพัสดุที่ส่งคืน:</label>
        <input type="text" id="track_return" name="track_return" maxlength="50" pattern="[A-Za-z0-9&\-]{8,50}" title="ตัวอักษรภาษาอังกฤษหรือตัวเลข 8–50 ตัว" required><br>

        <button type="submit" class="btn btn-edit">บันทึกการคืนสินค้า</button>
    </form>
    <?php else: ?>
        <p>ยังไม่มีรายการที่ต้องส่งคืน (ส่งคืนได้เมื่อร้านยืนยันการเช่าแล้ว)</p>
    <?php endif; ?>
</div>

    <a href="index.php" class="btn btn-back">ย้อนกลับสู่หน้าหลัก</a>
</main>

<footer>
    &copy; 2025 ระบบเช่า
</footer>
</body>
</html>