<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | BRGY System</title>
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

.announcements-container {
    display: flex;                   
    flex-wrap: wrap;                  
    gap: 20px;                        
    justify-content: flex-start;      
}

.announcement-box {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    max-width: 700px;                 
    flex: 1 1 300px;                  
    margin-bottom: 20px;              
}

.announcement-box:hover {
    transform: translateY(-5px);
}

.announcement-box.urgent {
    border-left: 5px solid #ff4d4d;
}

.urgent-badge {
    background: #ff4d4d;
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    margin-right: 10px;
}

.announcement-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
    line-height: 1.4;
}

.announcement-header small {
    display: block;
    font-size: 12px;
    color: #777;
}

.announcement-content {
    margin-top: 10px;
    font-size: 14px;
    line-height: 1.4;
    color: #555;
    max-height: 80px;
    overflow-wrap: break-word;  
    word-wrap: break-word;      
    word-break: break-word;  
    text-overflow: ellipsis; 
}

.announcement-image {
    text-align: center;
    margin-bottom: 10px;
}

.announcement-image img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.pagination {
    text-align: center;
    margin-top: 30px;
}

.page-link {
    background: #007bff;
    color: #fff;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin: 0 10px;
}

.page-link:hover {
    background: #0056b3;
}

.page-info {
    font-size: 16px;
    color: #333;
}

.alert {
    padding: 10px;
    background-color: #f8d7da;
    border-radius: 5px;
    margin: 10px 0;
    color: #721c24;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.announcements-section h2 {
    margin-bottom: 1.5rem;
    color: var(--dark-color);
}

</style>

<body>
    <div class="imgg"></div>
    <div class="main-content">

        <div class="announcements-section">
            <h2>Latest Announcements</h2>
            <div class="announcements-container">
                <?php
                $stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Add the "urgent" class if the announcement is urgent
                        $urgent_class = $row['is_urgent'] ? 'urgent' : '';
                        echo '<div class="announcement-box ' . $urgent_class . '">';
                        
                        // Show "urgent" badge if it's an urgent announcement
                        if ($row['is_urgent']) {
                            echo '<span class="urgent-badge"><i class="fas fa-exclamation-circle"></i> Urgent</span>';
                        }

                        echo '<div class="announcement-header">';
                        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                        echo '<small>' . date('M d, Y', strtotime($row['created_at'])) . '</small>';
                        echo '</div>';

                        // Display image if it exists
                        if (!empty($row['image_path'])) {
                            $image_url = htmlspecialchars($row['image_path']);
                            echo '<div class="announcement-image">';
                            echo '<img src="' . $image_url . '" alt="Announcement Image">';
                            echo '</div>';
                        }

                        // Display content
                        echo '<div class="announcement-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-info">No announcements yet.</div>';
                }
                $stmt->close();
                ?>
            </div>
            <a href="announcements.php" class="btn-view-all">View All Announcements</a>
        </div>

        <div class="quick-links">
            <h2>Quick Links</h2>
            <div class="links-container">
                <a href="request.php" class="quick-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Request Documents</span>
                </a>
                <!-- <a href="health.php" class="quick-link">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Services</span>
                </a>
                <a href="programs.php" class="quick-link">
                    <i class="fas fa-project-diagram"></i>
                    <span>Programs</span>
                </a> -->
                <a href="about.php" class="quick-link">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
