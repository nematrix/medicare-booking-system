<?php
session_start();
require "db.php";

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor = $_SESSION['user'];
$doctor_id = $doctor['id'];

/* =========================
   STATS
========================= */
$stmt = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=?) AS total,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='pending') AS pending,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='approved') AS approved,
        (SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND status='completed') AS completed
");
$stmt->bind_param("iiii", $doctor_id, $doctor_id, $doctor_id, $doctor_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

/* =========================
   TODAY APPOINTMENTS
========================= */
$today = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE doctor_id=? AND DATE(appointment_date)=CURDATE()
");
$today->bind_param("i", $doctor_id);
$today->execute();
$todayAppointments = $today->get_result()->fetch_assoc()['total'];

/* =========================
   APPOINTMENTS
========================= */
$stmt = $conn->prepare("
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id=?
    ORDER BY a.appointment_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();

/* =========================
   NOTIFICATIONS (UNREAD)
========================= */
$notif = $conn->prepare("
    SELECT * FROM notifications
    WHERE user_id=? AND is_read=0
    ORDER BY created_at DESC
");
$notif->bind_param("i", $doctor_id);
$notif->execute();
$notifications = $notif->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Dashboard</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<style>
.card{
    background:#e6ebf5;
    border-radius:18px;
    box-shadow:8px 8px 16px #c8ced9,-8px -8px 16px #fff;
}
.toast{
    position:fixed;
    top:20px;
    right:20px;
    background:#111827;
    color:white;
    padding:14px 18px;
    border-radius:12px;
    display:none;
    z-index:9999;
}
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220]">

<!-- TOAST -->
<div id="toast" class="toast"></div>

<audio id="notifSound">
    <source src="https://assets.mixkit.co/sfx/preview/mixkit-software-interface-start-2574.mp3" type="audio/mpeg">
</audio>

<div class="flex min-h-screen">

<!-- SIDEBAR -->
<aside class="w-64 card m-4 p-6">
    <h1 class="font-bold text-xl mb-6">🩺 Doctor Panel</h1>

    <div class="space-y-3 text-sm">

        <div class="flex items-center gap-3 p-3 bg-blue-500 text-white rounded-xl">
            <i data-feather="grid"></i> Dashboard
        </div>

        <a href="doctor_appointments.php" class="block p-3 rounded-xl hover:bg-gray-200">
            <i data-feather="calendar"></i> Appointments
        </a>

        <a href="patients.php" class="block p-3 rounded-xl hover:bg-gray-200">
            <i data-feather="users"></i> Patients
        </a>

    </div>
</aside>

<!-- MAIN -->
<main class="flex-1 p-10">

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">

    <h2 class="text-3xl font-bold">Welcome, <?= $doctor['name'] ?></h2>

    <!-- NOTIFICATION BELL -->
    <div class="relative card p-3">
        <i data-feather="bell"></i>
        <span id="notifCount"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-2 rounded-full">
            <?= $notifications->num_rows ?>
        </span>
    </div>

</div>

<!-- STATS -->
<div class="grid grid-cols-4 gap-4 mb-8">

<div class="card p-4">Total <?= $stats['total'] ?></div>
<div class="card p-4">Pending <?= $stats['pending'] ?></div>
<div class="card p-4">Approved <?= $stats['approved'] ?></div>
<div class="card p-4">Completed <?= $stats['completed'] ?></div>

</div>

<!-- APPOINTMENTS -->
<div class="card p-6">

<h3 class="font-bold mb-4">Recent Appointments</h3>

<table class="w-full text-sm">
<tr class="border-b">
<th class="p-2">Patient</th>
<th class="p-2">Date</th>
<th class="p-2">Status</th>
</tr>

<?php while($a = $appointments->fetch_assoc()): ?>
<tr class="border-b">
<td class="p-2"><?= $a['patient_name'] ?></td>
<td class="p-2"><?= $a['appointment_date'] ?></td>
<td class="p-2"><?= $a['status'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>

</main>
</div>

<script>
feather.replace();

/* =========================
   REAL-TIME CHECK
========================= */
let lastCount = <?= $notifications->num_rows ?>;

function checkNotifications() {
    fetch("fetch_notifications.php?doctor_id=<?= $doctor_id ?>")
        .then(res => res.json())
        .then(data => {

            if (data.count > lastCount) {

                showToast("🔔 New appointment booked!");
                playSound();

                document.getElementById("notifCount").innerText = data.count;

                lastCount = data.count;
            }
        });
}

setInterval(checkNotifications, 5000);

/* =========================
   TOAST
========================= */
function showToast(msg) {
    let t = document.getElementById("toast");
    t.innerText = msg;
    t.style.display = "block";

    setTimeout(() => {
        t.style.display = "none";
    }, 4000);
}

/* =========================
   SOUND
========================= */
function playSound() {
    document.getElementById("notifSound").play();
}
</script>

</body>
</html>