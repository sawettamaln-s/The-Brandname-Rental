<?php
require_once '../includes/db.php';
require_admin();

$error = '';
$genders = ['Male' => 'ชาย', 'Female' => 'หญิง'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $firstname = input('employees_firstname');
    $lastname = input('employees_lastname');
    $gender = input('gender');
    $department = input('department');
    $phonenumber = input('phonenumber');
    $salary = filter_var($_POST['salary'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($firstname === '' || $lastname === '' || $department === '') {
        $error = "กรุณากรอกข้อมูลให้ครบ";
    } elseif (!isset($genders[$gender])) {
        $error = "กรุณาเลือกเพศ";
    } elseif (!preg_match('/^[0-9\-+ ]{9,15}$/', $phonenumber)) {
        $error = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง";
    } elseif ($salary === false) {
        $error = "เงินเดือนต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป";
    } else {
        $new_id = next_id($conn, 'employees', 'employees_id', 'E', 3);
        $stmt = $conn->prepare("INSERT INTO employees (employees_id, employees_firstname, employees_lastname, gender, department, phonenumber, salary)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $new_id, $firstname, $lastname, $gender, $department, $phonenumber, $salary);
        $stmt->execute();
        $stmt->close();
        flash("เพิ่มพนักงาน $new_id เรียบร้อยแล้ว");
        redirect("employees.php");
    }
}

$next_id = next_id($conn, 'employees', 'employees_id', 'E', 3);

page_start('เพิ่มพนักงาน', 'admin', 'employees');
?>
<div class="card page-narrow">
    <h1>เพิ่มพนักงาน</h1>
    <?php error_alert($error); ?>
    <form method="POST" class="form-grid two-cols">
        <?php echo csrf_field(); ?>
        <div class="field">
            <label>รหัสพนักงาน (อัตโนมัติ)</label>
            <input type="text" value="<?php echo e($next_id); ?>" readonly>
        </div>
        <div class="field">
            <label for="gender">เพศ</label>
            <select id="gender" name="gender" required>
                <?php foreach ($genders as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo input('gender') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="employees_firstname">ชื่อ</label>
            <input type="text" id="employees_firstname" name="employees_firstname" maxlength="50" value="<?php echo e(input('employees_firstname')); ?>" required>
        </div>
        <div class="field">
            <label for="employees_lastname">นามสกุล</label>
            <input type="text" id="employees_lastname" name="employees_lastname" maxlength="50" value="<?php echo e(input('employees_lastname')); ?>" required>
        </div>
        <div class="field">
            <label for="department">แผนก</label>
            <input type="text" id="department" name="department" maxlength="50" value="<?php echo e(input('department')); ?>" required>
        </div>
        <div class="field">
            <label for="phonenumber">เบอร์โทรศัพท์</label>
            <input type="text" id="phonenumber" name="phonenumber" maxlength="15" value="<?php echo e(input('phonenumber')); ?>" required>
        </div>
        <div class="field">
            <label for="salary">เงินเดือน (บาท)</label>
            <input type="number" id="salary" name="salary" min="0" step="1" value="<?php echo e(input('salary')); ?>" required>
        </div>
        <div class="full actions">
            <button type="submit" class="btn">บันทึก</button>
            <a href="employees.php" class="btn btn-outline">ยกเลิก</a>
        </div>
    </form>
</div>
<?php page_end(); ?>
