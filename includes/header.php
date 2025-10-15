<body>
    

</html><div class="head" style="background-color: #300b3fff">
    <div class="nav__bar">
        <h1>Barangay Sto. Rosario Kanluran</h1>
        <p>A Modern Approach of Barangay Information and Documents System</p>
<?php if (isLoggedIn()): ?>
    <div class="user-info dropdown">
        <span class="user-name">Welcome, <?php echo $_SESSION['full_name']; ?> <i class="fas fa-caret-down"></i></span>
        <div class="dropdown-menu">
            <a href="user-settings.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
<?php endif; ?>

    </div>
</div>

<style>
.dropdown {
    position: relative;
    display: inline-block;
    cursor: pointer;
}

.user-name {
    font-weight: 600;
    color: white;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 120%;
    right: 0;
    background-color: white;
    min-width: 140px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    border-radius: 5px;
    overflow: hidden;
    z-index: 1000;
}

.dropdown-menu a {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-size: 0.9rem;
}

.dropdown-menu a:hover {
    background-color: #f2f2f2;
}


.dropdown.open .dropdown-menu {
    display: block;
}

.user-info.dropdown {
    margin-top: 1rem;
    position: absolute;
    top: 1.5rem;
    right: 2rem;
}


</style>

<script>

    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.querySelector('.user-info.dropdown');
        const userName = dropdown.querySelector('.user-name');

        userName.addEventListener('click', function (e) {
            e.stopPropagation(); 
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
</script>

</script>
</body>