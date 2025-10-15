<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle regular complaints
    if (!isset($_POST['form_type']) || $_POST['form_type'] !== 'kp_case') {
        $subject = sanitizeInput($_POST['subject']);
        $message = sanitizeInput($_POST['message']);
        
        if (empty($subject)) {
            $error = "Subject is required";
        } elseif (empty($message)) {
            $error = "Message is required";
        } else {
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $_SESSION['user_id'], $subject, $message);
            
            if ($stmt->execute()) {
                $success = "Your complaint/suggestion has been submitted successfully!";
            } else {
                $error = "Failed to submit complaint/suggestion. Please try again.";
            }
            
            $stmt->close();
        }
    } 
    // Handle KP Case submissions
    else {
        $nature_of_dispute = sanitizeInput($_POST['nature_of_dispute']);
        $complainant_name = sanitizeInput($_POST['complainant_name']);
        $complainant_street = sanitizeInput($_POST['complainant_street']);
        $complainant_barangay = sanitizeInput($_POST['complainant_barangay']);
        $respondent_name = sanitizeInput($_POST['respondent_name']);
        $respondent_street = sanitizeInput($_POST['respondent_street']);
        $respondent_barangay = sanitizeInput($_POST['respondent_barangay']);
        $complaint_subject = sanitizeInput($_POST['complaint_subject']);
        
        // Validate required fields
        if (empty($nature_of_dispute) || empty($complainant_name) || empty($complainant_street) || 
            empty($complainant_barangay) || empty($respondent_name) || empty($respondent_street) || 
            empty($respondent_barangay) || empty($complaint_subject)) {
            $error = "All fields marked with * are required";
        } else {
            // Generate KP Number and Rec Number
            $current_year = date('Y');
            
            // Get the next sequence number
            $stmt = $conn->prepare("SELECT COUNT(*) + 1 as next_num FROM kp_cases WHERE YEAR(created_at) = ?");
            $stmt->bind_param("s", $current_year);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $next_num = $row['next_num'];
            $stmt->close();
            
            $kp_number = "KP-" . $current_year . "-" . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            $rec_number = "REC-" . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("INSERT INTO kp_cases 
                (user_id, rec_number, kp_number, nature_of_dispute, complainant_name, 
                 complainant_street, complainant_barangay, respondent_name, 
                 respondent_street, respondent_barangay, complaint_subject) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("issssssssss", 
                $_SESSION['user_id'], $rec_number, $kp_number, $nature_of_dispute,
                $complainant_name, $complainant_street, $complainant_barangay,
                $respondent_name, $respondent_street, $respondent_barangay, 
                $complaint_subject);
            
            if ($stmt->execute()) {
                $success = "Your Katarungang Pambarangay complaint has been submitted successfully! Your KP Number: " . $kp_number;
            } else {
                $error = "Failed to submit KP complaint. Please try again.";
            }
            
            $stmt->close();
        }
    }
}

// Fetch regular complaints
$complaints = [];
$stmt = $conn->prepare("SELECT id, subject, message, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
}
$stmt->close();

// Fetch KP cases for the user
$kp_cases = [];
$stmt = $conn->prepare("SELECT id, rec_number, kp_number, nature_of_dispute, status, created_at FROM kp_cases WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kp_cases[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints/Suggestions | BRGY System</title>
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
            <h1>Complaints and Suggestions</h1>
            <p>Share your concerns or suggestions with the barangay</p>
        </div>
        
        <div class="content-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="complaint-form-section">
                <h2>Submit New Concerns/Suggestion</h2>
                <form action="complaints.php" method="POST">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <br>
                    <button type="submit" class="btn-submit">Submit</button>
                </form>
            </div>
            
            <div class="complaints-list-section">
                <h2>My Previous Complaints/Suggestions</h2>
                
                <?php if (empty($complaints)): ?>
                    <div class="alert alert-info">You haven't submitted any complaints or suggestions yet.</div>
                <?php else: ?>
                    <div class="complaints-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="previous-complaints.php?id=<?php echo $complaint['id']; ?>" class="btn-view">
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


        <div class="content-body">
    <div class="complaint-form-section">
        <div class="kp-form-section">
            <h2>Submit Katarungang Pambarangay Complaint</h2>
            <form action="complaints.php" method="POST">
                <input type="hidden" name="form_type" value="kp_case">

                <div class="form-group">
                    <label for="nature_of_dispute">Nature of Dispute *</label>
                    <input type="text" id="nature_of_dispute" name="nature_of_dispute" required 
                           placeholder="e.g., Property Dispute, Noise Complaint, etc.">
                </div>

                <h3>Complainant Information</h3>
                <div class="form-group">
                    <label for="complainant_name">Your Full Name *</label>
                    <input type="text" id="complainant_name" name="complainant_name" required 
                           value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="complainant_street">Street Address *</label>
                    <input type="text" id="complainant_street" name="complainant_street" required>
                </div>
                <div class="form-group">
                    <label for="complainant_barangay">Barangay *</label>
                    <input type="text" id="complainant_barangay" name="complainant_barangay" required>
                </div>

                <h3>Respondent Information</h3>
                <div class="form-group">
                    <label for="respondent_name">Respondent's Full Name *</label>
                    <input type="text" id="respondent_name" name="respondent_name" required>
                </div>
                <div class="form-group">
                    <label for="respondent_street">Respondent's Street Address *</label>
                    <input type="text" id="respondent_street" name="respondent_street" required>
                </div>
                <div class="form-group">
                    <label for="respondent_barangay">Respondent's Barangay *</label>
                    <input type="text" id="respondent_barangay" name="respondent_barangay" required>
                </div>

                <div class="form-group">
                    <label for="complaint_subject">Subject of Complaint *</label>
                    <textarea id="complaint_subject" name="complaint_subject" rows="4" required 
                              placeholder="Detailed description of the complaint..."></textarea>
                </div>
                
                <br>
                <button type="submit" class="btn-submit">Submit KP Complaint</button>
            </form>
        </div>
    </div>  
</div>
<div class="kp-cases-section">
    <h2>My Katarungang Pambarangay Cases</h2>
    
    <?php if (empty($kp_cases)): ?>
        <div class="alert alert-info">You haven't submitted any Katarungang Pambarangay cases yet.</div>
    <?php else: ?>
        <div class="kp-cases-table">
            <table>
                <thead>
                    <tr>
                        <th>Rec. No</th>
                        <th>KP Number</th>
                        <th>Nature of Dispute</th>
                        <th>Status</th>
                        <th>Date Filed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kp_cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['rec_number']); ?></td>
                            <td><?php echo htmlspecialchars($case['kp_number']); ?></td>
                            <td><?php echo htmlspecialchars($case['nature_of_dispute']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace('_', '-', $case['status']); ?>">
                                    <?php echo ucwords($case['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($case['created_at'])); ?></td>
                            <td>
                                <a href="kp-case-details.php?id=<?php echo $case['id']; ?>" class="btn-view">
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

</body>
</html>



