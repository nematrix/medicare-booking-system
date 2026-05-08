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
   FILTER
========================= */
$statusFilter = $_GET['status'] ?? '';

$sql = "
SELECT a.*, 
       p.name AS patient, 
       d.name AS doctor
FROM appointments a
JOIN users p ON a.patient_id = p.id
JOIN users d ON a.doctor_id = d.id
";

if (!empty($statusFilter)) {
    $sql .= " WHERE a.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$sql .= " ORDER BY a.created_at DESC";

$q = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
</script>

<style>
.card {
    background: #e6ebf5;
    border-radius: 20px;
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

.status {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.table-row:hover {
    background: rgba(59,130,246,0.08);
}
</style>

</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white p-10">

<div class="max-w-7xl mx-auto">

<!-- HEADER -->
<div class="flex justify-between items-center mb-8">

    <div>
        <h2 class="text-3xl font-bold flex items-center gap-2">
            <i data-feather="calendar"></i>
            Appointments
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            Manage patient appointments
        </p>
    </div>

    <div class="flex items-center gap-3">

        <!-- BACK -->
        <a href="admin.php"
           class="card bg-gray-700 text-white px-4 py-2 rounded-xl flex items-center gap-2">
            <i data-feather="arrow-left"></i>
            Back
        </a>

        <!-- FILTER -->
        <form method="GET">
            <select name="status"
                    onchange="this.form.submit()"
                    class="p-2 rounded-xl text-black">

                <option value="">All</option>
                <option value="pending" <?= $statusFilter=='pending'?'selected':'' ?>>Pending</option>
                <option value="approved" <?= $statusFilter=='approved'?'selected':'' ?>>Approved</option>
                <option value="cancelled" <?= $statusFilter=='cancelled'?'selected':'' ?>>Cancelled</option>

            </select>
        </form>

    </div>

</div>

<!-- TABLE -->
<div class="card p-6 overflow-x-auto">

<table class="w-full text-sm">

<thead class="text-gray-500 dark:text-gray-400">
<tr>
<th class="p-3 text-left">ID</th>
<th class="p-3 text-left">Patient</th>
<th class="p-3 text-left">Doctor</th>
<th class="p-3 text-left">Date</th>
<th class="p-3 text-left">Status</th>
<th class="p-3 text-left">Actions</th>
</tr>
</thead>

<tbody>

<?php if ($q->num_rows > 0): ?>

<?php while($a = $q->fetch_assoc()):

$status = strtolower($a['status']);

$badge = match($status) {
    'approved'  => 'bg-green-500 text-white',
    'cancelled' => 'bg-red-500 text-white',
    default     => 'bg-yellow-500 text-white'
};

$date = date("M d, Y H:i", strtotime($a['appointment_date']));
?>

<tr class="border-t dark:border-gray-700 table-row">

<td class="p-3"><?= $a['id'] ?></td>

<td class="p-3 font-medium">
    <?= htmlspecialchars($a['patient']) ?>
</td>

<td class="p-3">
    <?= htmlspecialchars($a['doctor']) ?>
</td>

<td class="p-3 text-gray-500 dark:text-gray-300">
    <?= $date ?>
</td>

<td class="p-3">
    <span class="status <?= $badge ?>">
        <?= ucfirst($status) ?>
    </span>
</td>

<td class="p-3 flex gap-3">

<?php if ($status == 'pending'): ?>

<a href="update_appointment.php?id=<?= $a['id'] ?>&status=approved"
   class="text-green-500 hover:scale-110 transition">
    <i data-feather="check-circle"></i>
</a>

<a href="update_appointment.php?id=<?= $a['id'] ?>&status=cancelled"
   class="text-red-500 hover:scale-110 transition">
    <i data-feather="x-circle"></i>
</a>

<?php else: ?>

<span class="text-gray-400 text-xs">
    No actions
</span>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="6" class="text-center py-10 text-gray-500">
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

/* Dark mode sync */
if (localStorage.getItem("theme") === "dark") {
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>