<?php
require_once 'includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $firstname = input('firstname');
    $lastname = input('lastname');
    $email = input('email');
    $phonenumber = input('phonenumber');
    $address = input('address');
    $postal_code = input('postal_code');
    $password = input('password', false);

    if ($firstname === '' || $lastname === '' || $address === '') {
        $error = "กรุณากรอกข้อมูลให้ครบค่ะ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้องค่ะ";
    } elseif (strlen($password) < 8) {
        $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษรค่ะ";
    } elseif (!preg_match('/^[0-9\-+ ]{9,15}$/', $phonenumber)) {
        $error = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้องค่ะ";
    } elseif (!preg_match('/^[0-9]{5}$/', $postal_code)) {
        $error = "รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลักค่ะ";
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = "อีเมลนี้มีบัญชีอยู่แล้วค่ะ";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $new_id = next_id($conn, 'customers', 'customers_id', 'C', 3);
            $customers_member = 'no';

            $stmt = $conn->prepare("INSERT INTO customers (customers_id, customers_firstname, customers_lastname,
            customers_member, email, phonenumber, address, postal_code, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $new_id, $firstname, $lastname, $customers_member, $email, $phonenumber, $address, $postal_code, $hashed_password);
            $stmt->execute();
            $stmt->close();
            flash("สร้างบัญชีเรียบร้อยแล้ว เข้าสู่ระบบได้เลยค่ะ");
            redirect("login.php");
        }
    }
}
$old = fn($key) => e(input($key));

page_start('สร้างบัญชี', 'shop', 'register');
?>
<div class="card auth-card wide">
    <div class="auth-kicker">Welcome</div>
    <h1>สร้างบัญชี</h1>
    <?php error_alert($error); ?>
    <form method="POST" class="form-grid two-cols">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="firstname">ชื่อ</label>
            <input type="text" name="firstname" id="firstname" maxlength="50" value="<?php echo $old('firstname'); ?>" required>
        </div>
        <div class="field">
            <label for="lastname">นามสกุล</label>
            <input type="text" name="lastname" id="lastname" maxlength="50" value="<?php echo $old('lastname'); ?>" required>
        </div>
        <div class="field">
            <label for="email">อีเมล</label>
            <input type="email" name="email" id="email" maxlength="100" value="<?php echo $old('email'); ?>" required>
        </div>
        <div class="field">
            <label for="password">รหัสผ่าน</label>
            <input type="password" name="password" id="password" minlength="8" required>
            <div class="hint">อย่างน้อย 8 ตัวอักษร</div>
        </div>
        <div class="field">
            <label for="phonenumber">เบอร์โทรศัพท์</label>
            <input type="tel" name="phonenumber" id="phonenumber" maxlength="15" value="<?php echo $old('phonenumber'); ?>" required>
        </div>
        <div class="field">
            <label for="postal_code">รหัสไปรษณีย์</label>
            <input type="text" name="postal_code" id="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="<?php echo $old('postal_code'); ?>" required>
        </div>
        <div class="field full">
            <label for="address">ที่อยู่จัดส่ง</label>
            <input type="text" name="address" id="address" maxlength="200" value="<?php echo $old('address'); ?>" required>
        </div>
        <button type="submit" class="btn btn-block full">สร้างบัญชี</button>
    </form>
    <p class="auth-switch">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
</div>
<?php page_end(); ?>
