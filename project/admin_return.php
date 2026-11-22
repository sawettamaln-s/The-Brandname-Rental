<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirm'])) {
        $rental_id = $_POST['rental_id'];

        $update_stmt = $conn->prepare("UPDATE track_return SET returnl_status = 'Complete' WHERE rental_id = ? AND returnl_status = 'Pending'");
        $update_stmt->bind_param("s", $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            $_SESSION['message'] = "ยืนยันการคืนสินค้าเรียบร้อยแล้ว";
        } else {
            $_SESSION['message'] = "ไม่สามารถยืนยันการคืนสินค้าได้ หรือคำสั่งซื้อไม่อยู่ในสถานะ Pending";
        }
        $update_stmt->close();
    }

    if (isset($_POST['cancel'])) {
        $rental_id = $_POST['rental_id'];

        $update_stmt = $conn->prepare("UPDATE track_return SET returnl_status = 'Cancel' WHERE rental_id = ? AND returnl_status = 'Pending'");
        $update_stmt->bind_param("s", $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            $_SESSION['message'] = "ยกเลิกการคืนสินค้าเรียบร้อยแล้ว";
        } else {
            $_SESSION['message'] = "ไม่สามารถยกเลิกการคืนสินค้าได้ หรือคำสั่งซื้อไม่อยู่ในสถานะ Pending";
        }
        $update_stmt->close();
    }

    header("Location: admin_return.php");
    exit();
}

$stmt = $conn->prepare("SELECT t.rental_id, r.customers_id, r.product_id, t.track_return, t.returnl_status, c.customers_firstname, c.customers_lastname
                        FROM track_return t
                        JOIN rentals r ON t.rental_id = r.rental_id
                        JOIN customers c ON r.customers_id = c.customers_id
                        WHERE t.returnl_status = 'Pending'");
$stmt->execute();
$rental_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสถานะการคืนสินค้า</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>

<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>


<main>
    <div class="container">
        <h2>จัดการสถานะการคืนสินค้า</h2>

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
                    <th>ชื่อ-นามสกุลลูกค้า</th>
                    <th>สินค้า</th>
                    <th>รหัสส่งสินค้า</th>
                    <th>สถานะการคืน</th>
                    <th>จัดการ</th>
                </tr>
                <?php while ($rental = $rental_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rental['rental_id']; ?></td>
                        <td><?php echo $rental['customers_id']; ?></td>
                        <td><?php echo $rental['customers_firstname'] . " " . $rental['customers_lastname']; ?></td>
                        <td><?php echo $rental['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($rental['track_return']); ?></td>
                        <td>
                            <?php echo $rental['returnl_status'] == 'Pending' ? "<span style='color: orange;'>รอการยืนยัน</span>" : ""; ?>
                        </td>
                        <td>
                            <form action="admin_return.php" method="POST" style="display: inline;">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <button type="submit" name="confirm" class="btn btn-confirm" <?php echo $rental['returnl_status'] != 'Pending' ? 'disabled' : ''; ?>>ยืนยันการคืน</button>
                            </form>

                            <form action="admin_return.php" method="POST" style="display: inline;">
                                <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                <button type="submit" name="cancel" class="btn btn-cancel" <?php echo $rental['returnl_status'] != 'Pending' ? 'disabled' : ''; ?>>ยกเลิกการคืน</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>ไม่มีคำสั่งคืนสินค้าที่รอการยืนยัน</p>
        <?php endif; ?>

        <a href="index_admin.php" class="btn btn-back">กลับหน้าแรก</a>
    </div>
</main>

</body>
</html>
