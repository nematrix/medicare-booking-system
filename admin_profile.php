<?php
session_start();
require "db.php";

/* AUTH */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$success = "";
$error = "";

/* DEFAULT IMAGE */
$defaultImage = "assets/default-admin.png";

/* FETCH ADMIN */
$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    die("Admin profile not found.");
}

/* PROFILE IMAGE */
$profilePic = !empty($admin['profile_image']) ? $admin['profile_image'] : $defaultImage;

/* SAFE OUTPUT HELPER */
function e($value) {
    return htmlspecialchars($value ?? '');
}

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    $profileImage = $admin['profile_image'];

    /* IMAGE UPLOAD */
    if (!empty($_FILES['profile_image']['name'])) {

        $dir = "uploads/admins/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetFile = $dir . $fileName;

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile);
            $profileImage = $targetFile;
        }
    }

    try {
        $stmt = $conn->prepare("
            UPDATE users
            SET name=?, email=?, profile_image=?
            WHERE id=?
        ");
        $stmt->bind_param("sssi", $name, $email, $profileImage, $user_id);
        $stmt->execute();

        header("Location: admin_profile.php?updated=1");
        exit();

    } catch(Exception $e) {
        $error = "Update failed";
    }
}

if (isset($_GET['updated'])) {
    $success = "Profile updated successfully";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Profile</title>

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
    transition: 0.3s;
}
.dark .card {
    background: #1f2937;
    color: white;
}

.hero {
    background: linear-gradient(135deg,#2563eb,#1e40af,#312e81);
}

.modal {
    backdrop-filter: blur(10px);
}

.popup {
    animation: pop .25s ease;
}

@keyframes pop {
    from {transform:scale(.9);opacity:0;}
    to {transform:scale(1);opacity:1;}
}
</style>
</head>

<body class="text-gray-800 dark:text-white min-h-screen">

<div class="p-6">

    <!-- ALERTS -->
    <?php if($success): ?>
        <div class="bg-green-500 text-white p-3 rounded-xl mb-4">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="bg-red-500 text-white p-3 rounded-xl mb-4">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">

        <div>
            <h1 class="text-3xl font-bold">Admin Profile</h1>
            <p class="text-gray-500 dark:text-gray-300">System administration panel</p>
        </div>

        <div class="flex gap-3">

            <a href="admin.php"
               class="bg-blue-600 text-white px-5 py-3 rounded-xl flex items-center gap-2">
                <i data-feather="home"></i> Dashboard
            </a>

            <button onclick="toggleTheme()"
                class="bg-gray-900 text-white px-5 py-3 rounded-xl">
                🌙 Theme
            </button>

            <button onclick="openModal()"
                class="bg-green-600 text-white px-5 py-3 rounded-xl">
                Edit Profile
            </button>

        </div>
    </div>

    <!-- HERO -->
    <div class="hero text-white p-8 rounded-3xl mb-8 shadow-xl flex items-center gap-6">

        <img src="<?= e($profilePic) ?>"
             class="w-20 h-20 rounded-full object-cover border-4 border-white">

        <div>
            <h2 class="text-2xl font-bold">
                <?= e($admin['name']) ?>
            </h2>
            <p class="text-blue-100"><?= e($admin['email']) ?></p>
            <p class="text-blue-200 text-sm">Administrator Account</p>
        </div>

    </div>

    <!-- MODERN DETAILS DIV -->
    <div class="card p-6 mb-8">

        <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold">Admin Details</h2>
            <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full">
                System Overview
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Name</span>
                <span class="font-semibold"><?= e($admin['name']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Email</span>
                <span class="font-semibold"><?= e($admin['email']) ?></span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Role</span>
                <span class="font-semibold">Admin</span>
            </div>

            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Account ID</span>
                <span class="font-semibold"><?= e($admin['id']) ?></span>
            </div>

        </div>
    </div>

</div>

<!-- EDIT MODAL -->
<div id="modal"
     class="hidden fixed inset-0 bg-black/60 flex items-center justify-center p-4 modal">

    <div class="card w-full max-w-2xl p-6 popup relative">

        <button onclick="closeModal()"
                class="absolute top-3 right-3 text-red-500 text-xl">✕</button>

        <h2 class="text-xl font-bold mb-4">Edit Profile</h2>

        <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">

            <input type="text" name="name"
                   value="<?= e($admin['name']) ?>"
                   class="p-3 rounded-xl border">

            <input type="email" name="email"
                   value="<?= e($admin['email']) ?>"
                   class="p-3 rounded-xl border">

            <div class="md:col-span-2">
                <label class="block mb-2 font-semibold">Profile Picture</label>
                <input type="file" name="profile_image"
                       class="w-full p-3 border rounded-xl">
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
    document.getElementById("modal").classList.remove("hidden");
}

function closeModal(){
    document.getElementById("modal").classList.add("hidden");
}

function toggleTheme(){
    document.documentElement.classList.toggle("dark");
}
</script>

</body>
</html>