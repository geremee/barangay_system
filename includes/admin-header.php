
<div class="admin-head">
    <div class="admin-nav__bar">
        <h1>Barangay Admin Panel</h1>

        <div class="admin-user dropdown">
            <span class="admin-name">
                Welcome, <?php echo $_SESSION['full_name']; ?> (Admin) <i class="fas fa-caret-down"></i>
            </span>
            <div class="dropdown-menu">
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .admin-head {
        background-color:  #2c3e50;
        color: white;
        padding: 1rem 2rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-left: 250px;
        position: relative;
    }

    @media (max-width: 768px) {
        .admin-head {
            margin-left: 0;
        }
    }

    .admin-nav__bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .admin-user.dropdown {
        position: relative;
        cursor: pointer;
    }

    .admin-name {
        font-weight: 600;
        color: white;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 130%;
        right: 0;
        background-color: white;
        min-width: 150px;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.querySelector('.admin-user.dropdown');
        const trigger = dropdown.querySelector('.admin-name');

        trigger.addEventListener('click', function (e) {
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
