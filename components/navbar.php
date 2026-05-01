<!-- Navbar Component -->

<link rel="stylesheet" href="../assets/css/index.css">
<nav class="navbar">
    <div class="nav-container">
        <a href="../views/clients.php" class="nav-logo">HNF</a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            ☰
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="../controllers/ctr_clients.php" class="nav-link">Clients</a></li>
            <li><a href="../controllers/ctr_attendance.php" class="nav-link">Attendance</a></li>
            <li><a href="../controllers/ctr_subscriptions.php" class="nav-link">Subscriptions</a></li>
            <li><a href="../controllers/ctr_sales.php" class="nav-link">Sales</a></li>
            <!-- <li><a href="" class="nav-link">Other Items</a></li> -->
        </ul>
    </div>
</nav>

<script>
    // Toggle mobile menu
    const navToggle = document.getElementById("navToggle");
    const navMenu = document.getElementById("navMenu");

    navToggle.addEventListener("click", function () {
        navMenu.classList.toggle("active");
    });
</script>