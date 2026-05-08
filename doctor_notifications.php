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
   MARK AS READ
========================= */
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read=1
        WHERE id=? AND user_id=?
    ");
    $stmt->bind_param("ii", $nid, $doctor_id);
    $stmt->execute();

    header("Location: doctor_notifications.php");
    exit();
}

/* =========================
   FETCH NOTIFICATIONS
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id=?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Notifications</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

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

<div class="max-w-5xl mx-auto p-8">

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">

    <h1 class="text-3xl font-bold">🔔 Notifications</h1>

    <a href="doctor.php"
       class="card px-4 py-2 rounded-xl">
        ← Back Dashboard
    </a>

</div>

<!-- LIST -->
<div class="space-y-4">

<?php if ($notifications->num_rows == 0): ?>
    <div class="card p-6 text-center text-gray-500">
        No notifications yet
    </div>
<?php endif; ?>

<?php while($n = $notifications->fetch_assoc()): ?>

<div class="card p-5 flex justify-between items-center">

    <div>
        <p class="font-semibold">
            <?= htmlspecialchars($n['message']) ?>
        </p>

        <p class="text-xs text-gray-500 mt-1">
            <?= $n['created_at'] ?>
        </p>
    </div>

    <div class="flex items-center gap-3">

        <?php if ($n['is_read'] == 0): ?>
            <span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full">
                New
            </span>
        <?php else: ?>
            <span class="text-xs bg-green-500 text-white px-2 py-1 rounded-full">
                Read
            </span>
        <?php endif; ?>

        <?php if ($n['is_read'] == 0): ?>
            <a href="?read=<?= $n['id'] ?>"
               class="text-sm bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg">
                Mark Read
            </a>
        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

</div>

<script>
feather.replace();
</script>

</body>
</html>