<?php
session_start();
require "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read=1
    WHERE user_id=?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

header("Location: doctor.php");
exit();
?>