<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $category = sanitizeInput($_POST['category']);
    $schedule = sanitizeInput($_POST['schedule']);
    $location = sanitizeInput($_POST['location']);
    $contact_person = sanitizeInput($_POST['contact_person']);
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($category)) $errors[] = "Category is required";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO programs (title, description, category, schedule, location, contact_person) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $title, $description, $category, $schedule, $location, $contact_person);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Program added successfully!";
            redirect("programs.php");
        } else {
            $errors[] = "Failed to add program. Please try again.";
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
    <title>Add Program | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Add New Program</h1>
            <p>Fill out the form to add a new barangay program</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="add-program.php" method="POST" class="program-form">
            <div class="form-group">
                <label for="title">Program Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="health">Health</option>
                    <option value="education">Education</option>
                    <option value="environment">Environment</option>
                    <option value="livelihood">Livelihood</option>
                    <option value="community">Community Development</option>
                    <option value="youth">Youth & Sports</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="schedule">Schedule</label>
                <input type="text" id="schedule" name="schedule" placeholder="e.g., Every Monday, 9AM-5PM">
            </div>
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Barangay Hall, Health Center">
            </div>
            
            <div class="form-group">
                <label for="contact_person">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" placeholder="Name of contact person">
            </div>
            
            <div class="form-actions">
                <a href="programs.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Save Program</button>
            </div>
        </form>
    </div>
</body>
</html>