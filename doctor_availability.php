<?php
include "db.php";

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

if ($doctor_id <= 0) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("
    SELECT day_of_week, start_time, end_time, is_available
    FROM doctor_schedules
    WHERE doctor_id = ?
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);