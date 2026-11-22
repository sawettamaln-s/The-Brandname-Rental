<?php
require_once 'db_connect.php';
require_admin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $new_status = isset($_POST['confirm']) ? 'Complete' : (isset($_POST['cancel']) ? 'Cancel' : null);

    if ($new_status !== null) {
        $update_stmt = $conn->prepare("UPDATE rentals SET rental_status = ? WHERE rental_id = ? AND rental_status = 'Pending'");
        $update_stmt->bind_param("ss", $new_status, $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            flash($new_status === 'Complete' ? "ยืนยันการเช่า $rental_id เรียบร้อยแล้ว" : "ยกเลิกการเช่า $rental_id เรียบร้อยแล้ว");
        } else {
            flash("ไม่สามารถเปลี่ยนสถานะได้ หรือคำสั่งเช่าไม่อยู่ในสถานะรอยืนยัน");
        }
        $update_stmt->close();
    }
    redirect("admin_orders.php");
}

$stmt = $conn->prepare("SELECT r.rental_id, r.customers_id, c.customers_firstname, c.customers_lastname,
    r.product_id, p.brand, p.daily_rental_price, r.rental_date, r.rental_days, r.deposit_fee
    FROM rentals r
    JOIN customers c ON c.customers_id = r.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id
    WHERE r.rental_status = 'Pending'
    ORDER BY r.rental_id");
$stmt->execute();
$rental_result = $stmt->get_result();
$stmt->close();

$message = take_flash();
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
        <p>ตรวจยอดโอนของลูกค้าก่อนกดยืนยัน ยอดที่ต้องได้รับ = ค่าเช่า + ค่ามัดจำ</p>

        <?php if ($message): ?>
            <div class="alert"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($rental_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>รหัสการเช่า</th>
                    <th>ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>วันที่เช่า</th>
                    <th>จำนวนวัน</th>
                    <th>ค่าเช่า</th>
                    <th>ค่ามัดจำ</th>
                    <th>ยอดรวม</th>
                    <th>จัดการ</th>
                </tr>
                <?php while ($rental = $rental_result->fetch_assoc()):
                    $rent = (int) $rental['daily_rental_price'] * (int) $rental['rental_days'];
                ?>
                    <tr>
                        <td><?php echo e($rental['rental_id']); ?></td>
                        <td><?php echo e($rental['customers_id'] . ' ' . $rental['customers_firstname'] . ' ' . $rental['customers_lastname']); ?></td>
                        <td><?php echo e($rental['product_id'] . ' ' . $rental['brand']); ?></td>
                        <td><?php echo e($rental['rental_date']); ?></td>
                        <td><?php echo e($rental['rental_days']); ?> วัน</td>
                        <td><?php echo number_format($rent); ?></td>
                        <td><?php echo number_format((int) $rental['deposit_fee']); ?></td>
                        <td><?php echo number_format($rent + (int) $rental['deposit_fee']); ?> บาท</td>
                        <td>
                            <form action="admin_orders.php" method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="confirm" class="btn btn-confirm">ยืนยัน</button>
                            </form>

                            <form action="admin_orders.php" method="POST" style="display: inline;" onsubmit="return confirm('ยกเลิกคำสั่งเช่านี้?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="cancel" class="btn btn-cancel">ยกเลิก</button>
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
