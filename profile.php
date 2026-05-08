<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$id = $user['id'];

/* SAFE USER FETCH */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();

/* UPDATE PROFILE */
if (isset($_POST['update_profile'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $id);
    $stmt->execute();

    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;

    header("Location: profile.php");
    exit();
}

/* CHANGE PASSWORD (simple version - improve later with hashing) */
$msg = "";

if (isset($_POST['change_password'])) {

    $old = $_POST['old_password'];
    $new = $_POST['new_password'];

    if ($old === $admin['password']) {

        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $new, $id);
        $stmt->execute();

        $msg = "Password updated successfully";

    } else {
        $msg = "Old password is incorrect";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>
<link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>

<style>

/* BACKGROUND */
body {
    background: linear-gradient(135deg, #e6ebf5, #f7f9ff);
    font-family: sans-serif;
}

/* GLASS CARD */
.card {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 20px;
    box-shadow: 10px 10px 25px rgba(0,0,0,0.08),
                -10px -10px 25px rgba(255,255,255,0.8);
    transition: 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

/* INPUT */
.input {
    width: 100%;
    padding: 12px;
    border-radius: 14px;
    border: 1px solid #ddd;
    background: rgba(255,255,255,0.6);
    outline: none;
}

/* BUTTONS */
.btn-blue {
    background: #2563eb;
    color: white;
    padding: 10px 16px;
    border-radius: 12px;
    transition: 0.3s;
}

.btn-blue:hover {
    background: #1d4ed8;
}

.btn-green {
    background: #16a34a;
    color: white;
    padding: 10px 16px;
    border-radius: 12px;
    transition: 0.3s;
}

.btn-green:hover {
    background: #15803d;
}

/* HEADER BANNER */
.header {
    background: linear-gradient(135deg, #2563eb, #60a5fa);
    color: white;
    padding: 25px;
    border-radius: 20px;
}

.avatar {
    width: 60px;
    height: 60px;
    background: white;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}

</style>
</head>

<body>

<div class="max-w-5xl mx-auto p-6">

    <!-- HEADER -->
    <div class="header flex justify-between items-center mb-6">

        <div class="flex items-center gap-4">

            <div class="avatar">
                <?= strtoupper($admin['name'][0]) ?>
            </div>

            <div>
                <h1 class="text-2xl font-bold"><?= $admin['name'] ?></h1>
                <p class="text-sm opacity-80">Admin Profile Settings</p>
            </div>

        </div>

        <div class="text-sm">
            ID: #<?= $admin['id'] ?>
        </div>

    </div>

    <!-- GRID -->
    <div class="grid md:grid-cols-2 gap-6">

        <!-- PROFILE UPDATE -->
        <div class="card p-6">

            <h2 class="text-xl font-bold mb-4">Update Profile</h2>

            <form method="POST" class="space-y-4">

                <input class="input" type="text" name="name"
                    value="<?= $admin['name'] ?>" placeholder="Full Name">

                <input class="input" type="email" name="email"
                    value="<?= $admin['email'] ?>" placeholder="Email">

                <button class="btn-blue" name="update_profile">
                    Save Changes
                </button>

            </form>

        </div>

        <!-- PASSWORD -->
        <div class="card p-6">

            <h2 class="text-xl font-bold mb-4">Change Password</h2>

            <?php if($msg): ?>
                <p class="text-sm text-blue-600 mb-3"><?= $msg ?></p>
            <?php endif; ?>

            <form method="POST" class="space-y-4">

                <input class="input" type="password" name="old_password"
                    placeholder="Old Password">

                <input class="input" type="password" name="new_password"
                    placeholder="New Password">

                <button class="btn-green" name="change_password">
                    Update Password
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>