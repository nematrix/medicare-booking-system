<?php
session_start();
include "db.php";

/* =========================
   AUTH (ADMIN ONLY)
========================= */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   VALIDATE ID
========================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID");
}

$id = (int) $_GET['id'];

/* =========================
   FETCH USER
========================= */
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}

$success = "";
$error = "";

/* =========================
   UPDATE USER (ADMIN CONTROL)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    if ($name === "" || $email === "") {
        $error = "Name and email are required";
    } elseif (!in_array($role, ['admin', 'doctor', 'patient'])) {
        $error = "Invalid role selected";
    } else {

        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, role = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $name, $email, $role, $id);

        if ($stmt->execute()) {

            /* password reset (optional) */
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $passStmt = $conn->prepare("
                    UPDATE users SET password = ? WHERE id = ?
                ");
                $passStmt->bind_param("si", $hashed, $id);
                $passStmt->execute();
            }

            $success = "User updated successfully";

            $user['name'] = $name;
            $user['email'] = $email;
            $user['role'] = $role;

        } else {
            $error = "Update failed. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit User</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
</script>

<style>
.card {
    background: #e6ebf5;
    box-shadow: 8px 8px 16px #c8ced9,
                -8px -8px 16px #ffffff;
    border-radius: 18px;
    transition: 0.25s ease;
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

<body class="bg-[#e6ebf5] dark:bg-[#0B1220] dark:text-white flex items-center justify-center min-h-screen">

<div class="card w-full max-w-md p-8">

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">

<h2 class="text-2xl font-bold">Edit User</h2>

<!-- BACK BUTTON -->
<a href="admin.php"
   class="flex items-center gap-2 px-3 py-2 bg-blue-500 text-white rounded-xl">
   <i data-feather="arrow-left"></i> Back
</a>

</div>

<!-- SUCCESS -->
<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-100 text-green-600 rounded-xl">
    <?= $success ?>
</div>
<?php endif; ?>

<!-- ERROR -->
<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-100 text-red-600 rounded-xl">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST">

<!-- NAME -->
<label class="text-sm">Full Name</label>
<input type="text" name="name"
    value="<?= htmlspecialchars($user['name']) ?>"
    class="w-full p-3 mb-4 border rounded-xl dark:bg-gray-800" required>

<!-- EMAIL -->
<label class="text-sm">Email</label>
<input type="email" name="email"
    value="<?= htmlspecialchars($user['email']) ?>"
    class="w-full p-3 mb-4 border rounded-xl dark:bg-gray-800" required>

<!-- ROLE -->
<label class="text-sm">Role</label>
<select name="role"
    class="w-full p-3 mb-4 border rounded-xl dark:bg-gray-800">

    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
    <option value="doctor" <?= $user['role']=='doctor'?'selected':'' ?>>Doctor</option>
    <option value="patient" <?= $user['role']=='patient'?'selected':'' ?>>Patient</option>

</select>

<!-- PASSWORD RESET -->
<label class="text-sm">Reset Password (optional)</label>
<input type="password" name="password"
    placeholder="Leave blank to keep current password"
    class="w-full p-3 mb-6 border rounded-xl dark:bg-gray-800">

<!-- BUTTONS -->
<div class="flex justify-between">

<a href="users.php"
   class="px-4 py-2 bg-gray-300 rounded-xl text-black">
   Cancel
</a>

<button class="px-6 py-2 bg-blue-500 text-white rounded-xl">
Update
</button>

</div>

</form>

</div>

<script>
feather.replace();

/* DARK MODE SYNC */
if (localStorage.getItem("theme") === "dark") {
    document.documentElement.classList.add("dark");
}
</script>

</body>
</html>