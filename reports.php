<?php
session_start();
require "db.php";

/* AUTH CHECK */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['user']['name'] ?? "Admin";

/* DATE FILTER */
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

$dateCondition = "";

/* SAFE DATE CONDITION */
if (!empty($startDate) && !empty($endDate)) {
    $startDate = $conn->real_escape_string($startDate);
    $endDate   = $conn->real_escape_string($endDate);

    $dateCondition = " AND DATE(a.created_at) BETWEEN '$startDate' AND '$endDate' ";
}

/* =========================
   SUMMARY
========================= */
$totalAppointments = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments a
    WHERE 1=1 $dateCondition
")->fetch_assoc()['total'];

$totalPatients = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role='patient'
")->fetch_assoc()['total'];

$totalDoctors = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role='doctor'
")->fetch_assoc()['total'];

$completedAppointments = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments a
    WHERE a.status='completed'
")->fetch_assoc()['total'];

/* =========================
   REVENUE
========================= */
$revenue = 0;

$check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'amount'");

if ($check->num_rows > 0) {
    $rev = $conn->query("
        SELECT SUM(amount) AS totalRevenue
        FROM appointments a
        WHERE a.status='completed'
    ");

    $revenue = $rev->fetch_assoc()['totalRevenue'] ?? 0;
}

/* =========================
   APPOINTMENTS
========================= */
$appointments = $conn->query("
    SELECT a.*, 
           p.name AS patient_name,
           d.name AS doctor_name
    FROM appointments a
    LEFT JOIN users p ON a.patient_id = p.id
    LEFT JOIN users d ON a.doctor_id = d.id
    WHERE 1=1 $dateCondition
    ORDER BY a.id DESC
    LIMIT 20
");

/* =========================
   PATIENTS
========================= */
$patients = $conn->query("
    SELECT *
    FROM users
    WHERE role='patient'
    ORDER BY id DESC
    LIMIT 10
");

/* =========================
   DOCTORS REPORT
========================= */
$doctors = $conn->query("
    SELECT u.name, u.email,
           COUNT(a.id) AS totalAppointments
    FROM users u
    LEFT JOIN appointments a ON u.id = a.doctor_id
    WHERE u.role='doctor'
    GROUP BY u.id
    ORDER BY totalAppointments DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Center</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-gray-800 shadow-lg p-6">
        <h1 class="text-2xl font-bold text-blue-600 mb-8 flex items-center gap-2">
            <i data-feather="file-text"></i>
            Reports
        </h1>

        <nav class="space-y-3">
            <a href="admin.php" class="flex gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="home"></i>
                Dashboard
            </a>

            <a href="analytics.php" class="flex gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="bar-chart-2"></i>
                Analytics
            </a>

            <a href="appointments.php" class="flex gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="calendar"></i>
                Appointments
            </a>

            <a href="logout.php" class="flex gap-3 p-3 rounded bg-red-500 text-white">
                <i data-feather="log-out"></i>
                Logout
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold">
                    Welcome, <?= htmlspecialchars($adminName) ?>
                </h2>
                <p class="text-gray-500">
                    Hospital reporting center
                </p>
            </div>

            <button onclick="toggleTheme()"
                class="bg-gray-800 text-white px-4 py-2 rounded flex gap-2">
                <i data-feather="moon"></i>
                Theme
            </button>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mb-8">
            <form method="GET" class="grid md:grid-cols-4 gap-4">
                <input type="date" name="start_date" value="<?= $startDate ?>"
                    class="p-3 rounded border text-black">

                <input type="date" name="end_date" value="<?= $endDate ?>"
                    class="p-3 rounded border text-black">

                <button class="bg-blue-500 text-white rounded p-3">
                    Filter Reports
                </button>

                <a href="reports.php"
                   class="bg-gray-500 text-white rounded p-3 text-center">
                    Reset
                </a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid md:grid-cols-5 gap-6 mb-8">

            <div class="bg-blue-500 text-white p-6 rounded-xl">
                <i data-feather="calendar"></i>
                <h3>Total Appointments</h3>
                <h1 class="text-3xl font-bold"><?= $totalAppointments ?></h1>
            </div>

            <div class="bg-green-500 text-white p-6 rounded-xl">
                <i data-feather="users"></i>
                <h3>Total Patients</h3>
                <h1 class="text-3xl font-bold"><?= $totalPatients ?></h1>
            </div>

            <div class="bg-purple-500 text-white p-6 rounded-xl">
                <i data-feather="user-check"></i>
                <h3>Total Doctors</h3>
                <h1 class="text-3xl font-bold"><?= $totalDoctors ?></h1>
            </div>

            <div class="bg-yellow-500 text-white p-6 rounded-xl">
                <i data-feather="check-circle"></i>
                <h3>Completed</h3>
                <h1 class="text-3xl font-bold"><?= $completedAppointments ?></h1>
            </div>

            <div class="bg-red-500 text-white p-6 rounded-xl">
                <i data-feather="dollar-sign"></i>
                <h3>Revenue</h3>
                <h1 class="text-2xl font-bold">
                    MK <?= number_format($revenue) ?>
                </h1>
            </div>
        </div>

        <!-- Export Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">

            <a href="export_excel.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <i data-feather="file-text"></i>
                Excel Report
            </a>

            <a href="export_pdf.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <i data-feather="file"></i>
                PDF Report
            </a>

            <a href="export_word.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <i data-feather="file-plus"></i>
                Word Report
            </a>
        </div>

        <!-- Appointment Table -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mb-8 overflow-auto">
            <h3 class="text-xl font-bold mb-4">Recent Appointments</h3>

            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-3">Patient</th>
                        <th class="text-left p-3">Doctor</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = $appointments->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($row['doctor_name']) ?></td>
                        <td class="p-3"><?= $row['appointment_date'] ?></td>
                        <td class="p-3"><?= ucfirst($row['status']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Doctors Table -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
            <h3 class="text-xl font-bold mb-4">Doctor Performance Report</h3>

            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-3">Doctor</th>
                        <th class="text-left p-3">Email</th>
                        <th class="text-left p-3">Appointments</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($doc = $doctors->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($doc['name']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($doc['email']) ?></td>
                        <td class="p-3"><?= $doc['totalAppointments'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script>
feather.replace();

function toggleTheme(){
    document.documentElement.classList.toggle("dark");

    if(document.documentElement.classList.contains("dark")){
        localStorage.setItem("theme","dark");
    } else {
        localStorage.setItem("theme","light");
    }
}

if(localStorage.getItem("theme")==="dark"){
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>