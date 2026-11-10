
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {

        try {

            $stmt = $conn->prepare(
                "DELETE FROM employees WHERE id = :id"
            );

            $stmt->execute(['id' => $id]);

            header("Location: employees.php?deleted=1");
            exit();

        } catch (PDOException $e) {

            die("Unable to delete employee. This employee may have related payroll or attendance records.");

        }

    }
}

header("Location: employees.php");
exit();
?>
