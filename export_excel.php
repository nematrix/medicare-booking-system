<?php
session_start();
require "db.php";

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   FETCH APPOINTMENT DATA
========================= */
$query = "
    SELECT 
        a.id,
        p.name AS patient_name,
        d.name AS doctor_name,
        doc.specialization,
        a.appointment_date,
        a.reason,
        a.status,
        a.created_at
    FROM appointments a

    LEFT JOIN users p 
        ON a.patient_id = p.id

    LEFT JOIN users d 
        ON a.doctor_id = d.id

    LEFT JOIN doctors doc
        ON d.id = doc.user_id

    ORDER BY a.id DESC
";

$result = $conn->query($query);

/* =========================
   FILE NAME
========================= */
$filename = "clinic_appointments_report_" . date("Y-m-d") . ".xls";

/* =========================
   EXCEL HEADERS
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   EXPORT TABLE
========================= */
echo "
<table border='1'>
    <tr style='background:#d1d5db; font-weight:bold;'>
        <th>Appointment ID</th>
        <th>Patient Name</th>
        <th>Doctor Name</th>
        <th>Specialization</th>
        <th>Appointment Date</th>
        <th>Appointment Time</th>
        <th>Reason</th>
        <th>Status</th>
        <th>Created At</th>
    </tr>
";

/* =========================
   DATA ROWS
========================= */
if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $appointmentDate = date("Y-m-d", strtotime($row['appointment_date']));
        $appointmentTime = date("h:i A", strtotime($row['appointment_date']));

        echo "
        <tr>
            <td>{$row['id']}</td>
            <td>{$row['patient_name']}</td>
            <td>{$row['doctor_name']}</td>
            <td>" . ($row['specialization'] ?? 'General') . "</td>
            <td>{$appointmentDate}</td>
            <td>{$appointmentTime}</td>
            <td>{$row['reason']}</td>
            <td>{$row['status']}</td>
            <td>{$row['created_at']}</td>
        </tr>
        ";
    }

} else {

    echo "
    <tr>
        <td colspan='9'>No appointment records found</td>
    </tr>
    ";
}

echo "</table>";

exit();
?>