<?php
session_start();
require "db.php";

/* AUTH */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$success = "";
$error = "";

/* Run once:
ALTER TABLE users ADD profile_image VARCHAR(255) NULL;
*/

/* FETCH DOCTOR */
$stmt = $conn->prepare(" 
    SELECT u.*, 
           d.id AS doctor_id,
           d.phone,
           d.specialization,
           d.license_number,
           d.consultation_fee
    FROM users u
    JOIN doctors d ON d.user_id = u.id
    WHERE u.id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

if (!$doctor) {
    die("Doctor profile not found.");
}

$doctor_id = $doctor['doctor_id'];

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license = trim($_POST['license_number']);
    $fee = trim($_POST['consultation_fee']);

    $profileImage = $doctor['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {

        $targetDir = "uploads/doctors/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFile = $targetDir . $fileName;

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile);
            $profileImage = $targetFile;
        }
    }

    try {
        $conn->begin_transaction();

        $stmt1 = $conn->prepare(" 
            UPDATE users
            SET name=?, email=?, profile_image=?
            WHERE id=?
        ");
        $stmt1->bind_param("sssi", $name, $email, $profileImage, $user_id);
        $stmt1->execute();

        $stmt2 = $conn->prepare(" 
            UPDATE doctors
            SET phone=?, specialization=?, license_number=?, consultation_fee=?
            WHERE id=?
        ");
        $stmt2->bind_param("sssdi", $phone, $specialization, $license, $fee, $doctor_id);
        $stmt2->execute();

        $conn->commit();
        header("Location: doctor_profile.php?updated=1");
        exit();

    } catch(Exception $e) {
        $conn->rollback();
        $error = "Profile update failed.";
    }
}

if (isset($_GET['updated'])) {
    $success = "Profile updated successfully.";
}

/* STATS */
$total = $conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id")
              ->fetch_assoc()['c'];

$approved = $conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id AND status='approved'")
                 ->fetch_assoc()['c'];

$completed = $conn->query("SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id AND status='completed'")
                  ->fetch_assoc()['c'];

/* APPOINTMENTS */
$appointments = $conn->query(" 
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id=$doctor_id
    ORDER BY a.appointment_date DESC
    LIMIT 6
");

$defaultImage = "assets/default-doctor.png";
$profilePic = !empty($doctor['profile_image']) ? $doctor['profile_image'] : $defaultImage;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Profile</title>

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

.card{
    background:white;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.dark .card{
    background:#1f2937;
    color:white;
}

.hero-gradient{
    background: linear-gradient(135deg,#2563eb,#1e40af,#312e81);
}

@keyframes popup {
    from {
        opacity:0;
        transform:scale(0.8);
    }
    to {
        opacity:1;
        transform:scale(1);
    }
}

.animate-popup{
    animation: popup .3s ease;
}
</style>
</head>

<body class="text-gray-800 dark:text-white min-h-screen">

<div class="p-6">

    <?php if($success): ?>
        <div class="bg-green-500 text-white p-3 rounded-xl mb-5">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="bg-red-500 text-white p-3 rounded-xl mb-5">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Doctor Profile</h1>
            <p class="text-gray-500 dark:text-gray-300">Manage your medical account</p>
        </div>

        <div class="flex gap-3">
            <a href="doctor.php" class="bg-blue-600 text-white px-5 py-3 rounded-xl">
                Dashboard
            </a>

            <button onclick="toggleTheme()"
                class="bg-gray-900 text-white px-5 py-3 rounded-xl">
                🌙 Theme
            </button>
        </div>
    </div>

    <!-- HERO PROFILE -->
    <div class="hero-gradient text-white p-8 rounded-3xl mb-8 shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">

            <div class="flex items-center gap-5">
                <img src="<?= htmlspecialchars($profilePic) ?>"
                     class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">

                <div>
                    <h2 class="text-2xl font-bold">
                        Dr. <?= htmlspecialchars($doctor['name']) ?>
                    </h2>
                    <p><?= htmlspecialchars($doctor['specialization']) ?></p>
                    <p><?= htmlspecialchars($doctor['email']) ?></p>
                </div>
            </div>

            <button onclick="openModal()"
                class="bg-white text-blue-600 px-5 py-3 rounded-xl font-bold">
                Edit Profile
            </button>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid md:grid-cols-3 gap-6 mb-8">

        <div class="card p-6">
            <h3>Total Appointments</h3>
            <h2 class="text-3xl font-bold text-blue-600"><?= $total ?></h2>
        </div>

        <div class="card p-6">
            <h3>Approved</h3>
            <h2 class="text-3xl font-bold text-green-600"><?= $approved ?></h2>
        </div>

        <div class="card p-6">
            <h3>Completed</h3>
            <h2 class="text-3xl font-bold text-purple-600"><?= $completed ?></h2>
        </div>

    </div>

    <!-- RECENT APPOINTMENTS -->
    <div class="card p-6">
        <h2 class="text-xl font-bold mb-4">Recent Appointments</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="p-3 text-left">Patient</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $appointments->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td class="p-3"><?= date('M d, Y h:i A', strtotime($row['appointment_date'])) ?></td>
                        <td class="p-3"><?= ucfirst($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

    <!-- DOCTOR DETAILS -->
<div class="card p-6 mb-8">

    <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold">Doctor Details</h2>
        <span class="text-sm px-3 py-1 rounded-full bg-blue-100 text-blue-700">
            Profile Overview
        </span>
    </div>

    <div class="grid md:grid-cols-2 gap-5">

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">Name</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['name']) ?></span>
        </div>

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">Email</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['email']) ?></span>
        </div>

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">Phone</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['phone']) ?></span>
        </div>

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">Specialization</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['specialization']) ?></span>
        </div>

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">License Number</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['license_number']) ?></span>
        </div>

        <div class="flex justify-between border-b pb-2">
            <span class="text-gray-500">Consultation Fee</span>
            <span class="font-semibold"><?= htmlspecialchars($doctor['consultation_fee']) ?></span>
        </div>

    </div>

</div>
<!-- EDIT MODAL -->
<div id="editModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">

    <div class="card w-full max-w-3xl p-6 animate-popup max-h-[90vh] overflow-y-auto relative">

        <button onclick="closeModal()"
            class="absolute top-4 right-4 text-red-500 text-xl">
            ✕
        </button>

        <h2 class="text-2xl font-bold mb-6">Edit Profile</h2>

        <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">

            <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" class="p-3 rounded-xl border">
            <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" class="p-3 rounded-xl border">
            <input type="text" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" class="p-3 rounded-xl border">
            <input type="text" name="specialization" value="<?= htmlspecialchars($doctor['specialization']) ?>" class="p-3 rounded-xl border">
            <input type="text" name="license_number" value="<?= htmlspecialchars($doctor['license_number']) ?>" class="p-3 rounded-xl border">
            <input type="number" step="0.01" name="consultation_fee" value="<?= htmlspecialchars($doctor['consultation_fee']) ?>" class="p-3 rounded-xl border">

            <div class="md:col-span-2">
                <label class="block mb-2 font-semibold">Profile Picture</label>
                <input type="file" name="profile_image" class="w-full p-3 border rounded-xl">
            </div>

            <button name="update_profile"
                class="md:col-span-2 bg-blue-600 text-white p-3 rounded-xl font-bold">
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
feather.replace();

function openModal(){
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal(){
    document.getElementById('editModal').classList.add('hidden');
}

window.onclick = function(e){
    const modal = document.getElementById('editModal');
    if(e.target === modal){
        closeModal();
    }
}

function toggleTheme(){
    document.documentElement.classList.toggle('dark');

    if(document.documentElement.classList.contains('dark')){
        localStorage.setItem('theme','dark');
    }else{
        localStorage.setItem('theme','light');
    }
}

if(localStorage.getItem('theme') === 'dark'){
    document.documentElement.classList.add('dark');
}
</script>

</body>
</html>
