<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$errors = [];
$title = $content = '';
$is_urgent = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;

    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";

    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $image_name = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_size = $_FILES['image']['size'];
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        if (!in_array($image_ext, $allowed_ext)) {
            $errors[] = "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
        } elseif ($image_size > 5 * 1024 * 1024) {
            $errors[] = "Image size must not exceed 5MB.";
        } else {
            $upload_dir = '../uploads/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_file_name = uniqid('ann_') . '.' . $image_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($image_tmp, $destination)) {
                // Store relative path for DB
                $image_path = 'uploads/images/' . $new_file_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, is_urgent, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $title, $content, $is_urgent, $image_path);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement added successfully!";
            redirect("announcements.php");
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Announcement | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Add Announcement</h1>
            <p>Create a new barangay announcement</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="add-announcement.php" method="POST" enctype="multipart/form-data" class="announcement-form">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>" />
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" rows="8" required><?php echo htmlspecialchars($content); ?></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_urgent" name="is_urgent" <?php echo $is_urgent ? 'checked' : ''; ?> />
                <label for="is_urgent">Mark as Urgent</label>
            </div>

            <div class="form-group">
                <label for="image">Upload Image (optional)</label>
                <input type="file" id="image" name="image" accept="image/*" />
                <small>Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
            </div>

            <div class="form-actions">
                <a href="announcements.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Publish Announcement</button>
            </div>
        </form>
    </div>
</body>
</html>
