<?php
session_start();

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['payment_slip'])) {
    $upload_dir = 'uploads/';
    $upload_file = $upload_dir . basename($_FILES['payment_slip']['name']);
    $upload_ok = 1;
    $image_file_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));

    if (getimagesize($_FILES['payment_slip']['tmp_name']) === false) {
        echo "กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น";
        $upload_ok = 0;
    }

    if ($_FILES['payment_slip']['size'] > 500000) {
        echo "ขออภัย ไฟล์ของคุณใหญ่เกินไป";
        $upload_ok = 0;
    }

    if ($upload_ok == 0) {
        echo "ขออภัย ไม่สามารถอัปโหลดไฟล์ของคุณได้";
    } else {
        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $upload_file)) {
            $_SESSION['payment_slip_uploaded'] = true;
            $_SESSION['uploaded_file'] = $upload_file;
            echo "อัปโหลดสลิปการชำระเงินสำเร็จ!";
        } else {
            echo "ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        }
    }
}

if (isset($_POST['cancel_upload'])) {
    if (isset($_SESSION['uploaded_file'])) {
        $uploaded_file = $_SESSION['uploaded_file'];
        if (file_exists($uploaded_file)) {
            unlink($uploaded_file);
        }
        unset($_SESSION['uploaded_file']);
        unset($_SESSION['payment_slip_uploaded']);
        echo "การอัปโหลดถูกยกเลิก";
    }
}

if (isset($_POST['delete_file'])) {
    if (isset($_SESSION['uploaded_file'])) {
        $uploaded_file = $_SESSION['uploaded_file'];
        if (file_exists($uploaded_file)) {
            unlink($uploaded_file);
        }
        unset($_SESSION['uploaded_file']);
        unset($_SESSION['payment_slip_uploaded']);
        echo "ลบไฟล์สำเร็จ";
    }
}
?>
<form action="upload_slip.php" method="post" enctype="multipart/form-data">
    <input type="file" name="payment_slip" required><br><br>
    <button type="submit" class="upload-btn">อัปโหลดสลิปการชำระเงิน</button>
</form>

<?php
if (isset($_SESSION['payment_slip_uploaded']) && $_SESSION['payment_slip_uploaded'] === true) {
    echo '<div>';
    echo '<p>ไฟล์ของคุณถูกอัปโหลดแล้ว: ' . basename($_SESSION['uploaded_file']) . '</p>';
    
    echo '<form action="upload_slip.php" method="post">';
    echo '<button type="submit" name="delete_file" class="delete-btn">ลบไฟล์</button>';
    echo '</form>';
}
?>
