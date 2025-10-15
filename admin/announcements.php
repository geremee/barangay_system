<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Clear messages after displaying
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Fetch announcements
$announcements = [];
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
if ($result) {
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle delete
if (isset($_GET['delete'])) {
    $announcement_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $announcement_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Announcement deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete announcement: " . $stmt->error;
    }
    
    $stmt->close();
    redirect("announcements.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>Announcements</h1>
                <p>Manage barangay announcements</p>
            </div>
            <div class="header-right">
                <a href="add-announcement.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="announcements-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No announcements yet. <a href="add-announcement.php">Create one</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 100))); ?><?php echo strlen($announcement['content']) > 100 ? '...' : ''; ?></td>
                                <td>
                                    <span class="badge <?php echo $announcement['is_urgent'] ? 'urgent' : 'normal'; ?>">
                                        <?php echo $announcement['is_urgent'] ? 'Urgent' : 'Normal'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></td>
                                <td>
                                    <a href="edit-announcement.php?id=<?php echo $announcement['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="announcements.php?delete=<?php echo $announcement['id']; ?>" class="btn-delete" onclick="return confirm('Delete this announcement?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>