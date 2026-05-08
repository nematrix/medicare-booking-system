<?php
session_start();
require "db.php";

/* AUTH (DOCTOR ONLY) */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor = $_SESSION['user'];
$doctor_id = $doctor['id'];

/* HANDLE ACTIONS */
if (isset($_GET['action'], $_GET['id'])) {

    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $validActions = ['approved', 'rejected', 'completed'];

    if (in_array($action, $validActions)) {

        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = ? 
            WHERE id = ? AND doctor_id = ?
        ");
        $stmt->bind_param("sii", $action, $id, $doctor_id);
        $stmt->execute();

        $p = $conn->prepare("
            SELECT patient_id 
            FROM appointments 
            WHERE id = ? AND doctor_id = ?
        ");
        $p->bind_param("ii", $id, $doctor_id);
        $p->execute();
        $patient = $p->get_result()->fetch_assoc();

        if ($patient) {

            $patient_id = $patient['patient_id'];

            $message = "Your appointment has been " . strtoupper($action);

            $n = $conn->prepare("
                INSERT INTO notifications 
                (user_id, role, appointment_id, message, type)
                VALUES (?, 'patient', ?, ?, 'appointment')
            ");

            $n->bind_param("iis", $patient_id, $id, $message);
            $n->execute();
        }

        header("Location: doctor_appointments.php");
        exit();
    }
}

/* FETCH */
$stmt = $conn->prepare("
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Appointments</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
</script>

<style>
.card{
    background:#e6ebf5;
    border-radius:18px;
    box-shadow:8px 8px 16px #c8ced9,-8px -8px 16px #fff;
}

.dark .card{
    background:#111827;
    box-shadow:8px 8px 16px #0b0f1a,-8px -8px 16px #1a2235;
}

/* improved row hover (same design, better clarity) */
tr:hover{
    transition:0.2s ease;
}
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex flex-col md:flex-row min-h-screen">

<!-- SIDEBAR -->
<aside class="w-full md:w-64 card m-4 p-5">

    <h1 class="text-xl font-bold mb-6 text-blue-700 dark:text-blue-400">🩺 Doctor Panel</h1>

    <nav class="flex md:flex-col gap-2 overflow-x-auto md:overflow-visible">

        <a href="doctor.php" class="flex items-center gap-2 p-3 rounded-xl hover:bg-blue-100 dark:hover:bg-gray-700 whitespace-nowrap">
            <i data-feather="grid"></i> Dashboard
        </a>

        <a href="doctor_appointments.php" class="flex items-center gap-2 p-3 rounded-xl bg-blue-600 text-white shadow whitespace-nowrap">
            <i data-feather="calendar"></i> Appointments
        </a>

        <a href="doctor_schedule.php" class="flex items-center gap-2 p-3 rounded-xl hover:bg-blue-100 dark:hover:bg-gray-700 whitespace-nowrap">
            <i data-feather="clock"></i> Schedule
        </a>

    </nav>
</aside>

<!-- MAIN -->
<main class="flex-1 p-4 md:p-10">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-6">

        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-blue-800 dark:text-blue-300">
                All Appointments
            </h2>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                Manage patient bookings
            </p>
        </div>

        <button onclick="document.documentElement.classList.toggle('dark')"
                class="card px-4 py-2 rounded-xl w-fit text-blue-700 dark:text-blue-300">
            🌙 Theme
        </button>

    </div>

    <!-- TABLE -->
    <div class="card p-4 md:p-6 overflow-x-auto">

        <table class="w-full min-w-[700px] text-sm">

            <thead>
            <tr class="border-b border-gray-300 dark:border-gray-700">
                <th class="p-3 text-left text-blue-700 dark:text-blue-300">Patient</th>
                <th class="p-3 text-left text-blue-700 dark:text-blue-300">Date</th>
                <th class="p-3 text-left text-blue-700 dark:text-blue-300">Reason</th>
                <th class="p-3 text-left text-blue-700 dark:text-blue-300">Status</th>
                <th class="p-3 text-left text-blue-700 dark:text-blue-300">Actions</th>
            </tr>
            </thead>

            <tbody>

            <?php while($a = $appointments->fetch_assoc()): ?>
            <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-blue-50 dark:hover:bg-gray-800">

                <td class="p-3 font-semibold">
                    <?= htmlspecialchars($a['patient_name']) ?>
                </td>

                <td class="p-3 text-gray-700 dark:text-gray-300">
                    <?= date("d M Y H:i", strtotime($a['appointment_date'])) ?>
                </td>

                <td class="p-3 text-gray-600 dark:text-gray-400">
                    <?= htmlspecialchars($a['reason']) ?>
                </td>

                <td class="p-3">
                    <span class="px-3 py-1 rounded-full text-xs text-white
                        <?= $a['status']=='pending'?'bg-yellow-500':'' ?>
                        <?= $a['status']=='approved'?'bg-green-600':'' ?>
                        <?= $a['status']=='rejected'?'bg-red-600':'' ?>
                        <?= $a['status']=='completed'?'bg-blue-600':'' ?>">
                        <?= ucfirst($a['status']) ?>
                    </span>
                </td>

                <td class="p-3 flex flex-wrap gap-2">

                    <a href="?action=approved&id=<?= $a['id'] ?>"
                       class="px-3 py-1 text-xs rounded-lg bg-green-600 text-white hover:bg-green-700">
                        Approve
                    </a>

                    <a href="?action=rejected&id=<?= $a['id'] ?>"
                       class="px-3 py-1 text-xs rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Reject
                    </a>

                    <a href="?action=completed&id=<?= $a['id'] ?>"
                       class="px-3 py-1 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Complete
                    </a>

                </td>

            </tr>
            <?php endwhile; ?>

            </tbody>
        </table>

    </div>

</main>
</div>

<script>
feather.replace();
</script>

</body>
</html>