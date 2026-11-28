<?php
require_once '../includes/db.php';
require_admin();

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $brand = input('brand');
    $details = input('details');
    $daily_rental_price = filter_var($_POST["daily_rental_price"] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $has_image = ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($brand === '' || $details === '') {
        $error = "กรุณากรอกยี่ห้อและรายละเอียดสินค้า";
    } elseif ($daily_rental_price === false) {
        $error = "ราคาเช่าต้องเป็นจำนวนเต็มมากกว่า 0";
    } else {
        // สร้างรหัสตอนบันทึก เพื่อไม่ให้รหัสซ้ำเมื่อมีการเพิ่มสินค้าจากหลายหน้าพร้อมกัน
        $product_id = next_id($conn, 'products', 'product_id', 'PD', 4);
        $image = null;
        $saved = [];
        if ($has_image) {
            // รูปตั้งชื่อตามรหัสสินค้า นามสกุลมาจากชนิดไฟล์ที่ตรวจได้
            $saved = save_image($_FILES['image'], PRODUCT_IMAGE_DIR, $product_id);
            if (isset($saved['error'])) {
                $error = $saved['error'];
            } else {
                $image = $saved['file'];
            }
        }

        if ($error === '') {
            try {
                $stmt = $conn->prepare("INSERT INTO products (product_id, brand, details, daily_rental_price, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $product_id, $brand, $details, $daily_rental_price, $image);
                $stmt->execute();
                $stmt->close();
                $note = !empty($saved['demo']) ? ' (โหมดเดโมไม่เก็บรูปที่อัปโหลด จึงแสดงรูปตัวแทน)' : '';
                flash("เพิ่มสินค้า $product_id เรียบร้อยแล้ว" . $note);
                redirect("products.php");
            } catch (mysqli_sql_exception $e) {
                if ($image) {
                    @unlink(PRODUCT_IMAGE_DIR . '/' . $image);
                }
                error_log('product add failed: ' . $e->getMessage());
                $error = "บันทึกสินค้าไม่สำเร็จ กรุณาลองใหม่";
            }
        }
    }
}

$next_product_id = next_id($conn, 'products', 'product_id', 'PD', 4);

page_start('เพิ่มสินค้า', 'admin', 'products');
?>
<div class="card page-narrow">
    <h1>เพิ่มสินค้าใหม่</h1>
    <?php error_alert($error); ?>
    <form method="POST" enctype="multipart/form-data" class="form-grid two-cols">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label>รหัสสินค้า (อัตโนมัติ)</label>
            <input type="text" value="<?php echo e($next_product_id); ?>" disabled>
        </div>
        <div class="field">
            <label for="daily_rental_price">ราคาเช่ารายวัน (บาท)</label>
            <input type="number" id="daily_rental_price" name="daily_rental_price" min="1" step="1" value="<?php echo e(input('daily_rental_price')); ?>" required>
        </div>
        <div class="field">
            <label for="brand">ยี่ห้อ</label>
            <input type="text" id="brand" name="brand" maxlength="50" value="<?php echo e(input('brand')); ?>" required>
        </div>
        <div class="field">
            <label for="details">รุ่น/รายละเอียด</label>
            <input type="text" id="details" name="details" maxlength="100" value="<?php echo e(input('details')); ?>" required>
        </div>
        <div class="field full">
            <label for="image">รูปสินค้า</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <div class="hint">JPG, PNG หรือ WEBP ไม่เกิน 2 MB (ไม่ใส่ก็ได้ จะแสดงรูปตัวแทน)<?php echo demo_mode() ? ' · โหมดเดโมจะตรวจไฟล์แต่ไม่เก็บรูปจริง' : ''; ?></div>
        </div>
        <div class="full actions">
            <button type="submit" class="btn">เพิ่มสินค้า</button>
            <a href="products.php" class="btn btn-outline">ยกเลิก</a>
        </div>
    </form>
</div>
<?php page_end(); ?>
