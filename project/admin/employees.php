<?php
require_once '../includes/db.php';
require_admin();

$employees = $conn->query("SELECT employees_id, employees_firstname, employees_lastname, gender, department, phonenumber, salary
    FROM employees ORDER BY employees_id")->fetch_all(MYSQLI_ASSOC);

page_start('พนักงาน', 'admin', 'employees');
?>
<div class="page-head">
    <h1>พนักงาน</h1>
    <a href="employee_add.php" class="btn">+ เพิ่มพนักงาน</a>
</div>
<?php render_alert(take_flash()); ?>

<div class="card table-wrap">
    <?php if ($employees): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>แผนก</th>
                    <th>เบอร์โทร</th>
                    <th class="num">เงินเดือน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $row): ?>
                    <tr>
                        <td><?php echo e($row['employees_id']); ?></td>
                        <td><?php echo e($row['employees_firstname'] . ' ' . $row['employees_lastname']); ?></td>
                        <td><?php echo e($row['department']); ?></td>
                        <td><?php echo e($row['phonenumber']); ?></td>
                        <td class="num"><?php echo baht($row['salary']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="muted">ไม่พบข้อมูลพนักงาน</p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
