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

$adminName = $_SESSION['user']['name'] ?? "Admin";
$user_id = $_SESSION['user']['id'];
$range = isset($_GET['range']) ? (int)$_GET['range'] : 7;

/* =========================
   NOTIFICATIONS
========================= */
$notifications = null;
$unreadCount = 0;

if (file_exists("notification_functions.php")) {
    require "notification_functions.php";

    if (function_exists("getNotifications")) {
        $notifications = getNotifications($conn, $user_id);
    }

    if (function_exists("getUnreadCount")) {
        $unreadCount = getUnreadCount($conn, $user_id);
    }
}

/* =========================
   MAIN STATS
========================= */
$statsQuery = "
SELECT 
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM users WHERE role='patient') AS total_patients,
    (SELECT COUNT(*) FROM users WHERE role='doctor') AS total_doctors,
    (SELECT COUNT(*) FROM appointments) AS total_appointments
";

$stats = $conn->query($statsQuery)->fetch_assoc();

/* =========================
   TODAY METRICS
========================= */
$todayPatients = $conn->query("
    SELECT COUNT(*) total 
    FROM users 
    WHERE role='patient' 
    AND DATE(created_at)=CURDATE()
")->fetch_assoc()['total'];

$todayAppointments = $conn->query("
    SELECT COUNT(*) total 
    FROM appointments 
    WHERE DATE(created_at)=CURDATE()
")->fetch_assoc()['total'];

$pendingAppointments = $conn->query("
    SELECT COUNT(*) total
    FROM appointments
    WHERE status='pending'
")->fetch_assoc()['total'];

/* =========================
   TOP DOCTOR
========================= */
$topDoctor = $conn->query("
    SELECT u.name, COUNT(a.id) total
    FROM users u
    LEFT JOIN appointments a ON u.id = a.doctor_id
    WHERE u.role='doctor'
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

/* =========================
   APPOINTMENT TREND
========================= */
$trendStmt = $conn->prepare("
    SELECT DATE(created_at) date, COUNT(*) total
    FROM appointments
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

$trendStmt->bind_param("i", $range);
$trendStmt->execute();
$trendResult = $trendStmt->get_result();

$trendLabels = [];
$trendData = [];

while ($row = $trendResult->fetch_assoc()) {
    $trendLabels[] = $row['date'];
    $trendData[] = $row['total'];
}

/* =========================
   STATUS CHART
========================= */
$statusQuery = $conn->query("
    SELECT status, COUNT(*) total
    FROM appointments
    GROUP BY status
");

$statusLabels = [];
$statusData = [];

while ($row = $statusQuery->fetch_assoc()) {
    $statusLabels[] = ucfirst($row['status']);
    $statusData[] = $row['total'];
}

/* =========================
   RECENT DOCTORS
========================= */
$doctors = $conn->query("
    SELECT name,email,status
    FROM users
    WHERE role='doctor'
    ORDER BY id DESC
    LIMIT 5
");

/* =========================
   RECENT PATIENTS
========================= */
$patients = $conn->query("
    SELECT name,email,created_at
    FROM users
    WHERE role='patient'
    ORDER BY id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="favicon_io/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-gray-800 shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-8 text-blue-600">
            MediCare Admin
        </h1>

        <nav class="space-y-3">
            <a href="admin.php" class="flex gap-3 p-3 rounded-lg bg-blue-500 text-white">
                <i data-feather="home"></i> Dashboard
            </a>

             <a href="admin_profile.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="user"></i> Profile
            </a>

            <a href="users.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="users"></i> Users
            </a>

            <a href="doctor_list.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="user-check"></i> Doctors
            </a>

            <a href="patients.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="heart"></i> Patients
            </a>

            <a href="appointments.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="calendar"></i> Appointments
            </a>

            <a href="admin_assisted_booking.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="clipboard"></i> Assisted Booking
            </a>

            <a href="analytics.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="bar-chart-2"></i> Analytics
            </a>

            <a href="reports.php" class="flex gap-3 p-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="file-text"></i> Reports
            </a>
        </nav>

        <a href="index.php"
           class="block mt-10 bg-red-500 text-white text-center p-3 rounded-lg hover:bg-red-600">
            Logout
        </a>
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
                    Hospital Management Dashboard
                </p>
            </div>

            <div class="flex items-center gap-4">

                <!-- Notifications -->
                <div class="relative">
                    <button onclick="toggleNotifications()" 
                            class="relative bg-white dark:bg-gray-800 p-3 rounded-lg shadow">

                        <i data-feather="bell"></i>

                        <?php if($unreadCount > 0): ?>
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs px-2 rounded-full">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div id="notificationBox"
                         class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg z-50">

                        <div class="p-4 border-b">
                            <h3 class="font-bold">Notifications</h3>
                        </div>

                        <?php if($notifications && $notifications->num_rows > 0): ?>
                            <?php while($notif = $notifications->fetch_assoc()): ?>
                                <div class="p-4 border-b">
                                    <p><?= htmlspecialchars($notif['message']) ?></p>
                                    <small class="text-gray-500">
                                        <?= date("d M Y h:i A", strtotime($notif['created_at'])) ?>
                                    </small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-4 text-gray-500">
                                No notifications
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Theme -->
                <button onclick="toggleTheme()"
                        class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow">
                    🌙
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-blue-500 text-white p-6 rounded-xl">
                <h4>Total Patients</h4>
                <h2 class="text-3xl font-bold"><?= $stats['total_patients'] ?></h2>
            </div>

            <div class="bg-green-500 text-white p-6 rounded-xl">
                <h4>Appointments Today</h4>
                <h2 class="text-3xl font-bold"><?= $todayAppointments ?></h2>
            </div>

            <div class="bg-yellow-500 text-white p-6 rounded-xl">
                <h4>Pending Appointments</h4>
                <h2 class="text-3xl font-bold"><?= $pendingAppointments ?></h2>
            </div>

            <div class="bg-purple-500 text-white p-6 rounded-xl">
                <h4>Top Doctor</h4>
                <h2 class="text-xl font-bold">
                    <?= $topDoctor['name'] ?? 'N/A'; ?>
                </h2>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid lg:grid-cols-2 gap-6 mb-8">

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold mb-4">Appointment Trend</h3>
                <canvas id="trendChart"></canvas>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold mb-4">Appointment Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Recent Tables -->
        <div class="grid lg:grid-cols-2 gap-6">

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold mb-4">Recent Doctors</h3>

                <?php if($doctors->num_rows > 0): ?>
                    <?php while($d = $doctors->fetch_assoc()): ?>
                        <div class="border-b py-2">
                            <?= htmlspecialchars($d['name']) ?>
                            <div class="text-sm text-gray-500"><?= $d['email'] ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No doctors found</p>
                <?php endif; ?>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold mb-4">Recent Patients</h3>

                <?php if($patients->num_rows > 0): ?>
                    <?php while($p = $patients->fetch_assoc()): ?>
                        <div class="border-b py-2">
                            <?= htmlspecialchars($p['name']) ?>
                            <div class="text-sm text-gray-500">
                                <?= date("M d, Y", strtotime($p['created_at'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No patients found</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
feather.replace();

/* Theme Persistence */
function toggleTheme() {
    document.documentElement.classList.toggle('dark');

    if(document.documentElement.classList.contains('dark')){
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}

if(localStorage.getItem('theme') === 'dark'){
    document.documentElement.classList.add('dark');
}

/* Notifications */
function toggleNotifications() {
    document.getElementById("notificationBox").classList.toggle("hidden");
}

/* Trend Chart */
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode($trendData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.2)',
            fill: true,
            tension: 0.4
        }]
    }
});

/* Status Chart */
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: [
                '#22c55e',
                '#f59e0b',
                '#ef4444',
                '#3b82f6'
            ]
        }]
    }
});
</script>

</body>
</html>