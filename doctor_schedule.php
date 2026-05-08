<?php
session_start();
include "db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['user']['id'];
$message = "";

/* SAVE SCHEDULE */
if (isset($_POST['save'])) {

    $day = $_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $duration = $_POST['slot_duration'];

    $stmt = $conn->prepare("
        INSERT INTO doctor_schedules
        (doctor_id, day_of_week, start_time, end_time, slot_duration, is_available)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $stmt->bind_param("isssi", $doctor_id, $day, $start, $end, $duration);

    $message = $stmt->execute()
        ? "Schedule saved successfully."
        : "Failed to save schedule.";
}

/* LOAD SCHEDULE */
$stmt = $conn->prepare("
    SELECT *
    FROM doctor_schedules
    WHERE doctor_id=?
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday')
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$schedules = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Schedule</title>
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
</style>
</head>

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] text-gray-900 dark:text-white">

<div class="flex flex-col md:flex-row min-h-screen">

<!-- SIDEBAR -->
<aside class="w-full md:w-64 card m-3 md:m-4 p-4 md:p-6">

    <h1 class="text-lg md:text-xl font-bold mb-4 md:mb-6 text-blue-700 dark:text-blue-400">
        👨‍⚕️ Doctor Panel
    </h1>

    <nav class="flex md:flex-col gap-2 overflow-x-auto md:overflow-visible text-sm">

        <a href="doctor.php"
           class="flex items-center gap-2 p-3 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-gray-700 whitespace-nowrap">
            <i data-feather="grid"></i> Dashboard
        </a>

        <a href="doctor_schedule.php"
           class="flex items-center gap-2 p-3 rounded-xl bg-blue-600 text-white whitespace-nowrap">
            <i data-feather="clock"></i> Schedule
        </a>

        <a href="logout.php"
           class="flex items-center gap-2 p-3 rounded-xl text-red-600 hover:bg-red-100 dark:hover:bg-gray-800 whitespace-nowrap">
            <i data-feather="log-out"></i> Logout
        </a>

    </nav>

</aside>

<!-- MAIN -->
<main class="flex-1 p-4 md:p-10">

<h2 class="text-2xl md:text-3xl font-bold mb-6 text-blue-700 dark:text-blue-300">
    Doctor Schedule
</h2>

<?php if($message): ?>
<div class="card p-4 mb-6 text-sm text-green-600 dark:text-green-400">
    <?= $message ?>
</div>
<?php endif; ?>

<!-- GRID -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

<!-- FORM -->
<div class="card p-5 md:p-6">

<h3 class="font-semibold mb-4 text-blue-700 dark:text-blue-300">
    Set Availability
</h3>

<form method="POST" class="space-y-4">

    <select name="day" required
        class="w-full p-3 rounded-xl bg-white/40 dark:bg-black/40 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-white">

        <option value="">Select Day</option>
        <option>Monday</option>
        <option>Tuesday</option>
        <option>Wednesday</option>
        <option>Thursday</option>
        <option>Friday</option>

    </select>

    <input type="time" name="start_time" required
        class="w-full p-3 rounded-xl bg-white/40 dark:bg-black/40 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-white">

    <input type="time" name="end_time" required
        class="w-full p-3 rounded-xl bg-white/40 dark:bg-black/40 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-white">

    <input type="number" name="slot_duration" value="30"
        class="w-full p-3 rounded-xl bg-white/40 dark:bg-black/40 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-white"
        placeholder="Slot duration (minutes)">

    <button name="save"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl font-bold">
        Save Schedule
    </button>

</form>

</div>

<!-- LIST -->
<div class="card p-5 md:p-6">

<h3 class="font-semibold mb-4 text-blue-700 dark:text-blue-300">
    My Schedule
</h3>

<div class="space-y-3 max-h-[500px] overflow-y-auto">

<?php while($row = $schedules->fetch_assoc()): ?>

<div class="p-3 border-b border-gray-300 dark:border-gray-700">

    <div class="flex justify-between text-gray-800 dark:text-gray-200">
        <strong class="text-blue-700 dark:text-blue-300">
            <?= $row['day_of_week'] ?>
        </strong>

        <span class="text-xs text-blue-500 dark:text-blue-400">
            <?= $row['slot_duration'] ?> min
        </span>
    </div>

    <div class="text-sm text-gray-700 dark:text-gray-300">
        <?= $row['start_time'] ?> - <?= $row['end_time'] ?>
    </div>

</div>

<?php endwhile; ?>

</div>

</div>

</div>

</main>
</div>

<script>
feather.replace();
</script>

</body>
</html>