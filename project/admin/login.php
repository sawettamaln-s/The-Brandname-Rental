<?php
require_once '../includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $username = input('username');
    $password = input('password', false);

    // บัญชีแอดมินเก็บในตาราง admins โดยรหัสผ่านเป็น hash
    $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        start_login_session();
        $_SESSION["admin_id"] = $admin['admin_id'];
        redirect("index.php");
    }
    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}

page_start('เข้าสู่ระบบหลังร้าน', 'admin');
?>
<div class="card auth-card">
    <div class="auth-kicker">Back office</div>
    <h1>เข้าสู่ระบบหลังร้าน</h1>
    <?php render_alert(take_flash()); ?>
    <?php error_alert($error); ?>
    <form method="POST" class="form-grid">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label for="username">ชื่อผู้ใช้</label>
            <input type="text" name="username" id="username" value="<?php echo e(input('username')); ?>" required autofocus>
        </div>
        <div class="field">
            <label for="password">รหัสผ่าน</label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="btn btn-block">เข้าสู่ระบบ</button>
    </form>
</div>
<?php page_end(); ?>
