<?php
require_once 'includes/db.php';

$current = current_customer($conn);
$customer_id = $current['customers_id'];
$error = '';
// โหมดเดโม: บัญชีทดลองเปลี่ยนอีเมลไม่ได้ เพื่อให้คนต่อไปยังล็อกอินด้วยอีเมลบนแถบเดโมได้
$email_locked = is_seed_account($customer_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $firstname = input('firstname');
    $lastname = input('lastname');
    $phonenumber = input('phonenumber');
    $email = $email_locked ? $current['email'] : input('email');
    $address = input('address');
    $postal_code = input('postal_code');

    $stmt = $conn->prepare("SELECT 1 FROM customers WHERE email = ? AND customers_id <> ?");
    $stmt->bind_param("ss", $email, $customer_id);
    $stmt->execute();
    $email_taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($firstname === '' || $lastname === '' || $address === '') {
        $error = "กรุณากรอกข้อมูลให้ครบค่ะ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้องค่ะ";
    } elseif ($email_taken) {
        $error = "อีเมลนี้มีบัญชีอื่นใช้อยู่แล้วค่ะ";
    } elseif (!preg_match('/^[0-9\-+ ]{9,15}$/', $phonenumber)) {
        $error = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้องค่ะ";
    } elseif (!preg_match('/^[0-9]{5}$/', $postal_code)) {
        $error = "รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลักค่ะ";
    } else {
        $update_stmt = $conn->prepare("UPDATE customers SET customers_firstname = ?, customers_lastname = ?,
        phonenumber = ?, email = ?, address = ?, postal_code = ? WHERE customers_id = ?");
        $update_stmt->bind_param("sssssss", $firstname, $lastname, $phonenumber, $email, $address, $postal_code, $customer_id);
        $update_stmt->execute();
        $update_stmt->close();
        $_SESSION["customers_name"] = $firstname . " " . $lastname;
        flash("บันทึกข้อมูลเรียบร้อยแล้วค่ะ");
        redirect("profile.php");
    }
}

$user = find_customer($conn, $customer_id);
if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}
// เมื่อบันทึกไม่ผ่าน แสดงค่าที่เพิ่งกรอก ไม่ใช่ค่าเดิมในฐานข้อมูล
$value = fn(string $field, string $column) => e($_SERVER['REQUEST_METHOD'] === 'POST' ? input($field) : $user[$column]);

page_start('แก้ไขข้อมูล', 'shop', 'profile');
?>
<div class="card page-narrow">
    <h1>แก้ไขข้อมูล</h1>
    <?php error_alert($error); ?>
    <form action="edit_profile.php" method="POST" class="form-grid two-cols">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="firstname">ชื่อ</label>
            <input type="text" id="firstname" name="firstname" maxlength="50" value="<?php echo $value('firstname', 'customers_firstname'); ?>" required>
        </div>
        <div class="field">
            <label for="lastname">นามสกุล</label>
            <input type="text" id="lastname" name="lastname" maxlength="50" value="<?php echo $value('lastname', 'customers_lastname'); ?>" required>
        </div>
        <div class="field">
            <label for="phonenumber">เบอร์โทร</label>
            <input type="text" id="phonenumber" name="phonenumber" maxlength="15" value="<?php echo $value('phonenumber', 'phonenumber'); ?>" required>
        </div>
        <div class="field full">
            <label for="email">อีเมล</label>
            <?php if ($email_locked): ?>
                <input type="email" id="email" value="<?php echo e($user['email']); ?>" readonly>
                <div class="hint">บัญชีทดลองเปลี่ยนอีเมลไม่ได้ค่ะ</div>
            <?php else: ?>
                <input type="email" id="email" name="email" maxlength="100" value="<?php echo $value('email', 'email'); ?>" required>
            <?php endif; ?>
        </div>
        <div class="field full">
            <label for="address">ที่อยู่จัดส่ง</label>
            <input type="text" id="address" name="address" maxlength="200" value="<?php echo $value('address', 'address'); ?>" required>
        </div>
        <div class="field">
            <label for="postal_code">รหัสไปรษณีย์</label>
            <input type="text" id="postal_code" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="<?php echo $value('postal_code', 'postal_code'); ?>" required>
        </div>
        <div class="full actions">
            <button type="submit" class="btn">บันทึก</button>
            <a href="profile.php" class="btn btn-outline">ยกเลิก</a>
        </div>
    </form>
</div>
<?php page_end(); ?>
