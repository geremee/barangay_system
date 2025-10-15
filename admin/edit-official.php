<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect("officials.php");
}

$official_id = (int)$_GET['id'];
$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT * FROM officials WHERE id = ?");
$stmt->bind_param("i", $official_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect("officials.php");
}

$official = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $position = sanitizeInput($_POST['position']);
    $bio = sanitizeInput($_POST['bio']);
    $display_order = (int)$_POST['display_order'];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($position)) $errors[] = "Position is required";
    
    $image_path = $official['image_path'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/officials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $file_name = uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                if (!empty($official['image_path']) && file_exists('../' . $official['image_path'])) {
                    unlink('../' . $official['image_path']);
                }
                $image_path = 'uploads/officials/' . $file_name;
            } else {
                $errors[] = "Failed to upload photo.";
            }
        } else {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE officials SET name = ?, position = ?, image_path = ?, bio = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $position, $image_path, $bio, $display_order, $official_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Official updated successfully!";
            redirect("officials.php");
        } else {
            $errors[] = "Failed to update official. Please try again.";
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
    <title>Edit Official | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Edit Barangay Official</h1>
            <p>Update the official details below</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="edit-official.php?id=<?php echo $official_id; ?>" method="POST" enctype="multipart/form-data" class="official-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($official['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($official['position']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="photo">Photo</label>
                <?php if (!empty($official['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($official['image_path']); ?>" alt="<?php echo htmlspecialchars($official['name']); ?>" class="current-photo">
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/*">
                <small>Leave blank to keep current photo</small>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio/Description</label>
                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($official['bio']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="<?php echo $official['display_order']; ?>" min="0">
                <small>Lower numbers appear first</small>
            </div>
            
            <div class="form-actions">
                <a href="officials.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Update Official</button>
            </div>
        </form>
    </div>
</body>
</html>