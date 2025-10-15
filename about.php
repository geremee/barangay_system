<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<style>
.imgg {
    background-image: url('assets/images/group1.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    opacity: 0.5; 
}


</style>
<body>
    <div class="imgg"></div>
    <div class="main-content">
        <div class="content-header">
            <h1>Barangay Sto. Rosario Kanluran's Officials</h1>
            <b><p style="color: black;">Meet our dedicated barangay officials</p></b>
        </div>

        <div class="officials-grid">
            <?php
            $stmt = $conn->prepare("SELECT * FROM officials ORDER BY display_order, name");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="official-card">';
                    
                    if (!empty($row['image_path'])) {
                        echo '<img src="' . htmlspecialchars($row['image_path']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                    } else {
                        echo '<div class="official-placeholder"><i class="fas fa-user"></i></div>';
                    }
                    
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['position']) . '</p>';
                    
                    if (!empty($row['bio'])) {
                        echo '<div class="official-bio">' . nl2br(htmlspecialchars($row['bio'])) . '</div>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="alert alert-info">No officials information available.</div>';
            }
            $stmt->close();
            ?>
        </div>

        <div class="barangay-info">
            <h2>About Our Barangay</h2>
            <p>Barangay Sto. Rosario Kanluran is a vibrant community dedicated to serving its residents with modern governance approaches. Our barangay hall is located at Pateros.</p>
            <p>Office Hours: Monday to Friday, 8:00 AM to 5:00 PM</p>
            <p>Contact Number: wala pang nakukuha</p>
            <p>Email: Di pa natatanong</p>
            <a href="Youtube.com">Youtubeeee
            </a>
        </div>
    </div>
</body>
</html>