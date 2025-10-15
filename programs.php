<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Programs | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<body>
    <div class="main-content">
        <div class="content-header">
            <h1>Barangay Programs and Projects</h1>
            <p>Explore the various programs offered by Barangay Sto. Rosario Kanluran, Pateros</p>
        </div>

        <div class="program-categories">
            <?php
            $categories = [
                'health' => 'Health Programs',
                'education' => 'Education Programs',
                'environment' => 'Environmental Programs',
                'livelihood' => 'Livelihood Programs',
                'community' => 'Community Development',
                'youth' => 'Youth and Sports'
            ];
            
            foreach ($categories as $category => $title) {
                echo '<div class="category-section">';
                echo '<h2>' . $title . '</h2>';
                
                $stmt = $conn->prepare("SELECT * FROM programs WHERE category = ? ORDER BY title");
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="programs-grid">';
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="program-card">';
                        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                        echo '<p>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                        
                        if (!empty($row['schedule'])) {
                            echo '<div class="program-info"><i class="fas fa-calendar-alt"></i> ' . htmlspecialchars($row['schedule']) . '</div>';
                        }
                        
                        if (!empty($row['location'])) {
                            echo '<div class="program-info"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</div>';
                        }
                        
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-info">No programs in this category yet.</div>';
                }
                
                $stmt->close();
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>