<?php
require_once 'db_connect.php';
require_admin();

$error = '';

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
    } elseif (!in_array($gender, ['Male', 'Female'], true)) {
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
        redirect("admin_profile.php");
    }
}

$next_id = next_id($conn, 'employees', 'employees_id', 'E', 3);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มพนักงาน</title>
    <style>
header {
    background-color: #34495e;
    color: white;
    text-align: center;
    padding: 10px;
    font-size: 28px;
    font-weight: bold;
    width: 100%;
    box-sizing: border-box;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 10;
}

h1 {
    font-size: 2.5rem;
    font-weight: 600;
    margin: 0;
    padding: 10px 0;
}

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20;
            padding: 20px;
            text-align: center;
        }
        .container {
    max-width: 500px;
    background: white;
    padding: 50px;
    margin: 100px auto 20px auto;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

        h2 {
            color: #333;
        }
        form {
            margin-top: 20px;
        }
        input, select {
            padding: 10px;
            margin: 10px 0;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            background-color: #34495e;
            color: white;
        }
        button:hover {
            background-color: #218838;
        }
        footer {
            background-color: #34495e;
            color: white;
            text-align: center;
            padding: 20px;
            width: 100%;
            position: fixed;
            bottom: 0;
            left: 0;
        }

    </style>
</head>
<body>


<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>


<div class="container">
    <h2>เพิ่มข้อมูลพนักงาน</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red; font-weight: bold;"><?php echo e($error); ?></p>
    <?php endif; ?>

    <form action="add_employee.php" method="POST">
        <?php echo csrf_field(); ?>
        <label for="employees_id">รหัสพนักงาน (อัตโนมัติ):</label>
        <input type="text" id="employees_id" value="<?php echo e($next_id); ?>" readonly>

        <label for="employees_firstname">ชื่อ:</label>
        <input type="text" id="employees_firstname" name="employees_firstname" required>

        <label for="employees_lastname">นามสกุล:</label>
        <input type="text" id="employees_lastname" name="employees_lastname" required>

        <label for="gender">เพศ:</label>
        <select id="gender" name="gender" required>
            <option value="Male">ชาย</option>
            <option value="Female">หญิง</option>
        </select>

        <label for="department">แผนก:</label>
        <input type="text" id="department" name="department" required>

        <label for="phonenumber">เบอร์โทรศัพท์:</label>
        <input type="text" id="phonenumber" name="phonenumber" required>

        <label for="salary">เงินเดือน:</label>
        <input type="number" id="salary" name="salary" min="0" step="1" required>

        <button type="submit">บันทึกข้อมูล</button>
    </form>

    <div style="margin-top: 20px;">
        <a href="admin_profile.php">
            <button style="background-color: #6c757d;">ย้อนกลับ</button>
        </a>
    </div>
</div>

<footer>
    <p>&copy; 2025 ระบบเช่าผู้ดูแล</p>
</footer>

</body>
</html>
