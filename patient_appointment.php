<?php
session_start();
include "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$patient_id = $user['id'];

$status_filter = $_GET['status'] ?? "";

/* QUERY */
$sql = "SELECT * FROM appointments WHERE patient_id = ?";

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
}

$sql .= " ORDER BY appointment_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($status_filter)) {
    $stmt->bind_param("is", $patient_id, $status_filter);
} else {
    $stmt->bind_param("i", $patient_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Appointments</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
}

.dark .card {
    background: #111827;
    box-shadow: 8px 8px 16px #0b0f1a, -8px -8px 16px #1a2235;
}
</style>

</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="min-h-screen p-4 sm:p-6 lg:p-10">

<!-- HEADER -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

    <div>
        <h1 class="text-2xl sm:text-3xl font-bold">My Appointments</h1>
        <p class="text-gray-500">All your bookings</p>
    </div>

    <div class="flex flex-wrap gap-2">

        <a href="patient.php" class="card px-4 py-2 rounded-xl text-center">
            ← Dashboard
        </a>

        <button onclick="document.documentElement.classList.toggle('dark')"
                class="card px-4 py-2 rounded-xl">
            🌙 Theme
        </button>

    </div>

</div>

<!-- FILTERS -->
<div class="flex flex-wrap gap-2 mb-6">

    <a href="patient_appointment.php" class="card px-4 py-2 rounded-xl">All</a>
    <a href="?status=pending" class="card px-4 py-2 rounded-xl text-yellow-600">Pending</a>
    <a href="?status=approved" class="card px-4 py-2 rounded-xl text-green-600">Approved</a>
    <a href="?status=rejected" class="card px-4 py-2 rounded-xl text-red-600">Rejected</a>
    <a href="?status=completed" class="card px-4 py-2 rounded-xl text-blue-600">Completed</a>

</div>

<!-- TABLE -->
<div class="card p-4 sm:p-6 overflow-x-auto">

<table class="w-full min-w-[600px] text-sm">

<thead>
<tr class="border-b border-gray-300 dark:border-gray-700">
    <th class="p-3 text-left">Doctor ID</th>
    <th class="p-3 text-left">Date</th>
    <th class="p-3 text-left">Reason</th>
    <th class="p-3 text-left">Status</th>
</tr>
</thead>

<tbody>

<?php if ($result->num_rows > 0): ?>

    <?php while($row = $result->fetch_assoc()): ?>

    <tr class="border-b border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800">

        <td class="p-3">
            <?= $row['doctor_id'] ?>
        </td>

        <td class="p-3">
            <?= $row['appointment_date'] ?>
        </td>

        <td class="p-3">
            <?= htmlspecialchars($row['reason']) ?>
        </td>

        <td class="p-3">
            <span class="px-3 py-1 rounded-full text-xs text-white
                <?= $row['status']=='approved'?'bg-green-500':'' ?>
                <?= $row['status']=='pending'?'bg-yellow-500':'' ?>
                <?= $row['status']=='rejected'?'bg-red-500':'' ?>
                <?= $row['status']=='completed'?'bg-blue-500':'' ?>
            ">
                <?= ucfirst($row['status']) ?>
            </span>
        </td>

    </tr>

    <?php endwhile; ?>

<?php else: ?>

    <tr>
        <td colspan="4" class="text-center p-6 text-gray-500">
            No appointments found
        </td>
    </tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<script>
feather.replace();
</script>

</body>
</html>