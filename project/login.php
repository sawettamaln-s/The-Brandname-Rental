<?php
require_once 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $email = input('email');
    $password = input('password', false);

    $stmt = $conn->prepare("SELECT customers_id, customers_firstname, customers_lastname, password FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // แจ้งข้อความเดียวกันทั้งกรณีไม่มีอีเมลและรหัสผิด เพื่อไม่ให้เดาได้ว่าอีเมลไหนมีในระบบ
    if ($user && password_verify($password, $user['password'])) {
        start_login_session();
        $_SESSION["customers_id"] = $user["customers_id"];
        $_SESSION["customers_name"] = $user["customers_firstname"] . " " . $user["customers_lastname"];
        redirect("index.php");
    }
    $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
}

page_start('เข้าสู่ระบบ', 'shop', 'login');
?>
<div class="card auth-card">
    <h1>เข้าสู่ระบบ</h1>
    <?php render_alert(take_flash()); ?>
    <?php error_alert($error); ?>
    <form method="POST" class="form-grid">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="email">อีเมล</label>
            <input type="email" name="email" id="email" value="<?php echo e(input('email')); ?>" required autofocus>
        </div>
        <div class="field">
            <label for="password">รหัสผ่าน</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="btn btn-block">เข้าสู่ระบบ</button>
    </form>
    <p class="auth-switch">ยังไม่มีบัญชี? <a href="register.php">สร้างบัญชี</a></p>
</div>
<?php page_end(); ?>
