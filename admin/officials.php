<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$officials = $conn->query("SELECT * FROM officials ORDER BY display_order, name")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['delete'])) {
    $official_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM officials WHERE id = ?");
    $stmt->bind_param("i", $official_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Official deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete official.";
    }
    
    $stmt->close();
    redirect("officials.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Officials | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>Barangay Officials</h1>
                <p>Manage the list of barangay officials</p>
            </div>
            <div class="header-right">
                <a href="add-official.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add Official
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="officials-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Display Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($officials)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No officials found. <a href="add-official.php">Add an official</a>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($officials as $official): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($official['image_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($official['image_path']); ?>" alt="<?php echo htmlspecialchars($official['name']); ?>" class="official-thumbnail">
                                    <?php else: ?>
                                        <div class="official-placeholder"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($official['name']); ?></td>
                                <td><?php echo htmlspecialchars($official['position']); ?></td>
                                <td><?php echo $official['display_order']; ?></td>
                                <td>
                                    <a href="edit-official.php?id=<?php echo $official['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="officials.php?delete=<?php echo $official['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this official?')">
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