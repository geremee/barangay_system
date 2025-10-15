<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $like_search = "%$search%";
    $where[] = "(
        username LIKE ? OR
        full_name LIKE ? OR
        email LIKE ? OR
        contact_number LIKE ? OR
        street_address LIKE ? OR
        (is_head_of_family = 1 AND (
            JSON_SEARCH(children_info, 'one', ?) IS NOT NULL
            OR full_name LIKE ?
        ))
    )";
    $params = array_fill(0, 5, $like_search);
    $params[] = $search;
    $params[] = $like_search;
    $types = str_repeat('s', 7);
}

$query = "SELECT id, username, email, full_name, contact_number, created_at, user_type, is_approved FROM users";

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
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle approval
if (isset($_GET['approve'])) {
    $user_id = (int)$_GET['approve'];
    
    $stmt = $conn->prepare("UPDATE users SET is_approved = TRUE, approved_at = NOW(), approved_by = ? WHERE id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User approved successfully!";
    } else {
        $_SESSION['error'] = "Failed to approve user.";
    }
    
    $stmt->close();
    redirect("users.php");
}

// Handle rejection
if (isset($_GET['reject'])) {
    $user_id = (int)$_GET['reject'];
    
    $stmt = $conn->prepare("UPDATE users SET is_approved = FALSE WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User rejected successfully!";
    } else {
        $_SESSION['error'] = "Failed to reject user.";
    }
    
    $stmt->close();
    redirect("users.php");
}

if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
        
        $stmt->close();
    }
    redirect("users.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>

<style>
    .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.approved {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
}

.btn-approve {
    background-color: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    margin: 0 2px;
}

.btn-reject {
    background-color: #dc3545;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    margin: 0 2px;
}
</style>

</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>User Management</h1>
            <p>Manage registered users of the system</p>
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
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['contact_number']; ?></td>
                                <td>
                                    <span class="user-type-badge <?php echo $user['user_type']; ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_approved']): ?>
                                        <span class="status-badge approved">Approved</span>
                                    <?php else: ?>
                                        <span class="status-badge pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn-admin-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (!$user['is_approved']): ?>
                                        <a href="users.php?approve=<?php echo $user['id']; ?>" class="btn-approve" onclick="return confirm('Approve this user?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    <?php else: ?>
                                        <a href="users.php?reject=<?php echo $user['id']; ?>" class="btn-reject" onclick="return confirm('Reject this user?')">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                    <?php endif; ?>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
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
