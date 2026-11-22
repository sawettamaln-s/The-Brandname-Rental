<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION["customers_id"])) {
header("Location: login.php");
exit();
}

$customer_id = $_SESSION["customers_id"];

$sql = "UPDATE customers SET customers_member = 'yes' WHERE customers_id = '$customer_id'";

if ($conn->query($sql) === TRUE) {
header("Location: profile.php");
exit();
} else {
echo "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $conn->error;
}
?>
