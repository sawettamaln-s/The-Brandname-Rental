<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rental_id = $_POST['rental_id'];
    $track_return = $_POST['track_return'];
    $postal_code = '73000';
    $return_date = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO track_return (rental_id, track_return, postal_code)
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE track_return = ?, postal_code = ?");
    $stmt->bind_param("sssss", $rental_id, $track_return, $postal_code, $track_return, $postal_code);
    
    if ($stmt->execute()) {

        $result = $conn->query("SELECT return_id FROM returnss ORDER BY return_id DESC LIMIT 1");
        $latest_return_id = "Ret0010";
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $latest_return_num = intval(substr($row["return_id"], 3));
            $latest_return_id = "Ret" . str_pad($latest_return_num + 1, 4, "0", STR_PAD_LEFT);
        }

        $update_return_stmt = $conn->prepare("INSERT INTO returnss (return_id, rental_id, return_date) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE return_date = ?");
        $update_return_stmt->bind_param("ssss", $latest_return_id, $rental_id, $return_date, $return_date);
        $update_return_stmt->execute();
        $update_return_stmt->close();

        header("Location: profile.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูลการคืนสินค้า";
    }

    $stmt->close();
}
?>
