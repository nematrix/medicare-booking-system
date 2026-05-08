<?php
session_start();
include "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

require "notification_functions.php";

/* =========================
   PROFILE IMAGE
========================= */
$defaultImage = "assets/default-user.png";

$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$profileImage = (!empty($res['profile_image']))
    ? $res['profile_image']
    : $defaultImage;

/* =========================
   APPOINTMENT STATS
========================= */
function countAppointments($conn, $user_id, $status = null) {
    if ($status) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM appointments
            WHERE patient_id=? AND status=?
        ");
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM appointments
            WHERE patient_id=?
        ");
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total'];
}

$total = countAppointments($conn, $user_id);
$pending = countAppointments($conn, $user_id, "pending");
$approved = countAppointments($conn, $user_id, "approved");
$rejected = countAppointments($conn, $user_id, "rejected");

/* =========================
   RECENT APPOINTMENTS
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM appointments
    WHERE patient_id=?
    ORDER BY appointment_date DESC
    LIMIT 6
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

/* =========================
   NOTIFICATIONS
========================= */
$notifications = getNotifications($conn, $user_id);
$unread = getUnreadCount($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' };
</script>

<style>
.card {
    background: #e6ebf5;
    border-radius: 18px;
    box-shadow: 8px 8px 16px #c8ced9,
                -8px -8px 16px #ffffff;
    transition: 0.3s ease;
}

.dark .card {
    background: #111827;
    box-shadow: 8px 8px 16px #0b0f1a,
                -8px -8px 16px #1a2235;
}

.card:hover {
    transform: translateY(-4px);
}
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex flex-col lg:flex-row min-h-screen">

<!-- SIDEBAR -->
<aside class="w-full lg:w-64 card m-2 lg:m-4 p-4 lg:p-6">

    <h1 class="text-xl font-bold mb-6 text-center lg:text-left">
        🏥 Patient Panel
    </h1>

    <nav class="grid grid-cols-2 lg:grid-cols-1 gap-3 text-sm">

        <a href="patient.php"
           class="flex items-center gap-2 p-3 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white">
            <i data-feather="grid"></i> Dashboard
        </a>

        <a href="patient_profile.php"
           class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
            <i data-feather="user"></i> Profile
        </a>

        <a href="book_appointment.php"
           class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
            <i data-feather="calendar"></i> Book
        </a>

        <a href="patient_appointment.php"
           class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
            <i data-feather="list"></i> Appointments
        </a>

        <a href="cancel_appointment.php"
           class="flex items-center gap-2 p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700">
            <i data-feather="trash"></i> Cancel
        </a>

        <a href="logout.php"
           class="flex items-center gap-2 p-3 rounded-xl text-red-500 hover:bg-gray-200 dark:hover:bg-gray-700">
            <i data-feather="log-out"></i> Logout
        </a>

    </nav>
</aside>

<!-- MAIN -->
<main class="flex-1 p-4 sm:p-6 lg:p-10">

<!-- HEADER -->
<div class="flex flex-col lg:flex-row justify-between gap-4 mb-8">

    <div>
        <h2 class="text-2xl lg:text-3xl font-bold">
            Welcome, <?= htmlspecialchars($user['name']) ?>
        </h2>
        <p class="text-gray-500">Patient dashboard overview</p>
    </div>

    <div class="flex items-center gap-3">

        <!-- NOTIFICATIONS -->
        <div class="relative">

            <button onclick="toggleNotif()" class="relative card p-3 rounded-xl">
                <i data-feather="bell"></i>

                <?php if($unread > 0): ?>
                    <span id="notifBadge"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-2 rounded-full">
                        <?= $unread ?>
                    </span>
                <?php endif; ?>
            </button>

            <div id="notifBox"
                 class="hidden absolute right-0 mt-3 w-72 sm:w-80 card p-4 z-50 max-h-96 overflow-y-auto">

                <h3 class="font-bold mb-3">Notifications</h3>

                <?php if(!empty($notifications)): ?>
                    <?php foreach($notifications as $n): ?>
                        <div class="border-b py-2">
                            <p class="text-sm"><?= htmlspecialchars($n['message']) ?></p>
                            <small class="text-gray-500">
                                <?= date("d M Y h:i A", strtotime($n['created_at'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No notifications</p>
                <?php endif; ?>

            </div>
        </div>

        <!-- PROFILE WITH IMAGE -->
        <div class="flex items-center gap-3 card px-4 py-2 rounded-xl">

            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-300">
                <img src="<?= htmlspecialchars($profileImage) ?>"
                     class="w-full h-full object-cover"
                     onerror="this.src='assets/default-user.png'">
            </div>

            <div class="text-sm">
                <p class="font-semibold"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-gray-500 text-xs">Patient</p>
            </div>
        </div>

        <button onclick="document.documentElement.classList.toggle('dark')"
                class="card px-4 py-2 rounded-xl">
            🌙 Theme
        </button>

    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

<?php
function kpi($icon,$label,$value,$color){
    echo "
    <div class='card p-6'>
        <div class='flex gap-4'>
            <div class='w-12 h-12 rounded-xl flex items-center justify-center text-white bg-gradient-to-br $color'>
                <i data-feather='$icon'></i>
            </div>
            <div>
                <p class='text-sm text-gray-500'>$label</p>
                <h3 class='text-2xl font-bold'>$value</h3>
            </div>
        </div>
    </div>";
}

kpi("activity","Total",$total,"from-blue-500 to-indigo-600");
kpi("clock","Pending",$pending,"from-yellow-400 to-orange-500");
kpi("check-circle","Approved",$approved,"from-green-500 to-emerald-600");
?>

</div>

<!-- TABLE -->
<div class="card p-4 sm:p-6 overflow-x-auto">

<h3 class="font-semibold mb-4">Recent Appointments</h3>

<table class="w-full text-sm min-w-[600px]">

<thead>
<tr class="border-b">
<th class="p-2">Date</th>
<th class="p-2">Reason</th>
<th class="p-2">Status</th>
</tr>
</thead>

<tbody>
<?php while($row = $appointments->fetch_assoc()): ?>
<tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-800">

<td class="p-2"><?= $row['appointment_date'] ?></td>
<td class="p-2"><?= htmlspecialchars($row['reason']) ?></td>

<td class="p-2">
<span class="px-3 py-1 rounded-full text-white text-xs
<?= $row['status']=='approved'?'bg-green-500':'' ?>
<?= $row['status']=='pending'?'bg-yellow-500':'' ?>
<?= $row['status']=='rejected'?'bg-red-500':'' ?>">
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

<script>
function toggleNotif() {
    document.getElementById("notifBox").classList.toggle("hidden");
}
feather.replace();
</script>

</body>
</html>