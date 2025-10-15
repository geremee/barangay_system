<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$user_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'")->fetch_assoc()['total'];
$document_count = $conn->query("SELECT COUNT(*) as total FROM documents")->fetch_assoc()['total'];
$pending_count = $conn->query("SELECT COUNT(*) as total FROM documents WHERE status = 'pending'")->fetch_assoc()['total'];
$program_count = $conn->query("SELECT COUNT(*) as total FROM programs")->fetch_assoc()['total'];
$complaint_count = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'pending'")->fetch_assoc()['total'];

$recent_requests = $conn->query("SELECT d.id, d.document_type, d.status, d.request_date, u.full_name 
                                FROM documents d 
                                JOIN users u ON d.user_id = u.id 
                                ORDER BY d.request_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$recent_complaints = $conn->query("SELECT c.id, c.subject, c.status, c.created_at, u.full_name 
                                  FROM complaints c 
                                  JOIN users u ON c.user_id = u.id 
                                  ORDER BY c.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | BRGY System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Dashboard</h1>
            <p>Welcome to the Barangay Admin Panel</p>
        </div>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon user-stat">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $user_count; ?></h3>
                    <p>Registered Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon document-stat">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $document_count; ?></h3>
                    <p>Document Requests</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending-stat">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon program-stat">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $program_count; ?></h3>
                    <p>Active Programs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon complaint-stat">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $complaint_count; ?></h3>
                    <p>Pending Complaints</p>
                </div>
            </div>
        </div>
        
        <div class="admin-dashboard-sections">
            <div class="dashboard-section">
                <h2>Recent Document Requests</h2>
                
                <?php if (empty($recent_requests)): ?>
                    <div class="alert alert-info">No recent document requests found.</div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Document Type</th>
                                <th>Requested By</th>
                                <th>Status</th>
                                <th>Request Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td><?php echo ucwords(str_replace('-', ' ', $request['document_type'])); ?></td>
                                    <td><?php echo $request['full_name']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="text-right">
                        <a href="documents.php" class="btn-admin">View All Requests</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section">
                <h2>Recent Complaints/Suggestions</h2>
                
                <?php if (empty($recent_complaints)): ?>
                    <div class="alert alert-info">No recent complaints found.</div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Subject</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                                <tr>
                                    <td>#<?php echo $complaint['id']; ?></td>
                                    <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                    <td><?php echo $complaint['full_name']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="text-right">
                        <a href="complaints.php" class="btn-admin">View All Complaints</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>