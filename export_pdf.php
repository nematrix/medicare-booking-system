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
   FETCH DATA
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDF Report</title>

    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th {
            background: #f2f2f2;
        }

        th, td {
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }

        .print-btn {
            background: red;
            color: white;
            padding: 10px 20px;
            border: none;
            margin-bottom: 20px;
            cursor: pointer;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

<button onclick="window.print()" class="print-btn">
    Download PDF
</button>

<h2>Clinic Appointment Report</h2>
<p><strong>Generated:</strong> <?= date("F d, Y h:i A") ?></p>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Specialization</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Reason</th>
        </tr>
    </thead>

    <tbody>

    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>

            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                <td><?= $row['specialization'] ?? 'General' ?></td>
                <td><?= date("M d, Y", strtotime($row['appointment_date'])) ?></td>
                <td><?= date("h:i A", strtotime($row['appointment_date'])) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
            </tr>

        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No records found</td>
        </tr>
    <?php endif; ?>

    </tbody>
</table>

<script>
window.onload = function () {
    window.print();
}
</script>

</body>
</html>