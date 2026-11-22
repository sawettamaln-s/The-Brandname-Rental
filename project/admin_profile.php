<?php
require_once 'db_connect.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลพนักงาน</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100vh;
            margin: 0;
            font-size: 18px;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 10px rgba(112, 22, 22, 0.1);
            border-radius: 8px;
        }

        header {
            background-color: #34495e;
            padding: 20px 0;
            width: 100%;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 16px;
        }

        th {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }

        td {
            background-color: #f9f9f9;
        }

        tr:nth-child(even) td {
            background-color: #f1f1f1;
        }

        tr:hover {
            background-color: #f0f7ff;
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

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .add-btn {
            background-color: #34495e;
            color: white;
            margin-right: 15px;
        }

        .add-btn:hover {
            background-color: #218838;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .button-group {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <h1>THE BRANDNAME RENTAL - ADMIN</h1>
</header>


<div class="container">
    <h2>ข้อมูลพนักงาน</h2>

    <?php
    $sql = "SELECT employees_id, employees_firstname, employees_lastname, department, salary FROM employees ORDER BY employees_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>รหัสพนักงาน</th>
                    <th>ชื่อ</th>
                    <th>นามสกุล</th>
                    <th>แผนก</th>
                    <th>เงินเดือน</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . e($row["employees_id"]) . "</td>
                    <td>" . e($row["employees_firstname"]) . "</td>
                    <td>" . e($row["employees_lastname"]) . "</td>
                    <td>" . e($row["department"]) . "</td>
                    <td>" . number_format($row["salary"], 2) . " บาท</td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>ไม่พบข้อมูลพนักงาน</p>";
    }
    $conn->close();
    ?>
    
    <div class="button-group">
        <a href="add_employee.php">
            <button class="add-btn">เพิ่มพนักงาน</button>
        </a>
    </div>

    <div class="button-group">
        <a href="index_admin.php">
            <button class="back-btn">ย้อนกลับไปหน้าหลัก</button>
        </a>
    </div>
</div>

<footer>
    <p>&copy; 2025 ระบบเช่าผู้ดูแล</p>
</footer>

</body>
</html>
