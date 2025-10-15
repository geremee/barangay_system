<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('complaints.php');
}

$complaint_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('complaints.php');
}

$complaint = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaint Details | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<body>
    <div class="main-content">
        <div class="content-header">
            <h1>Complaint/Suggestion Details</h1>
            <p>View the details of your submitted concern</p>
        </div>

        <div class="content-body">
            <div class="details-card">
                <h2>Subject: <?php echo htmlspecialchars($complaint['subject']); ?></h2>

                <p><strong>Status:</strong>
                    <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                        <?php echo ucwords(str_replace('_', ' ', $complaint['status'])); ?>
                    </span>
                </p>

                <p><strong>Date Submitted:</strong> <?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></p>

                <div class="form-group">
                    <label><strong>Message:</strong></label>
                    <div class="static-box"><?php echo nl2br(htmlspecialchars($complaint['message'])); ?></div>
                </div>

                <?php if (!empty($complaint['admin_response'])): ?>
                    <div class="form-group">
                        <label><strong>Admin Response:</strong></label>
                        <div class="static-box"><?php echo nl2br(htmlspecialchars($complaint['admin_response'])); ?></div>
                    </div>
                <?php endif; ?>
                <br>
                <a href="complaints.php" class="btn-view"><i class="fas fa-arrow-left"></i> Back to Complaints</a>
            </div>
        </div>
    </div>
</body>
</html>
