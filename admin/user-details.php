<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect("users.php");
}

$user_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect("users.php");
}

$user = $result->fetch_assoc();
$stmt->close();

$document_count = $conn->query("SELECT COUNT(*) as total FROM documents WHERE user_id = $user_id")->fetch_assoc()['total'];
$complaint_count = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE user_id = $user_id")->fetch_assoc()['total'];

function calculateAge($dob) {
    if (!$dob) return 'N/A';
    $dob = new DateTime($dob);
    $today = new DateTime();
    return $today->diff($dob)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <div class="header-left">
                <h1>User Details</h1>
                <p>View and manage user information</p>
            </div>
            <div class="header-right">
                <a href="users.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>

        <div class="user-details">
            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $document_count; ?></h3>
                        <p>Document Requests</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $complaint_count; ?></h3>
                        <p>Complaints/Feedback</p>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h2>Basic Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Number:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['address']); ?></span>
                </div>

                <?php if (!empty($user['proof_of_residency'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Proof of Residency (ID):</span>
                        <span class="detail-value">
                            <?php
                            $file_extension = pathinfo($user['proof_of_residency'], PATHINFO_EXTENSION);
                            $file_url = "../" . $user['proof_of_residency'];

                            if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png'])) {
                                echo "<img src='{$file_url}' alt='Proof of Residency' style='max-width: 300px; height: auto; border: 1px solid #ccc;'>";
                            } elseif (strtolower($file_extension) === 'pdf') {
                                echo "<a href='{$file_url}' target='_blank'>View PDF</a>";
                            } else {
                                echo "Unsupported file format.";
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h2>Additional Personal Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['date_of_birth']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Age:</span>
                    <span class="detail-value"><?php echo calculateAge($user['date_of_birth']); ?> years old</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['gender']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Civil Status:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['civil_status']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">PWD:</span>
                    <span class="detail-value"><?php echo $user['pwd'] ? 'Yes' : 'No'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nationality:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['nationality']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Registered Voter:</span>
                    <span class="detail-value"><?php echo $user['registered_voter'] ? 'Yes' : 'No'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Occupation:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['occupation']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Barangay Street Address:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['street_address']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Place of Birth:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['place_of_birth']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Head of Family:</span>
                    <span class="detail-value"><?php echo ($user['is_head_of_family'] ? 'Yes' : 'No'); ?></span>
                </div>
                <?php if (!$user['is_head_of_family'] && !empty($user['head_name'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Name of Head of Family:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['head_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($user['is_head_of_family'] && !empty($user['children_info'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Children:</span>
                        <span class="detail-value">
                            <?php
                            $children = json_decode($user['children_info'], true);
                            if (!empty($children) && is_array($children)) {
                                echo "<ul>";
                                foreach ($children as $child) {
                                    $childName = isset($child['name']) ? htmlspecialchars($child['name']) : 'Unknown Name';
                                    $childAge = isset($child['age']) ? (int)$child['age'] : 'Unknown Age';

                                    echo "<li>Name: {$childName}, Age: {$childAge}</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo "No children information provided.";
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h2>Account Information</h2>
                <div class="detail-row">
                    <span class="detail-label">User Type:</span>
                    <span class="detail-value user-type-badge <?php echo $user['user_type']; ?>">
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date Registered:</span>
                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></span>
                </div>
            </div>

            <div class="detail-actions">
                <button class="btn-admin-edit" onclick="window.location.href='users.php'">
                    <i class="fas fa-edit"></i> Edit User
                </button>
                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
                        <i class="fas fa-trash"></i> Delete User
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
