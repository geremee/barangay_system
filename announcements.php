<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Announcements | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: -1;
    opacity: 0.5;
}

.announcement-container{
    display: flex;
    gap: 20px;
    justify-content: flex-start;
}

.announcement-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.announcement-card:hover {
    transform: translateY(-10px);
}

.announcement-card.urgent {
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
    font-size: 24px;
    color: #333;
}

.announcement-header small {
    display: block;
    font-size: 14px;
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
    margin-bottom: 15px;
}

.announcement-image img {
    max-width: 30%;
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
</style>
<body>
    <div class="imgg"></div>
    <div class="main-content">
        <div class="content-header">
            <h1>Barangay Announcements</h1>
            <p>Stay updated with the latest news and announcements from Barangay Sto. Rosario Kanluran, Pateros</p>
        </div>

        <div class="announcements-section">
            <div class="announcements-container">
                <?php
                
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $limit = 10;
                $offset = ($page - 1) * $limit;

                
                $total_result = $conn->query("SELECT COUNT(*) AS total FROM announcements");
                $total_rows = $total_result->fetch_assoc()['total'];
                $total_pages = ceil($total_rows / $limit);

                
                $stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->bind_param("ii", $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $urgent_class = $row['is_urgent'] ? 'urgent' : '';
                        echo '<div class="announcement-card ' . $urgent_class . '">';

                        if ($row['is_urgent']) {
                            echo '<span class="urgent-badge"><i class="fas fa-exclamation-circle"></i> Urgent</span>';
                        }

                        echo '<div class="announcement-header">';
                        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                        echo '<small>' . date('F j, Y \a\t h:i A', strtotime($row['created_at'])) . '</small>';
                        echo '</div>';

                        if (!empty($row['image_path'])) {
                            
                            $image_url = htmlspecialchars($row['image_path']);
                            echo '<div class="announcement-image">';
                            echo '<img src="' . $image_url . '" alt="Announcement Image">';
                            echo '</div>';
                        }

                        echo '<div class="announcement-content">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-info">No announcements found.</div>';
                }
                $stmt->close();
                ?>
            </div>

            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="announcements.php?page=<?php echo $page - 1; ?>" class="page-link"><i class="fas fa-chevron-left"></i> Previous</a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="announcements.php?page=<?php echo $page + 1; ?>" class="page-link">Next <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
