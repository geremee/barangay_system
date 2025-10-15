<?php
require_once '../includes/config.php';


if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}


if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "Invalid document ID";
    redirect('documents.php');
}

$document_id = (int)$_GET['id'];


try {
    $stmt = $conn->prepare("SELECT d.*, u.full_name, u.email 
                           FROM documents d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE d.id = ?");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Document not found";
        redirect('documents.php');
    }

    $document = $result->fetch_assoc();
    $stmt->close();
    

    $file_stmt = $conn->prepare("SELECT * FROM document_files WHERE document_id = ?");
    $file_stmt->bind_param("i", $document_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    $files = [];
    
    while ($file = $file_result->fetch_assoc()) {
        $files[] = $file;
    }
    $file_stmt->close();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching document details";
    redirect('documents.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Details | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>Document Details <span class="detail-id">#<?php echo htmlspecialchars($document['id']); ?></span></h1>
                <p>View and manage document information</p>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="document-details">
            <div class="detail-section">
                <h2>Document Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span class="detail-value id-value">#<?php echo htmlspecialchars($document['id']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tracking Number:</span>
                    <span class="detail-value id-value">#<?php echo htmlspecialchars($document['tracking_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Document Type:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucwords(str_replace(['-', '_'], ' ', $document['document_type']))); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-badge status-<?php echo htmlspecialchars($document['status']); ?>">
                        <?php echo htmlspecialchars(ucfirst($document['status'])); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Request Date:</span>
                    <span class="detail-value">
                        <?php 
                        if (!empty($document['request_date']) && $document['request_date'] != '0000-00-00 00:00:00') {
                            echo htmlspecialchars(date('M d, Y h:i A', strtotime($document['request_date'])));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Purpose:</span>
                    <span class="detail-value"><?php echo !empty($document['purpose']) ? htmlspecialchars($document['purpose']) : 'N/A'; ?></span>
                </div>
                <?php if (!empty($document['business_name'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Business Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($document['business_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($document['admin_notes'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Admin Notes:</span>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($document['admin_notes'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h2>User Information</h2>
                <div class="detail-row">
                    <span class="detail-label">User Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($document['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">User Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($document['email']); ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h2>Attached Files</h2>
                <?php if (!empty($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <div class="detail-row file-row">
                            <span class="detail-label"><?php echo htmlspecialchars(ucwords(str_replace(['-', '_'], ' ', $file['file_type']))); ?>:</span>
                            <span class="detail-value">
                                <?php if (file_exists('../' . $file['file_path'])): ?>
                                    <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn-below">
                                        <i class="fas fa-download"></i> Download File
                                    </a>
                                    <small>(<?php echo htmlspecialchars(pathinfo($file['file_path'], PATHINFO_EXTENSION)); ?>)</small>
                                <?php else: ?>
                                    <span class="text-warning">File not found</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="detail-row">
                        <span class="detail-value">No files uploaded for this document.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-actions">
                <a href="documents.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</body>
</html>