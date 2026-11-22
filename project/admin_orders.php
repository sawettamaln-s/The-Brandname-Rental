<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirm'])) {
        $rental_id = $_POST['rental_id'];

        $update_stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Complete' WHERE rental_id = ? AND rental_status = 'Pending'");
        $update_stmt->bind_param("s", $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            $_SESSION['message'] = "ยืนยันการเช่าเรียบร้อยแล้ว";
        } else {
            $_SESSION['message'] = "ไม่สามารถยืนยันการเช่าได้ หรือคำสั่งซื้อไม่อยู่ในสถานะ Pending";
        }
        $update_stmt->close();
    }

    if (isset($_POST['cancel'])) {
        $rental_id = $_POST['rental_id'];

        $update_stmt = $conn->prepare("UPDATE rentals SET rental_status = 'Cancel' WHERE rental_id = ? AND rental_status = 'Pending'");
        $update_stmt->bind_param("s", $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            $_SESSION['message'] = "ยกเลิกการเช่าเรียบร้อยแล้ว";
        } else {
            $_SESSION['message'] = "ไม่สามารถยกเลิกการเช่าได้ หรือคำสั่งซื้อไม่อยู่ในสถานะ Pending";
        }
        $update_stmt->close();
    }

    header("Location: admin_orders.php");
    exit();
}

$stmt = $conn->prepare("SELECT rental_id, customers_id, product_id, rental_date, rental_days, deposit_fee, rental_status FROM rentals WHERE rental_status = 'Pending'");
$stmt->execute();
$rental_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>

<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>


<main>
    <div class="container">
        <h2>จัดการคำสั่งซื้อ</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($rental_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>รหัสการเช่า</th>
                    <th>รหัสลูกค้า</th>
                    <th>สินค้า</th>
                    <th>วันที่เช่า</th>
                    <th>จำนวนวัน</th>
                    <th>ค่ามัดจำ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
                <?php while ($rental = $rental_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rental['rental_id']; ?></td>
                        <td><?php echo $rental['customers_id']; ?></td>
                        <td><?php echo $rental['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($rental['rental_date']); ?></td>
                        <td><?php echo htmlspecialchars($rental['rental_days']); ?> วัน</td>
                        <td><?php echo htmlspecialchars($rental['deposit_fee']); ?></td>
                        <td>
                            <?php echo $rental['rental_status'] == 'Pending' ? "<span style='color: orange;'>รอการยืนยัน</span>" : ""; ?>
                        </td>
                        <td>

                            <form action="admin_orders.php" method="POST" style="display: inline;">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <button type="submit" name="confirm" class="btn btn-confirm" <?php echo $rental['rental_status'] != 'Pending' ? 'disabled' : ''; ?>>ยืนยัน</button>
                            </form>

                            <form action="admin_orders.php" method="POST" style="display: inline;">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <button type="submit" name="cancel" class="btn btn-cancel" <?php echo $rental['rental_status'] != 'Pending' ? 'disabled' : ''; ?>>ยกเลิก</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>ไม่มีคำสั่งซื้อที่รอการยืนยัน</p>
        <?php endif; ?>

        <a href="index_admin.php" class="btn btn-back">กลับหน้าแรก</a>
    </div>
</main>


</body>
</html>
