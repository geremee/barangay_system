<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = [];
$params = [];
$types = '';

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$query = "SELECT * FROM programs";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$programs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_GET['delete'])) {
    $program_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->bind_param("i", $program_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Program deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete program.";
    }
    
    $stmt->close();
    redirect("programs.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Programs | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>Barangay Programs</h1>
                <p>Manage and update barangay programs and services</p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="admin-filters">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" onchange="this.form.submit()">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="health" <?php echo $category === 'health' ? 'selected' : ''; ?>>Health</option>
                        <option value="education" <?php echo $category === 'education' ? 'selected' : ''; ?>>Education</option>
                        <option value="environment" <?php echo $category === 'environment' ? 'selected' : ''; ?>>Environment</option>
                        <option value="livelihood" <?php echo $category === 'livelihood' ? 'selected' : ''; ?>>Livelihood</option>
                        <option value="community" <?php echo $category === 'community' ? 'selected' : ''; ?>>Community</option>
                        <option value="youth" <?php echo $category === 'youth' ? 'selected' : ''; ?>>Youth & Sports</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search programs..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        <div class="detail-actions">
                <a href="add-program.php" class="btn-back">
                    <i class="fas fa-plus"></i> Add New Program
                </a>
        </div> <br>

        <div class="programs-grid">
            <?php if (empty($programs)): ?>
                <div class="alert alert-info">No programs found. <a href="add-program.php">Add a new program</a>.</div>
            <?php else: ?>
                <?php foreach ($programs as $program): ?>
                    <div class="program-card">
                        <div class="program-header">
                            <h3><?php echo htmlspecialchars($program['title']); ?></h3>
                            <span class="program-category category-<?php echo $program['category']; ?>">
                                <?php echo ucfirst($program['category']); ?>
                            </span>
                        </div>
                        
                        <div class="program-body">
                            <p><?php echo nl2br(htmlspecialchars($program['description'])); ?></p>
                            
                            <?php if (!empty($program['schedule'])): ?>
                                <div class="program-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo htmlspecialchars($program['schedule']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($program['location'])): ?>
                                <div class="program-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($program['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($program['contact_person'])): ?>
                                <div class="program-info">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($program['contact_person']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="program-footer">
                            <small>Last updated: <?php echo date('M d, Y', strtotime($program['updated_at'])); ?></small>
                            <div class="program-actions">
                                <a href="edit-program.php?id=<?php echo $program['id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="programs.php?delete=<?php echo $program['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this program?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>