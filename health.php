<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Services | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<body>
    <div class="main-content">
        <div class="content-header">
            <h1>Health Related Services Of Pateros</h1>
            <p>Please check the details below for various health-related services available to residents.</p>
        </div>
        <div class="health-services">
            <?php
            $stmt = $conn->prepare("SELECT * FROM programs WHERE category = 'health' ORDER BY title");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="service-card">';
                    echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                    echo '<p>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    
                    if (!empty($row['schedule'])) {
                        echo '<div class="service-info"><i class="fas fa-calendar-alt"></i> ' . htmlspecialchars($row['schedule']) . '</div>';
                    }
                    
                    if (!empty($row['location'])) {
                        echo '<div class="service-info"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</div>';
                    }
                    
                    if (!empty($row['contact_person'])) {
                        echo '<div class="service-info"><i class="fas fa-user"></i> ' . htmlspecialchars($row['contact_person']) . '</div>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="alert alert-info">No health services found.</div>';
            }
            $stmt->close();
            ?>
        </div>
    </div>
</body>
</html>