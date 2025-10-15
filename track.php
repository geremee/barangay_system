<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$requests = [];
$stmt = $conn->prepare("SELECT id, tracking_number, document_type, status, request_date, processed_date  FROM documents WHERE user_id = ? ORDER BY request_date DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Requests | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<style>
.imgg {
    background-image: url('assets/images/group1.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    opacity: 0.5; 
}


</style>
<body>
    <div class="imgg"></div>
    <div class="main-content">
        <div class="content-header">
            <h1>My Document Requests</h1>
            <p>Track the status of your submitted requests</p>
        </div>
        
        <div class="content-body">
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">
                    You haven't submitted any document requests yet. <a href="request.php">Request a document now</a>.
                </div>
            <?php else: ?>
                <div class="requests-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Tracking Number</th>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Processed Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td>
                                        <b><?php 
                                        echo !empty($request['tracking_number']) 
                                            ? '#' . htmlspecialchars($request['tracking_number']) 
                                            : '<span class="text-muted">Not assigned</span>';
                                        ?></b>
                                    </td>

                                    <td><?php echo ucwords(str_replace('-', ' ', $request['document_type'])); ?></td>
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
                                        <a href="request-details.php?id=<?php echo $request['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>