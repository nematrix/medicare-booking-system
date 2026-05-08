<?php
session_start();
require "db.php";

/* AUTH */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$success = "";
$error = "";

/* FETCH PATIENT */
$stmt = $conn->prepare("
    SELECT u.*, p.*
    FROM users u
    LEFT JOIN patients p ON p.user_id = u.id
    WHERE u.id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Patient profile not found.");
}

/* DEFAULT PROFILE IMAGE */
$defaultImage = "assets/default-patient.png";
$profilePic = !empty($user['profile_image']) ? $user['profile_image'] : $defaultImage;

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['date_of_birth']);
    $address = trim($_POST['address']);
    $blood = trim($_POST['blood_group']);

    $profileImage = $user['profile_image'];

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile_image']['name'])) {

        $dir = "uploads/patients/";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $target = $dir . $fileName;

        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);
            $profileImage = $target;
        }
    }

    try {
        $conn->begin_transaction();

        $u = $conn->prepare("
            UPDATE users
            SET name=?, email=?, profile_image=?
            WHERE id=?
        ");
        $u->bind_param("sssi", $name, $email, $profileImage, $user_id);
        $u->execute();

        $p = $conn->prepare("
            UPDATE patients
            SET phone=?, gender=?, date_of_birth=?, address=?, blood_group=?
            WHERE user_id=?
        ");
        $p->bind_param(
            "sssssi",
            $phone,
            $gender,
            $dob,
            $address,
            $blood,
            $user_id
        );
        $p->execute();

        $conn->commit();
        header("Location: patient_profile.php?updated=1");
        exit();

    } catch(Exception $e) {
        $conn->rollback();
        $error = "Profile update failed.";
    }
}

if (isset($_GET['updated'])) {
    $success = "Profile updated successfully.";
}

/* PROFILE COMPLETION */
$fields = [
    $user['phone'],
    $user['gender'],
    $user['date_of_birth'],
    $user['address'],
    $user['blood_group']
];

$filled = count(array_filter($fields));
$percent = ($filled / count($fields)) * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Profile</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' };
</script>

<style>
body {
    background: linear-gradient(135deg,#eff6ff,#dbeafe,#eef2ff);
}

.dark body {
    background: linear-gradient(135deg,#0f172a,#1e1b4b,#111827);
}

.card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.dark .card {
    background: #1f2937;
    color: white;
}

.hero-gradient {
    background: linear-gradient(135deg,#2563eb,#1e40af,#312e81);
}

.animate-popup {
    animation: popup .3s ease;
}

@keyframes popup {
    from {opacity:0; transform:scale(0.85);}
    to {opacity:1; transform:scale(1);}
}
</style>
</head>

<body class="text-gray-800 dark:text-white min-h-screen">

<?php if($success): ?>
<div class="fixed top-5 right-5 bg-green-500 text-white px-5 py-3 rounded-xl z-50">
    <?= $success ?>
</div>
<?php endif; ?>

<div class="p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Patient Profile</h1>
            <p class="text-gray-500 dark:text-gray-300">Manage your medical account</p>
        </div>

        <div class="flex gap-3">
            <a href="patient.php" class="bg-blue-600 text-white px-5 py-3 rounded-xl">
                Dashboard
            </a>

            <button onclick="toggleTheme()" class="bg-gray-900 text-white px-5 py-3 rounded-xl">
                🌙 Theme
            </button>

            <button onclick="openModal()" class="bg-green-600 text-white px-5 py-3 rounded-xl">
                Edit Profile
            </button>
        </div>
    </div>

    <!-- HERO -->
    <div class="hero-gradient text-white p-8 rounded-3xl mb-8 shadow-xl flex items-center gap-6">

        <img src="<?= $profilePic ?>"
             class="w-24 h-24 rounded-full object-cover border-4 border-white shadow">

        <div>
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($user['name']) ?></h2>
            <p>Patient Account</p>

            <div class="w-64 bg-white/20 rounded-full mt-2">
                <div class="bg-green-400 h-2 rounded-full" style="width: <?= $percent ?>%"></div>
            </div>

            <small><?= round($percent) ?>% profile complete</small>
        </div>
    </div>

    <!-- DETAILS -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-bold mb-5">Patient Details</h2>

        <div class="grid md:grid-cols-2 gap-5">

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Email</span>
                <span class="font-semibold"><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Phone</span>
                <span class="font-semibold"><?= htmlspecialchars($user['phone']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Gender</span>
                <span class="font-semibold"><?= htmlspecialchars($user['gender']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">DOB</span>
                <span class="font-semibold"><?= htmlspecialchars($user['date_of_birth']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Blood Group</span>
                <span class="font-semibold"><?= htmlspecialchars($user['blood_group']) ?></span>
            </div>

            <div class="md:col-span-2 flex justify-between border-b pb-2">
                <span class="text-gray-500">Address</span>
                <span class="font-semibold"><?= htmlspecialchars($user['address']) ?></span>
            </div>

        </div>
    </div>

</div>

<!-- MODAL -->
<div id="editModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center p-4">

    <div class="card w-full max-w-2xl p-6 animate-popup">

        <div class="flex justify-between mb-5">
            <h2 class="text-xl font-bold">Edit Profile</h2>
            <button onclick="closeModal()" class="text-red-500">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">

            <input name="name" value="<?= $user['name'] ?>" class="p-3 rounded-xl border">
            <input name="email" value="<?= $user['email'] ?>" class="p-3 rounded-xl border">
            <input name="phone" value="<?= $user['phone'] ?>" class="p-3 rounded-xl border">

            <select name="gender" class="p-3 rounded-xl border">
                <option>male</option>
                <option>female</option>
                <option>other</option>
            </select>

            <input type="date" name="date_of_birth" value="<?= $user['date_of_birth'] ?>" class="p-3 rounded-xl border">
            <input name="blood_group" value="<?= $user['blood_group'] ?>" class="p-3 rounded-xl border">

            <div class="md:col-span-2">
                <input type="file" name="profile_image" class="p-3 border rounded-xl w-full">
            </div>

            <textarea name="address" class="md:col-span-2 p-3 rounded-xl border"><?= $user['address'] ?></textarea>

            <button name="update_profile" class="md:col-span-2 bg-blue-600 text-white p-3 rounded-xl">
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

function toggleTheme(){
    document.documentElement.classList.toggle('dark');
}
</script>

</body>
</html>