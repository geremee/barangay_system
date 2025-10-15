<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect("programs.php");
}

$program_id = (int)$_GET['id'];
$errors = [];
$success = '';

$stmt = $conn->prepare("SELECT * FROM programs WHERE id = ?");
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect("programs.php");
}

$program = $result->fetch_assoc();
$stmt->close();

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
        $stmt = $conn->prepare("UPDATE programs SET title = ?, description = ?, category = ?, schedule = ?, location = ?, contact_person = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $description, $category, $schedule, $location, $contact_person, $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Program updated successfully!";
            redirect("programs.php");
        } else {
            $errors[] = "Failed to update program. Please try again.";
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
    <title>Edit Program | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Edit Program</h1>
            <p>Update the program details below</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="edit-program.php?id=<?php echo $program_id; ?>" method="POST" class="program-form">
            <div class="form-group">
                <label for="title">Program Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($program['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($program['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="health" <?php echo $program['category'] === 'health' ? 'selected' : ''; ?>>Health</option>
                    <option value="education" <?php echo $program['category'] === 'education' ? 'selected' : ''; ?>>Education</option>
                    <option value="environment" <?php echo $program['category'] === 'environment' ? 'selected' : ''; ?>>Environment</option>
                    <option value="livelihood" <?php echo $program['category'] === 'livelihood' ? 'selected' : ''; ?>>Livelihood</option>
                    <option value="community" <?php echo $program['category'] === 'community' ? 'selected' : ''; ?>>Community Development</option>
                    <option value="youth" <?php echo $program['category'] === 'youth' ? 'selected' : ''; ?>>Youth & Sports</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="schedule">Schedule</label>
                <input type="text" id="schedule" name="schedule" value="<?php echo htmlspecialchars($program['schedule']); ?>" placeholder="e.g., Every Monday, 9AM-5PM">
            </div>
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($program['location']); ?>" placeholder="e.g., Barangay Hall, Health Center">
            </div>
            
            <div class="form-group">
                <label for="contact_person">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($program['contact_person']); ?>" placeholder="Name of contact person">
            </div>
            
            <div class="form-actions">
                <a href="programs.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Update Program</button>
            </div>
        </form>
    </div>
</body>
</html>