<?php
include "db.php";

/* =========================
   FETCH DOCTORS
========================= */
$doctors = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.profile_image,
        d.specialization
    FROM users u
    JOIN doctors d ON u.id = d.user_id
    WHERE u.role = 'doctor'
    ORDER BY u.id DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medicare++</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon_io/favicon.ico">


<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>

<script>
tailwind.config = { darkMode: 'class' };
</script>

<style>
html {
    scroll-behavior: smooth;
}

/* ===== GLOBAL ANIMATION STATE ===== */
.reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: all 0.8s ease;
}

.reveal.active {
    opacity: 1;
    transform: translateY(0);
}

/* ===== CARDS ===== */
.card {
    transition: 0.3s ease;
    border-radius: 18px;
}

.card:hover {
    transform: translateY(-6px) scale(1.02);
}

/* ===== HERO ===== */
.hero {
    background: linear-gradient(135deg, rgba(2,6,23,0.85), rgba(37,99,235,0.75)),
    url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3') center/cover;
    background-size: cover;
    background-position: center;
    animation: heroZoom 12s ease-in-out infinite alternate;
}

@keyframes heroZoom {
    from { transform: scale(1); }
    to { transform: scale(1.05); }
}

/* ===== STAGGER DOCTORS ===== */
.doctor-card {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.doctor-card.active {
    opacity: 1;
    transform: translateY(0);
}
</style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white">

<!-- NAV -->
<header class="flex justify-between items-center px-8 py-5 bg-white dark:bg-gray-800 shadow sticky top-0 z-50">
    <h1 class="text-2xl font-bold text-blue-600">MediCare+</h1>

    <nav class="flex gap-6 text-sm font-medium items-center">
        <a href="#home" class="hover:text-blue-500">Home</a>
        <a href="#features" class="hover:text-blue-500">Features</a>
        <a href="#doctors" class="hover:text-blue-500">Doctors</a>

        <button onclick="toggleTheme()" class="px-3 py-1 rounded-lg bg-gray-200 dark:bg-gray-700">
            🌙
        </button>

        <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-xl">
            Login
        </a>
    </nav>
</header>

<!-- HERO -->
<section id="home" class="hero text-white text-center py-28 px-6 reveal">

    <h1 class="text-5xl font-black mb-4">
        Medicare+ booking appointment System
    </h1>

    <p class="max-w-2xl mx-auto text-blue-100 mb-8">
        Manage doctors, patients and appointments in one system.
    </p>

    <a href="register.php" class="bg-white text-blue-700 px-6 py-3 rounded-xl font-semibold">
        Get Started
    </a>
</section>

<!-- STATS -->
<section class="py-16 max-w-6xl mx-auto grid md:grid-cols-4 gap-6 text-center px-6 reveal">

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h2 class="text-3xl font-bold text-blue-600 counter" data-target="1200">0</h2>
        <p>Patients</p>
    </div>

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h2 class="text-3xl font-bold text-green-600 counter" data-target="85">0</h2>
        <p>Doctors</p>
    </div>

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h2 class="text-3xl font-bold text-yellow-500 counter" data-target="3400">0</h2>
        <p>Appointments</p>
    </div>

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h2 class="text-3xl font-bold text-purple-600 counter" data-target="24">0</h2>
        <p>Support</p>
    </div>

</section>

<!-- FEATURES -->
<section id="features" class="py-20 max-w-6xl mx-auto px-6 grid md:grid-cols-3 gap-8 reveal">

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h3 class="text-xl font-bold mb-2">Instant Booking</h3>
        <p class="text-gray-500">Book appointments quickly with real-time availability.</p>
    </div>

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h3 class="text-xl font-bold mb-2">Verified Doctors</h3>
        <p class="text-gray-500">Only approved doctors are listed.</p>
    </div>

    <div class="card bg-white dark:bg-gray-800 p-6 shadow">
        <h3 class="text-xl font-bold mb-2">Smart Dashboard</h3>
        <p class="text-gray-500">Manage hospital operations easily.</p>
    </div>

</section>

<!-- DOCTORS -->
<section id="doctors" class="py-20 bg-gray-100 dark:bg-gray-800 reveal">

    <h2 class="text-3xl font-bold text-center mb-10">Available Doctors</h2>

    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8 px-6">

        <?php $i = 0; while($d = $doctors->fetch_assoc()): $i++; ?>

        <?php
            $img = !empty($d['profile_image'])
                ? $d['profile_image']
                : "https://i.pravatar.cc/150?u=" . $d['id'];
        ?>

        <div class="card doctor-card bg-white dark:bg-gray-900 p-6 text-center shadow"
             style="transition-delay: <?= $i * 80 ?>ms;">

            <img src="<?= htmlspecialchars($img) ?>"
                 class="mx-auto rounded-full mb-4 border-4 border-blue-200 w-24 h-24 object-cover">

            <h3 class="font-bold text-lg">
                <?= htmlspecialchars($d['name']) ?>
            </h3>

            <p class="text-gray-500">
                <?= htmlspecialchars($d['specialization'] ?: 'General') ?>
            </p>

            <a href="register.php"
               class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-xl">
                Book Appointment
            </a>

        </div>

        <?php endwhile; ?>

    </div>
</section>

<!-- CTA -->
<section class="py-24 text-center bg-blue-600 text-white reveal">

    <h2 class="text-4xl font-black mb-4">Start Your Healthcare Journey</h2>

    <p class="mb-6 text-blue-100">
        Join thousands using modern hospital management tools
    </p>

    <a href="register.php" class="bg-white text-blue-700 px-6 py-3 rounded-xl font-bold">
        Create Account
    </a>

</section>

<!-- FOOTER -->
<footer class="py-8 text-center text-gray-500 text-sm">
    © 2026 MediCare System
</footer>

<script>
feather.replace();

/* =========================
   SCROLL REVEAL
========================= */
const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add("active");
        }
    });
}, { threshold: 0.15 });

document.querySelectorAll(".reveal, .doctor-card")
    .forEach(el => observer.observe(el));

/* COUNTERS */
document.querySelectorAll(".counter").forEach(el => {
    let target = +el.dataset.target;
    let count = 0;
    let step = target / 80;

    function update() {
        count += step;
        if (count < target) {
            el.innerText = Math.ceil(count);
            requestAnimationFrame(update);
        } else {
            el.innerText = target;
        }
    }

    update();
});

/* THEME */
function toggleTheme() {
    document.documentElement.classList.toggle("dark");
}
</script>

</body>
</html>