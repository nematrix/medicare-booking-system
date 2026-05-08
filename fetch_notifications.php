<?php
session_start();
require "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    exit();
}

$doctor_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id=? AND is_read=0
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>