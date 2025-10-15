<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect("complaints.php");
}

$complaint_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT c.*, u.full_name, u.email, u.contact_number 
                       FROM complaints c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect("complaints.php");
}

$complaint = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Details | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>Complaint Details</h1>
                <p>View and manage complaint details</p>
            </div>
            <div class="header-right">
                <a href="complaints.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Complaints
                </a>
            </div>
        </div>
        
        <div class="complaint-details">
            <div class="detail-section">
                <h2>Complaint Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Complaint ID:</span>
                    <span class="detail-value">#<?php echo $complaint['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                        <?php echo ucwords(str_replace('_', ' ', $complaint['status'])); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date Submitted:</span>
                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($complaint['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Updated:</span>
                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($complaint['updated_at'])); ?></span>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>Complaint Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($complaint['subject']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Message:</span>
                    <div class="detail-value message-content"><?php echo nl2br(htmlspecialchars($complaint['message'])); ?></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>User Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Submitted By:</span>
                    <span class="detail-value"><?php echo $complaint['full_name']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo $complaint['email']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Number:</span>
                    <span class="detail-value"><?php echo $complaint['contact_number']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($complaint['admin_response'])): ?>
                <div class="detail-section">
                    <h2>Admin Response</h2>
                    <div class="detail-row">
                        <div class="detail-value admin-response"><?php echo nl2br(htmlspecialchars($complaint['admin_response'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>
            

            </div>
            </div>
        </div>
    </div>
</body>
</html>