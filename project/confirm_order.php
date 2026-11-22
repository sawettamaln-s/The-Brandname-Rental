<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION["customers_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION["customers_id"];
$employee_id = '';

$employee_stmt = $conn->prepare("SELECT employees_id FROM employees ORDER BY RAND() LIMIT 1");
$employee_stmt->execute();
$employee_result = $employee_stmt->get_result();
if ($employee_result->num_rows > 0) {
    $employee = $employee_result->fetch_assoc();
    $employee_id = $employee['employees_id'];
}

function generateTrackRentals() {
    $letters = chr(rand(65, 90)) . chr(rand(65, 90));

    $numbers = '';
    for ($i = 0; $i < 9; $i++) {
        $numbers .= rand(0, 9);
    }
    return $letters . $numbers . 'TH';
}

$track_rentals = generateTrackRentals();

$rental_date = date("Y-m-d");
$deposit_fee = 900;

$stmt = $conn->prepare("SELECT rental_id FROM rentals WHERE rental_id LIKE 'Ren%' ORDER BY rental_id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {

    $last_rental_id = $result->fetch_assoc()['rental_id'];

    $last_id_number = substr($last_rental_id, 3);
    $new_id_number = str_pad($last_id_number + 1, 4, "0", STR_PAD_LEFT);
    $new_rental_id = 'Ren' . $new_id_number;
} else {
    $new_rental_id = 'Ren1121';
}

$stmt = $conn->prepare("INSERT INTO rentals (rental_id, customers_id, product_id, rental_date, rental_days, deposit_fee, employees_id, track_rentals) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($_SESSION['cart'] as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];
    $rental_days = $quantity;

    $stmt->bind_param("ssssssss", $new_rental_id, $customer_id, $product_id, $rental_date, $rental_days, $deposit_fee, $employee_id, $track_rentals);
    $stmt->execute();
}

$stmt->close();

unset($_SESSION['cart']);

echo "<script>
        alert('การชำระเงินเสร็จสมบูรณ์! ขอบคุณที่ใช้บริการ');
        window.location.href = 'profile.php';
    </script>";
exit();
?>
