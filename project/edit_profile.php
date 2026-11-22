<?php
require_once 'includes/db.php';

$customer_id = require_customer();
$genders = ['Male' => 'ชาย', 'Female' => 'หญิง', 'Other' => 'อื่น ๆ'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $firstname = input('firstname');
    $lastname = input('lastname');
    $gender = input('gender');
    $phonenumber = input('phonenumber');
    $email = input('email');
    $address = input('address');
    $postal_code = input('postal_code');

    $stmt = $conn->prepare("SELECT 1 FROM customers WHERE email = ? AND customers_id <> ?");
    $stmt->bind_param("ss", $email, $customer_id);
    $stmt->execute();
    $email_taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($firstname === '' || $lastname === '' || $address === '') {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } elseif (!isset($genders[$gender])) {
        $error = "กรุณาเลือกเพศ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif ($email_taken) {
        $error = "อีเมลนี้มีผู้ใช้อื่นใช้แล้ว";
    } elseif (!preg_match('/^[0-9\-+ ]{9,15}$/', $phonenumber)) {
        $error = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง";
    } elseif (!preg_match('/^[0-9]{5}$/', $postal_code)) {
        $error = "รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก";
    } else {
        $update_stmt = $conn->prepare("UPDATE customers SET customers_firstname = ?, customers_lastname = ?,
        gender = ?, phonenumber = ?, email = ?, address = ?, postal_code = ? WHERE customers_id = ?");
        $update_stmt->bind_param("ssssssss", $firstname, $lastname, $gender, $phonenumber, $email, $address, $postal_code, $customer_id);
        $update_stmt->execute();
        $update_stmt->close();
        $_SESSION["customers_name"] = $firstname . " " . $lastname;
        flash("บันทึกข้อมูลเรียบร้อย");
        redirect("profile.php");
    }
}

$user = find_customer($conn, $customer_id);
if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}
// เมื่อบันทึกไม่ผ่าน แสดงค่าที่เพิ่งกรอก ไม่ใช่ค่าเดิมในฐานข้อมูล
$value = fn(string $field, string $column) => e($_SERVER['REQUEST_METHOD'] === 'POST' ? input($field) : $user[$column]);
$current_gender = $_SERVER['REQUEST_METHOD'] === 'POST' ? input('gender') : $user['gender'];

page_start('แก้ไขข้อมูลส่วนตัว', 'shop', 'profile');
?>
<div class="card page-narrow">
    <h1>แก้ไขข้อมูลส่วนตัว</h1>
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
            <label for="gender">เพศ</label>
            <select id="gender" name="gender" required>
                <?php foreach ($genders as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $current_gender === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="phonenumber">เบอร์โทร</label>
            <input type="text" id="phonenumber" name="phonenumber" maxlength="15" value="<?php echo $value('phonenumber', 'phonenumber'); ?>" required>
        </div>
        <div class="field full">
            <label for="email">อีเมล</label>
            <input type="email" id="email" name="email" maxlength="100" value="<?php echo $value('email', 'email'); ?>" required>
        </div>
        <div class="field full">
            <label for="address">ที่อยู่</label>
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
