<?php
session_start();
require "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['user']['name'] ?? "Admin";

/* TOTAL COUNTS */
$totalPatients = $conn->query("SELECT COUNT(*) total FROM users WHERE role='patient'")->fetch_assoc()['total'];
$totalDoctors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='doctor'")->fetch_assoc()['total'];
$totalAppointments = $conn->query("SELECT COUNT(*) total FROM appointments")->fetch_assoc()['total'];
$pendingAppointments = $conn->query("SELECT COUNT(*) total FROM appointments WHERE status='pending'")->fetch_assoc()['total'];

/* REVENUE */
$revenue = 0;
$checkColumn = $conn->query("SHOW COLUMNS FROM appointments LIKE 'amount'");
if ($checkColumn->num_rows > 0) {
    $revenue = $conn->query("
        SELECT SUM(amount) totalRevenue 
        FROM appointments 
        WHERE status='completed'
    ")->fetch_assoc()['totalRevenue'] ?? 0;
}

/* MONTHLY APPOINTMENTS */
$monthlyQuery = $conn->query("
    SELECT MONTH(created_at) month_num, COUNT(*) total
    FROM appointments
    WHERE YEAR(created_at)=YEAR(CURDATE())
    GROUP BY MONTH(created_at)
");

$appointmentMonths = [];
$appointmentTotals = [];

while($row = $monthlyQuery->fetch_assoc()){
    $appointmentMonths[] = date("M", mktime(0,0,0,$row['month_num'],1));
    $appointmentTotals[] = $row['total'];
}

/* PATIENT GROWTH */
$patientQuery = $conn->query("
    SELECT MONTH(created_at) month_num, COUNT(*) total
    FROM users
    WHERE role='patient'
    GROUP BY MONTH(created_at)
");

$patientMonths = [];
$patientTotals = [];

while($row = $patientQuery->fetch_assoc()){
    $patientMonths[] = date("M", mktime(0,0,0,$row['month_num'],1));
    $patientTotals[] = $row['total'];
}

/* STATUS */
$statusQuery = $conn->query("
    SELECT status, COUNT(*) total
    FROM appointments
    GROUP BY status
");

$statusLabels = [];
$statusData = [];

while($row = $statusQuery->fetch_assoc()){
    $statusLabels[] = ucfirst($row['status']);
    $statusData[] = $row['total'];
}

/* TOP DOCTORS */
$topDoctors = $conn->query("
    SELECT u.name, COUNT(a.id) totalAppointments
    FROM users u
    LEFT JOIN appointments a ON u.id = a.doctor_id
    WHERE u.role='doctor'
    GROUP BY u.id
    ORDER BY totalAppointments DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white dark:bg-gray-800 shadow-lg p-6">
        <h1 class="text-2xl font-bold text-blue-600 mb-8 flex items-center gap-2">
            <i data-feather="activity"></i>
            Analytics
        </h1>

        <nav class="space-y-3">

            <a href="admin.php" class="flex items-center gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="home"></i>
                Dashboard
            </a>

            <a href="appointments.php" class="flex items-center gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="calendar"></i>
                Appointments
            </a>

            <a href="doctor_list.php" class="flex items-center gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="user-check"></i>
                Doctors
            </a>

            <a href="patients.php" class="flex items-center gap-3 p-3 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                <i data-feather="users"></i>
                Patients
            </a>

            <a href="logout.php" class="flex items-center gap-3 p-3 rounded bg-red-500 text-white">
                <i data-feather="log-out"></i>
                Logout
            </a>

        </nav>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-8">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold">
                    Welcome, <?= htmlspecialchars($adminName) ?>
                </h2>
                <p class="text-gray-500">
                    Hospital analytics overview
                </p>
            </div>

            <button onclick="toggleTheme()"
                    class="bg-gray-800 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i data-feather="moon"></i>
                Theme
            </button>
        </div>

        <!-- METRIC CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">

            <div class="bg-blue-500 text-white p-6 rounded-xl">
                <i data-feather="users"></i>
                <h3>Total Patients</h3>
                <h1 class="text-3xl font-bold"><?= $totalPatients ?></h1>
            </div>

            <div class="bg-green-500 text-white p-6 rounded-xl">
                <i data-feather="user-check"></i>
                <h3>Total Doctors</h3>
                <h1 class="text-3xl font-bold"><?= $totalDoctors ?></h1>
            </div>

            <div class="bg-purple-500 text-white p-6 rounded-xl">
                <i data-feather="calendar"></i>
                <h3>Appointments</h3>
                <h1 class="text-3xl font-bold"><?= $totalAppointments ?></h1>
            </div>

            <div class="bg-yellow-500 text-white p-6 rounded-xl">
                <i data-feather="clock"></i>
                <h3>Pending</h3>
                <h1 class="text-3xl font-bold"><?= $pendingAppointments ?></h1>
            </div>

            <div class="bg-red-500 text-white p-6 rounded-xl">
                <i data-feather="dollar-sign"></i>
                <h3>Revenue</h3>
                <h1 class="text-2xl font-bold">
                    MK <?= number_format($revenue) ?>
                </h1>
            </div>
        </div>

        <!-- EXPORT CARDS -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">

            <a href="export_excel.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <div class="bg-green-500 text-white p-4 rounded-lg">
                    <i data-feather="file-text"></i>
                </div>
                <div>
                    <h3 class="font-bold">Excel Report</h3>
                </div>
            </a>

            <a href="export_pdf.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <div class="bg-red-500 text-white p-4 rounded-lg">
                    <i data-feather="file"></i>
                </div>
                <div>
                    <h3 class="font-bold">PDF Report</h3>
                </div>
            </a>

            <a href="export_word.php"
               class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex items-center gap-4">
                <div class="bg-blue-500 text-white p-4 rounded-lg">
                    <i data-feather="file-plus"></i>
                </div>
                <div>
                    <h3 class="font-bold">Word Report</h3>
                </div>
            </a>
        </div>

        <!-- CHARTS -->
        <div class="grid lg:grid-cols-2 gap-6 mb-8">

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold flex items-center gap-2 mb-4">
                    <i data-feather="bar-chart-2"></i>
                    Monthly Appointments
                </h3>
                <canvas id="appointmentsChart"></canvas>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold flex items-center gap-2 mb-4">
                    <i data-feather="trending-up"></i>
                    Patient Growth
                </h3>
                <canvas id="patientsChart"></canvas>
            </div>
        </div>

        <!-- STATUS + DOCTORS -->
        <div class="grid lg:grid-cols-2 gap-6">

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold flex items-center gap-2 mb-4">
                    <i data-feather="pie-chart"></i>
                    Appointment Status
                </h3>
                <canvas id="statusChart"></canvas>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
                <h3 class="font-bold flex items-center gap-2 mb-4">
                    <i data-feather="award"></i>
                    Top Doctors
                </h3>

                <?php while($doctor = $topDoctors->fetch_assoc()): ?>
                    <div class="border-b py-3">
                        <strong><?= htmlspecialchars($doctor['name']) ?></strong>
                        <p class="text-sm text-gray-500">
                            <?= $doctor['totalAppointments'] ?> appointments
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>

        </div>

    </main>
</div>

<script>
feather.replace();

function toggleTheme(){
    document.documentElement.classList.toggle("dark");

    if(document.documentElement.classList.contains("dark")){
        localStorage.setItem("theme","dark");
    }else{
        localStorage.setItem("theme","light");
    }
}

if(localStorage.getItem("theme")==="dark"){
    document.documentElement.classList.add("dark");
}

/* Charts */
new Chart(document.getElementById("appointmentsChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($appointmentMonths) ?>,
        datasets: [{
            data: <?= json_encode($appointmentTotals) ?>,
            backgroundColor: "#3b82f6"
        }]
    }
});

new Chart(document.getElementById("patientsChart"), {
    type: "line",
    data: {
        labels: <?= json_encode($patientMonths) ?>,
        datasets: [{
            data: <?= json_encode($patientTotals) ?>,
            borderColor: "#10b981"
        }]
    }
});

new Chart(document.getElementById("statusChart"), {
    type: "doughnut",
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: [
                "#22c55e",
                "#f59e0b",
                "#ef4444",
                "#3b82f6"
            ]
        }]
    }
});
</script>

</body>
</html>