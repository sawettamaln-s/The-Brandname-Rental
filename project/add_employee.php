<?php
include 'db_connect.php';

$sql = "SELECT MAX(CAST(SUBSTRING(employees_id, 2) AS UNSIGNED)) AS max_id FROM employees";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$next_id = "E" . str_pad($row['max_id'] + 1, 3, "0", STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['employees_firstname'];
    $lastname = $_POST['employees_lastname'];
    $gender = $_POST['gender'];
    $department = $_POST['department'];
    $phonenumber = $_POST['phonenumber'];
    $salary = $_POST['salary'];

    $insert_sql = "INSERT INTO employees (employees_id, employees_firstname, employees_lastname, gender, department, phonenumber, salary) 
                VALUES ('$next_id', '$firstname', '$lastname', '$gender', '$department', '$phonenumber', '$salary')";

    if ($conn->query($insert_sql) === TRUE) {
        echo "<script>alert('บันทึกข้อมูลพนักงานสำเร็จ!'); window.location.href='admin_profile.php';</script>";
    } else {
        echo "Error: " . $insert_sql . "<br>" . $conn->error;
    }
}

$conn->close();
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

    <form action="add_employee.php" method="POST">
        <label for="employees_id">รหัสพนักงาน (อัตโนมัติ):</label>
        <input type="text" id="employees_id" name="employees_id" value="<?= $next_id ?>" readonly>

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
        <input type="number" id="salary" name="salary" step="0.01" required>

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
