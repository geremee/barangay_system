<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect("announcements.php");
}

$announcement_id = (int)$_GET['id'];
$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect("announcements.php");
}

$announcement = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, is_urgent = ? WHERE id = ?");
        $stmt->bind_param("ssii", $title, $content, $is_urgent, $announcement_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement updated successfully!";
            redirect("announcements.php");
        } else {
            $errors[] = "Failed to update announcement. Please try again.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Announcement | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Edit Announcement</h1>
            <p>Update the announcement details below</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="edit-announcement.php?id=<?php echo $announcement_id; ?>" method="POST" class="announcement-form">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" rows="8" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_urgent" name="is_urgent" <?php echo $announcement['is_urgent'] ? 'checked' : ''; ?>>
                <label for="is_urgent">Mark as Urgent</label>
            </div>
            
            <div class="form-actions">
                <a href="announcements.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Update Announcement</button>
            </div>
        </form>
    </div>
</body>
</html>