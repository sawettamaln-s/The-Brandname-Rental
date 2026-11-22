<?php
require_once 'db_connect.php';
require_admin();

// ยืนยัน (Complete) = ของสภาพดี คืนมัดจำครึ่งหนึ่ง, ชำรุด (Cancel) = ร้านยึดมัดจำทั้งหมด
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $rental_id = input('rental_id');
    $new_status = isset($_POST['confirm']) ? 'Complete' : (isset($_POST['cancel']) ? 'Cancel' : null);

    if ($new_status !== null) {
        $update_stmt = $conn->prepare("UPDATE track_return SET returnl_status = ? WHERE rental_id = ? AND returnl_status = 'Pending'");
        $update_stmt->bind_param("ss", $new_status, $rental_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            flash($new_status === 'Complete' ? "บันทึก $rental_id: สภาพดี คืนมัดจำครึ่งหนึ่ง" : "บันทึก $rental_id: ชำรุด ยึดมัดจำทั้งหมด");
        } else {
            flash("ไม่สามารถเปลี่ยนสถานะได้ หรือรายการไม่อยู่ในสถานะรอตรวจ");
        }
        $update_stmt->close();
    }
    redirect("admin_return.php");
}

$stmt = $conn->prepare("SELECT t.rental_id, r.customers_id, r.product_id, p.brand, r.deposit_fee, t.track_return, rs.return_date,
    c.customers_firstname, c.customers_lastname
    FROM track_return t
    JOIN rentals r ON t.rental_id = r.rental_id
    JOIN customers c ON r.customers_id = c.customers_id
    LEFT JOIN products p ON p.product_id = r.product_id
    LEFT JOIN returnss rs ON rs.rental_id = t.rental_id
    WHERE t.returnl_status = 'Pending'
    ORDER BY t.rental_id");
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
        <p>ตรวจสภาพกระเป๋าเมื่อได้รับของ สภาพดีกด "สภาพดี" (คืนมัดจำครึ่งหนึ่ง) ชำรุดกด "ชำรุด" (ยึดมัดจำทั้งหมด)</p>

        <?php if ($message): ?>
            <div class="alert"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($rental_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>รหัสการเช่า</th>
                    <th>ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>เลขพัสดุที่ส่งคืน</th>
                    <th>วันที่แจ้งส่งคืน</th>
                    <th>ค่ามัดจำ</th>
                    <th>จัดการ</th>
                </tr>
                <?php while ($rental = $rental_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($rental['rental_id']); ?></td>
                        <td><?php echo e($rental['customers_id'] . ' ' . $rental['customers_firstname'] . ' ' . $rental['customers_lastname']); ?></td>
                        <td><?php echo e($rental['product_id'] . ' ' . $rental['brand']); ?></td>
                        <td><?php echo e($rental['track_return']); ?></td>
                        <td><?php echo e($rental['return_date'] ?? '-'); ?></td>
                        <td><?php echo number_format((int) $rental['deposit_fee']); ?> บาท</td>
                        <td>
                            <form action="admin_return.php" method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="confirm" class="btn btn-confirm">สภาพดี</button>
                            </form>

                            <form action="admin_return.php" method="POST" style="display: inline;" onsubmit="return confirm('บันทึกว่าสินค้าชำรุดและยึดมัดจำทั้งหมด?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rental_id" value="<?php echo e($rental['rental_id']); ?>">
                                <button type="submit" name="cancel" class="btn btn-cancel">ชำรุด</button>
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
