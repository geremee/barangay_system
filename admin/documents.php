<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where = [];
$params = [];
$types = '';

if ($status !== 'all') {
    $where[] = "d.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($search)) {
    // Search by tracking number, full name, document type, or ID
    $where[] = "(u.full_name LIKE ? OR d.document_type LIKE ? OR d.tracking_number LIKE ? OR d.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? $search : 0;
    $types .= 'sssi';
}

// Updated query to include tracking_number
$query = "SELECT d.id, d.tracking_number, d.document_type, d.status, d.request_date, d.processed_date, u.full_name 
          FROM documents d 
          JOIN users u ON d.user_id = u.id";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY d.request_date DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = sanitizeInput($_POST['status']);
    $admin_notes = sanitizeInput($_POST['admin_notes']);
    
    $stmt = $conn->prepare("UPDATE documents SET status = ?, processed_date = NOW(), admin_notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $admin_notes, $request_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Request status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update request status.";
    }
    
    $stmt->close();
    redirect("documents.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Requests | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Document Requests</h1>
            <p>Manage and process document requests from residents</p>
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
                    <label for="status">Status:</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search by name, tracking #, or document type..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Tracking Number</th>
                        <th>Document Type</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Request Date</th>
                        <th>Processed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No document requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td>
                                    <?php if (!empty($request['tracking_number'])): ?>
                                        <strong>#<?php echo $request['tracking_number']; ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucwords(str_replace('-', ' ', $request['document_type'])); ?></td>
                                <td><?php echo $request['full_name']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <?php echo $request['processed_date'] ? 
                                        date('M d, Y h:i A', strtotime($request['processed_date'])) : '--'; ?>
                                </td>
                                <td>
                                    <a href="document-details.php?id=<?php echo $request['id']; ?>" class="btn-admin-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button class="btn-admin-edit" onclick="openStatusModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>')">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="statusModal" class="admin-modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeStatusModal()">&times;</span>
                <h2>Update Request Status</h2>
                <form method="post">
                    <input type="hidden" name="request_id" id="modalRequestId">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="modalStatus" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_notes">Admin Notes:</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openStatusModal(requestId, currentStatus) {
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'flex';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>