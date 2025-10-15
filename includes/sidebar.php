<div class="side" style="background-color: #630a63ff">
    <div class="sidebar">
        <div class="top">
            <img src="images/logo.jpg" alt="BRGY Logo" class="logo" style="width: 170px;height: 170px;">
            <h1 class="top__text" style="    font-size: 1.5rem;margin-bottom: 0.5rem;">Barangay Sto. Rosario Kanluran</h1>
        </div>

        <div class="side__content">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="request.php"><i class="fas fa-file-alt"></i> Request Documents</a>
            <!-- <a href="health.php"><i class="fas fa-heartbeat"></i> Health</a> -->
            <!-- <a href="programs.php"><i class="fas fa-project-diagram"></i> Programs</a> -->
            <a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="track.php"><i class="fas fa-search"></i> Track my Request</a>
            <a href="complaints.php"><i class="fas fa-info"></i> Complaints and Suggestions</a>
            <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
            <?php if (!isLoggedIn()): ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php else: ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    
</style>
<!-- style="background-color: #720372ff -->