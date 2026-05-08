<?php
session_start();
require "db.php";

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = $user['role'];
$name = $user['name'];

/* =========================
   USER DATA
========================= */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

/* =========================
   DEFAULTS
========================= */
$doctor = null;
$patient = null;
$appointments = ['total' => 0];

/* =========================
   PATIENT PROFILE
========================= */
if ($role === "patient") {

    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc() ?? [];

    $appointments = $conn->query("
        SELECT COUNT(*) AS total 
        FROM appointments 
        WHERE patient_id=$user_id
    ")->fetch_assoc();

/* =========================
   DOCTOR PROFILE (CORE FOCUS)
========================= */
} elseif ($role === "doctor") {

    $stmt = $conn->prepare("
        SELECT d.*, u.name, u.email
        FROM doctors d
        JOIN users u ON u.id=d.user_id
        WHERE d.user_id=?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $doctor = $stmt->get_result()->fetch_assoc();

    if (!$doctor) {
        die("Doctor profile not created yet. Please complete setup.");
    }

    $doctor_id = $doctor['id'];

    $appointments = $conn->query("
        SELECT COUNT(*) AS total 
        FROM appointments 
        WHERE doctor_id=$doctor_id
    ")->fetch_assoc();

/* =========================
   ADMIN
========================= */
} else {
    $appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<style>

body {
    background: #e6ebf5;
}

/* NEUMORPHIC CARD */
.card {
    background: #e6ebf5;
    border-radius: 20px;
    box-shadow: 8px 8px 16px #c8ced9,
                -8px -8px 16px #ffffff;
    transition: 0.25s ease;
}

.card:hover {
    transform: translateY(-4px);
}

/* GLASS EFFECT */
.glass {
    background: rgba(255,255,255,0.4);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.3);
}

/* PROFILE AVATAR */
.avatar {
    width: 60px;
    height: 60px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="flex min-h-screen p-6 gap-6">

<!-- SIDEBAR -->
<aside class="w-64 card p-6 glass">

    <h1 class="text-xl font-bold mb-6">🏥 Clinic System</h1>

    <nav class="space-y-3 text-sm">

        <a href="profile.php" class="flex gap-3 p-3 rounded-xl bg-blue-500 text-white">
            <i data-feather="user"></i> Profile
        </a>

        <a href="appointments.php" class="flex gap-3 p-3 rounded-xl">
            <i data-feather="calendar"></i> Appointments
        </a>

        <?php if($role === "doctor"): ?>
        <a href="schedule.php" class="flex gap-3 p-3 rounded-xl">
            <i data-feather="clock"></i> Schedule
        </a>
        <?php endif; ?>

    </nav>

</aside>

<!-- MAIN -->
<main class="flex-1">

<!-- HEADER -->
<div class="card p-6 glass flex justify-between items-center mb-6">

    <div class="flex items-center gap-4">

        <div class="avatar">
            <?= strtoupper($name[0]) ?>
        </div>

        <div>
            <h2 class="text-2xl font-bold"><?= $name ?></h2>
            <p class="text-gray-600"><?= ucfirst($role) ?> Profile</p>
        </div>

    </div>

    <div class="card px-4 py-2">
        <i data-feather="activity"></i>
        <?= $appointments['total'] ?> Appointments
    </div>

</div>

<!-- CONTENT -->
<div class="grid md:grid-cols-3 gap-6">

    <!-- ACCOUNT -->
    <div class="card p-6">

        <h3 class="font-bold mb-3">Account Info</h3>

        <p>Email: <?= $userData['email'] ?></p>
        <p>Status: <?= $userData['status'] ?></p>
        <p>NID: <?= $userData['national_identification'] ?></p>
        <p>Joined: <?= $userData['created_at'] ?></p>

    </div>

    <!-- ROLE DETAILS -->
    <div class="card p-6">

        <h3 class="font-bold mb-3">
            <?= ucfirst($role) ?> Details
        </h3>

        <?php if($role === "patient"): ?>

            <p>Phone: <?= $patient['phone'] ?? '-' ?></p>
            <p>Gender: <?= $patient['gender'] ?? '-' ?></p>
            <p>DOB: <?= $patient['date_of_birth'] ?? '-' ?></p>
            <p>Blood: <?= $patient['blood_group'] ?? '-' ?></p>

        <?php elseif($role === "doctor"): ?>

            <p><b>Specialization:</b> <?= $doctor['specialization'] ?></p>
            <p><b>License:</b> <?= $doctor['license_number'] ?></p>
            <p><b>Phone:</b> <?= $doctor['phone'] ?></p>
            <p><b>Fee:</b> MWK <?= $doctor['consultation_fee'] ?></p>

        <?php else: ?>

            <p>Admin System Access</p>

        <?php endif; ?>

    </div>

    <!-- STATS -->
    <div class="card p-6">

        <h3 class="font-bold mb-3">Quick Stats</h3>

        <p>Role: <?= strtoupper($role) ?></p>
        <p>Total Appointments: <?= $appointments['total'] ?></p>
        <p>Status: Active</p>

    </div>

</div>

<!-- DOCTOR PRIORITY SECTION -->
<?php if($role === "doctor"): ?>

<div class="card p-6 mt-6">

    <h3 class="font-bold mb-4">Doctor Focus Dashboard</h3>

    <div class="grid md:grid-cols-2 gap-4">

        <div class="card p-4">
            <b>Specialization</b><br>
            <?= $doctor['specialization'] ?>
        </div>

        <div class="card p-4">
            <b>Consultation Fee</b><br>
            MWK <?= $doctor['consultation_fee'] ?>
        </div>

    </div>

</div>

<?php endif; ?>

</main>

</div>

<script>
feather.replace();
</script>

</body>
</html>