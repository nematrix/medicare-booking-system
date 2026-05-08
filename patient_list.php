<?php
session_start();
require "db.php";

/* =========================
   AUTH (DOCTOR ONLY)
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor = $_SESSION['user'];
$doctor_id = $doctor['id'];

/* =========================
   GET PATIENTS WHO BOOKED THIS DOCTOR
========================= */
$stmt = $conn->prepare("
    SELECT 
        u.id AS patient_id,
        u.name AS patient_name,
        u.email,
        COUNT(a.id) AS total_visits,
        MAX(a.appointment_date) AS last_visit,
        SUM(CASE WHEN a.status='pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN a.status='approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN a.status='rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN a.status='completed' THEN 1 ELSE 0 END) AS completed
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id = ?
    GROUP BY a.patient_id
    ORDER BY last_visit DESC
");

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Patients</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
</script>

<style>
.card {
    background: #e6ebf5;
    border-radius: 18px;
    box-shadow: 8px 8px 16px #c8ced9, -8px -8px 16px #ffffff;
    transition: 0.2s ease;
}

.dark .card {
    background: #111827;
    box-shadow: 8px 8px 16px #0b0f1a, -8px -8px 16px #1a2235;
}

.card:hover {
    transform: translateY(-4px);
}
</style>

</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex min-h-screen">

<!-- SIDEBAR -->
<aside class="w-64 card m-4 p-6">

    <h1 class="text-xl font-bold mb-8 text-blue-700 dark:text-blue-400">🩺 Doctor Panel</h1>

    <nav class="space-y-2 text-sm">

        <a href="doctor.php" class="block p-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">
            <i data-feather="grid"></i> Dashboard
        </a>

        <a href="doctor_appointments.php" class="block p-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">
            <i data-feather="calendar"></i> Appointments
        </a>

        <a href="patients.php" class="block p-3 rounded-xl bg-blue-600 text-white">
            <i data-feather="users"></i> Patients
        </a>

        <a href="doctor_schedule.php" class="block p-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700">
            <i data-feather="clock"></i> Schedule
        </a>

        <a href="logout.php" class="block p-3 rounded-xl text-red-600 hover:bg-red-100 dark:hover:bg-gray-800">
            <i data-feather="log-out"></i> Logout
        </a>

    </nav>

</aside>

<!-- MAIN -->
<main class="flex-1 p-10">

<!-- HEADER -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-blue-700 dark:text-blue-300">My Patients</h2>
    <p class="text-gray-600 dark:text-gray-400">Patients who booked appointments with you</p>
</div>

<!-- TABLE -->
<div class="card p-6 overflow-x-auto">

<table class="w-full text-sm">

<thead>
<tr class="border-b border-gray-300 dark:border-gray-700 text-blue-700 dark:text-blue-300">
    <th class="p-3 text-left">Patient</th>
    <th class="p-3 text-left">Email</th>
    <th class="p-3 text-left">Visits</th>
    <th class="p-3 text-left">Last Visit</th>
    <th class="p-3 text-left">Status Summary</th>
</tr>
</thead>

<tbody>

<?php while($p = $patients->fetch_assoc()): ?>

<tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-blue-50 dark:hover:bg-gray-800">

    <td class="p-3 font-semibold">
        <?= htmlspecialchars($p['patient_name']) ?>
    </td>

    <td class="p-3 text-gray-700 dark:text-gray-300">
        <?= htmlspecialchars($p['email']) ?>
    </td>

    <td class="p-3">
        <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-xs">
            <?= $p['total_visits'] ?>
        </span>
    </td>

    <td class="p-3 text-gray-700 dark:text-gray-300">
        <?= date("d M Y H:i", strtotime($p['last_visit'])) ?>
    </td>

    <td class="p-3 text-xs space-y-1">

        <div class="text-yellow-600">🟡 Pending: <?= $p['pending'] ?></div>
        <div class="text-green-600">🟢 Approved: <?= $p['approved'] ?></div>
        <div class="text-red-600">🔴 Rejected: <?= $p['rejected'] ?></div>
        <div class="text-blue-600">🔵 Completed: <?= $p['completed'] ?></div>

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