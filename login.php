<?php
session_start();
include "db.php";

$error = "";

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user'] = $user;

        if ($user['role'] == "admin") {
            header("Location: admin.php");
        } elseif ($user['role'] == "doctor") {
            header("Location: doctor.php");
        } else {
            header("Location: patient.php");
        }
        exit();

    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon_io/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' }
</script>

<style>
body {
    background: #e6ebf5;
}

.dark body {
    background: #0B1220;
}

.card {
    background: #e6ebf5;
    box-shadow: 8px 8px 16px #c8ced9,
                -8px -8px 16px #ffffff;
    border-radius: 20px;
}

.dark .card {
    background: #111827;
    box-shadow: 8px 8px 16px #0b0f1a,
                -8px -8px 16px #1a2235;
}

.input {
    background: #e6ebf5;
    box-shadow: inset 4px 4px 8px #c8ced9,
                inset -4px -4px 8px #ffffff;
}

.dark .input {
    background: #0f172a;
    box-shadow: inset 4px 4px 8px #0b0f1a,
                inset -4px -4px 8px #1a2235;
}
</style>

</head>

<body class="flex items-center justify-center min-h-screen">

<div class="card grid grid-cols-1 md:grid-cols-2 w-full max-w-5xl overflow-hidden">

    <!-- LEFT -->
    <div class="hidden md:flex flex-col justify-center p-10 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">

        <h1 class="text-3xl font-bold mb-4 flex items-center gap-2">
            <i data-feather="activity"></i>
            Hospital System
        </h1>

        <p class="text-white/80 mb-6">
            Secure medical platform for doctors, patients, and administrators.
        </p>

        <ul class="space-y-2 text-sm text-white/80">
            <li>✔ Appointment scheduling system</li>
            <li>✔ Doctor approval workflow</li>
            <li>✔ Patient management dashboard</li>
            <li>✔ Real-time notifications</li>
        </ul>

    </div>

    <!-- RIGHT -->
    <div class="p-8 md:p-10">

        <h2 class="text-2xl font-bold mb-1 flex items-center gap-2">
            <i data-feather="log-in"></i>
            Welcome Back
        </h2>

        <p class="text-gray-500 dark:text-gray-300 mb-6 text-sm">
            Login to access your dashboard
        </p>

        <!-- ERROR -->
        <?php if($error): ?>
            <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-600 border border-red-300">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="POST" class="space-y-4">

            <div>
                <label class="text-sm">Email</label>
                <input type="email" name="email" required
                       class="input w-full p-3 rounded-xl mt-1">
            </div>

            <div>
                <label class="text-sm">Password</label>

                <div class="relative">
                    <input id="password" type="password" name="password" required
                           class="input w-full p-3 rounded-xl mt-1 pr-10">

                    <span onclick="togglePass()"
                          class="absolute right-3 top-4 cursor-pointer text-gray-500">
                        <i data-feather="eye"></i>
                    </span>
                </div>
            </div>

            <button name="login"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl font-bold">
                Login
            </button>

        </form>

        <p class="mt-5 text-sm text-gray-500 text-center">
            No account?
            <a href="register.php" class="text-blue-600 font-semibold">Create one</a>
        </p>

        <!-- THEME -->
        <button onclick="document.documentElement.classList.toggle('dark')"
                class="mt-6 w-full p-2 rounded-xl bg-gray-200 dark:bg-gray-700">
            Toggle Theme
        </button>

    </div>

</div>

<script>
feather.replace();

function togglePass() {
    const pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}
</script>

</body>
</html>