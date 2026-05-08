<?php
session_start();
require "db.php";
require "notification_functions.php";

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor = $_SESSION['user'];
$doctor_id = $doctor['id'];

/* =========================
   NOTIFICATIONS
========================= */
$notifications = getNotifications($conn, $doctor_id);
$notifCount = getUnreadCount($conn, $doctor_id);

/* =========================
   DASHBOARD STATS
========================= */
$statsQuery = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=?) AS total,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='pending') AS pending,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='approved') AS approved,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='completed') AS completed
");

$statsQuery->bind_param("iiii", $doctor_id, $doctor_id, $doctor_id, $doctor_id);
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();

/* =========================
   TODAY APPOINTMENTS
========================= */
$todayQuery = $conn->prepare("
    SELECT COUNT(*) total
    FROM appointments
    WHERE doctor_id=?
    AND DATE(appointment_date)=CURDATE()
");

$todayQuery->bind_param("i", $doctor_id);
$todayQuery->execute();
$todayAppointments = $todayQuery->get_result()->fetch_assoc()['total'];

/* =========================
   RECENT APPOINTMENTS
========================= */
$recentQuery = $conn->prepare("
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id=?
    ORDER BY a.appointment_date DESC
    LIMIT 8
");

$recentQuery->bind_param("i", $doctor_id);
$recentQuery->execute();
$appointments = $recentQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard</title>

<link rel="shortcut icon" href="favicon_io/favicon.ico">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = {
    darkMode: 'class'
}
</script>

<style>
body{
    background: linear-gradient(135deg,#eff6ff,#dbeafe,#eef2ff);
}

.dark body{
    background: linear-gradient(135deg,#0f172a,#1e1b4b,#111827);
}

.sidebar-gradient{
    background: linear-gradient(180deg,#1d4ed8,#312e81);
}

.stat-card{
    transition: 0.3s ease;
}

.stat-card:hover{
    transform: translateY(-6px);
}

.fade-in{
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(15px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
</style>
</head>

<body class="text-gray-800 dark:bg-gray-900 dark:text-white">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 sidebar-gradient text-white p-6 hidden md:block">

        <h1 class="text-2xl font-bold mb-8">
            🩺 Doctor Panel
        </h1>

        <nav class="space-y-3">

            <a href="doctor.php" class="flex items-center gap-3 p-3 bg-white/20 rounded-xl">
                <i data-feather="grid"></i>
                Dashboard
            </a>

            <a href="doctor_profile.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-xl">
                <i data-feather="user"></i>
                Profile
            </a>

            <a href="doctor_appointments.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-xl">
                <i data-feather="calendar"></i>
                Appointments
            </a>

            <a href="patient_list.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-xl">
                <i data-feather="users"></i>
                Patients
            </a>

            <a href="doctor_schedule.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-xl">
                <i data-feather="clock"></i>
                Schedule
            </a>

            <a href="logout.php" class="flex items-center gap-3 p-3 hover:bg-red-500 rounded-xl mt-8">
                <i data-feather="log-out"></i>
                Logout
            </a>

        </nav>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 md:p-10 fade-in">

        <!-- HEADER -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">

            <div>
                <h2 class="text-3xl font-bold">
                    Welcome Dr. <?= htmlspecialchars($doctor['name']) ?>
                </h2>

                <p class="text-gray-500 dark:text-gray-300">
                    Manage your patients and appointments
                </p>
            </div>

            <div class="flex items-center gap-4">

                <!-- Notifications -->
                <button onclick="openNotifications()"
                        class="relative bg-white dark:bg-gray-800 p-3 rounded-xl shadow">

                    <i data-feather="bell"></i>

                    <?php if($notifCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-2 rounded-full">
                            <?= $notifCount ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Theme Toggle -->
                <button onclick="toggleTheme()"
                        class="bg-white dark:bg-gray-800 p-3 rounded-xl shadow">
                    <i data-feather="moon"></i>
                </button>

            </div>
        </div>


        <!-- STATS CARDS -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Total -->
            <div class="stat-card bg-blue-600 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <i data-feather="clipboard" class="w-10 h-10"></i>
                    <span class="text-sm">Total</span>
                </div>
                <h3 class="text-3xl font-bold"><?= $stats['total'] ?></h3>
            </div>

            <!-- Pending -->
            <div class="stat-card bg-yellow-500 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <i data-feather="clock" class="w-10 h-10"></i>
                    <span class="text-sm">Pending</span>
                </div>
                <h3 class="text-3xl font-bold"><?= $stats['pending'] ?></h3>
            </div>

            <!-- Approved -->
            <div class="stat-card bg-green-500 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <i data-feather="check-circle" class="w-10 h-10"></i>
                    <span class="text-sm">Approved</span>
                </div>
                <h3 class="text-3xl font-bold"><?= $stats['approved'] ?></h3>
            </div>

            <!-- Completed -->
            <div class="stat-card bg-indigo-600 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <i data-feather="activity" class="w-10 h-10"></i>
                    <span class="text-sm">Completed</span>
                </div>
                <h3 class="text-3xl font-bold"><?= $stats['completed'] ?></h3>
            </div>

        </div>


        <!-- TODAY CARD -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">

            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 dark:text-gray-300">
                        Today's Appointments
                    </p>

                    <h2 class="text-4xl font-bold mt-2">
                        <?= $todayAppointments ?>
                    </h2>
                </div>

                <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-full">
                    <i data-feather="calendar" class="text-blue-600"></i>
                </div>
            </div>
        </div>


        <!-- RECENT APPOINTMENTS -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 overflow-x-auto">

            <h3 class="text-xl font-bold mb-5">
                Recent Appointments
            </h3>

            <table class="w-full">

                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="text-left p-3">Patient</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while($row = $appointments->fetch_assoc()): ?>

                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">

                        <td class="p-3">
                            <?= htmlspecialchars($row['patient_name']) ?>
                        </td>

                        <td class="p-3">
                            <?= date("M d, Y h:i A", strtotime($row['appointment_date'])) ?>
                        </td>

                        <td class="p-3">
                            <span class="px-3 py-1 rounded-full text-xs text-white
                                <?= $row['status']=='pending' ? 'bg-yellow-500' : '' ?>
                                <?= $row['status']=='approved' ? 'bg-green-500' : '' ?>
                                <?= $row['status']=='completed' ? 'bg-blue-500' : '' ?>
                                <?= $row['status']=='rejected' ? 'bg-red-500' : '' ?>">
                                
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                    </tr>

                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>

    </main>
</div>


<!-- NOTIFICATION MODAL -->
<div id="notificationModal"
     class="hidden fixed inset-0 bg-black/50 items-center justify-center z-50">

    <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl shadow-xl p-6">

        <div class="flex justify-between mb-4">
            <h3 class="font-bold text-lg">Notifications</h3>
            <button onclick="closeNotifications()">✕</button>
        </div>

        <div class="max-h-80 overflow-y-auto">

            <?php if($notifications->num_rows > 0): ?>
                <?php while($n = $notifications->fetch_assoc()): ?>

                    <div class="border-b dark:border-gray-700 py-3">
                        <p><?= htmlspecialchars($n['message']) ?></p>

                        <small class="text-gray-500">
                            <?= date("M d, Y h:i A", strtotime($n['created_at'])) ?>
                        </small>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center">
                    No notifications found
                </p>
            <?php endif; ?>

        </div>
    </div>
</div>


<script>
feather.replace();

/* Theme persistence */
function toggleTheme(){
    document.documentElement.classList.toggle("dark");

    if(document.documentElement.classList.contains("dark")){
        localStorage.setItem("theme","dark");
    }else{
        localStorage.setItem("theme","light");
    }
}

if(localStorage.getItem("theme") === "dark"){
    document.documentElement.classList.add("dark");
}

/* Notifications */
function openNotifications(){
    document.getElementById("notificationModal").classList.remove("hidden");
    document.getElementById("notificationModal").classList.add("flex");
}

function closeNotifications(){
    document.getElementById("notificationModal").classList.add("hidden");
    document.getElementById("notificationModal").classList.remove("flex");
}
</script>

</body>
</html>