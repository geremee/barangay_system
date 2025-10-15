<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('track-requests.php');
}

$request_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT d.*, u.full_name, u.email FROM documents d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.user_id = ?");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('track-requests.php');
}

$request = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Details | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
</head>
<body>
    <div class="main-content">
        <div class="content-header">
            <h1>Request Details</h1>
            <p>Detailed view of your document request</p>
        </div>

        <div class="content-body">
            <div class="details-card">
                <h2>Document: <?php echo ucwords(str_replace('-', ' ', $request['document_type'])); ?></h2>
                <br>
                <p><strong>Status:</strong>
                    <span class="status-badge status-<?php echo $request['status']; ?>">
                        <?php echo ucfirst($request['status']); ?>
                    </span>
                </p>

                <p><strong>Purpose:</strong> <?php echo !empty($request['purpose']) ? htmlspecialchars($request['purpose']) : 'N/A'; ?></p>

                <?php if (!empty($request['business_name'])): ?>
                    <p><strong>Business Name:</strong> <?php echo htmlspecialchars($request['business_name']); ?></p>
                <?php endif; ?>

                <p><strong>Request Date:</strong> <?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></p>

                <p><strong>Processed Date:</strong> 
                    <?php echo $request['processed_date'] ? date('M d, Y h:i A', strtotime($request['processed_date'])) : '--'; ?>
                </p>

                <?php if (!empty($request['admin_notes'])): ?>
                    <p><strong>Admin Notes:</strong> <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                <?php endif; ?>

                <br><a href="track.php" class="btn-view"><i class="fas fa-arrow-left"></i> Back to Requests</a>
            </div>
        </div>
    </div>
</body>
</html>
