<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$case_type = isset($_GET['case_type']) ? sanitizeInput($_GET['case_type']) : 'regular';

// Handle KP Case updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_kp_case'])) {
        $case_id = (int)$_POST['case_id'];
        $first_med = !empty($_POST['first_mediation_date']) ? $_POST['first_mediation_date'] : null;
        $second_med = !empty($_POST['second_mediation_date']) ? $_POST['second_mediation_date'] : null;
        $third_med = !empty($_POST['third_mediation_date']) ? $_POST['third_mediation_date'] : null;
        $first_con = !empty($_POST['first_conference_date']) ? $_POST['first_conference_date'] : null;
        $second_con = !empty($_POST['second_conference_date']) ? $_POST['second_conference_date'] : null;
        $third_con = !empty($_POST['third_conference_date']) ? $_POST['third_conference_date'] : null;
        $status = sanitizeInput($_POST['status']);
        $settled_thru = !empty($_POST['settled_thru']) ? sanitizeInput($_POST['settled_thru']) : null;
        $cfa_issued = isset($_POST['cfa_issued']) ? 1 : 0;
        $referred_to = !empty($_POST['referred_to_agency']) ? sanitizeInput($_POST['referred_to_agency']) : null;
        $date_settled = !empty($_POST['date_settled_closed']) ? $_POST['date_settled_closed'] : null;
        $case_notes = sanitizeInput($_POST['case_notes']);
        $documents_link = sanitizeInput($_POST['case_documents_link']);

        $stmt = $conn->prepare("UPDATE kp_cases SET 
            first_mediation_date = ?, second_mediation_date = ?, third_mediation_date = ?,
            first_conference_date = ?, second_conference_date = ?, third_conference_date = ?,
            status = ?, settled_thru = ?, cfa_issued = ?, referred_to_agency = ?,
            date_settled_closed = ?, case_notes = ?, case_documents_link = ?, updated_at = NOW()
            WHERE id = ?");
        
        $stmt->bind_param("ssssssssissssi", 
            $first_med, $second_med, $third_med, $first_con, $second_con, $third_con,
            $status, $settled_thru, $cfa_issued, $referred_to, $date_settled, 
            $case_notes, $documents_link, $case_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "KP Case updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update KP Case.";
        }
        
        $stmt->close();
        redirect("complaints.php?case_type=kp");
    }
}


if ($case_type === 'kp') {
    
    $where = [];
    $params = [];
    $types = '';

    if ($status !== 'all') {
        $where[] = "k.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (!empty($search)) {
        $where[] = "(k.complainant_name LIKE ? OR k.respondent_name LIKE ? OR k.kp_number LIKE ? OR k.rec_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ssss';
    }

    $query = "SELECT k.*, u.full_name as submitted_by 
              FROM kp_cases k 
              JOIN users u ON k.user_id = u.id";

    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY k.created_at DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $cases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // complaints
    $where = [];
    $params = [];
    $types = '';

    if ($status !== 'all') {
        $where[] = "c.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (!empty($search)) {
        $where[] = "(u.full_name LIKE ? OR c.subject LIKE ? OR c.id = ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = is_numeric($search) ? $search : 0;
        $types .= 'ssi';
    }

    $query = "SELECT c.id, c.subject, c.status, c.created_at, u.full_name 
              FROM complaints c 
              JOIN users u ON c.user_id = u.id";

    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $cases = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints | BRGY Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include '../includes/admin-header.php'; ?>
    <?php include '../includes/admin-sidebar.php'; ?>
    
    <style>
        
.case-type-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.tab-btn {
    padding: 10px 20px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-bottom: none;
    margin-right: 5px;
    cursor: pointer;
    border-radius: 4px 4px 0 0;
}

.tab-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}


.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-row .form-group {
    flex: 1;
}

.checkbox-group {
    display: flex;
    align-items: center;
    margin-top: 25px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
}
    </style>
</head>
<body>
    <div class="admin-main-content">
        <div class="admin-content-header">
            <h1>Complaints and KP Cases Management</h1>
            <p>Manage regular complaints and Katarungang Pambarangay cases</p>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="case-type-tabs">
            <button class="tab-btn <?php echo $case_type === 'regular' ? 'active' : ''; ?>" 
                    onclick="window.location.href='complaints.php?case_type=regular'">
                Regular Complaints
            </button>
            <button class="tab-btn <?php echo $case_type === 'kp' ? 'active' : ''; ?>" 
                    onclick="window.location.href='complaints.php?case_type=kp'">
                Katarungang Pambarangay Cases
            </button>
        </div>
        
        <div class="admin-filters">
            <form method="get" class="filter-form">
                <input type="hidden" name="case_type" value="<?php echo $case_type; ?>">
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <?php if ($case_type === 'kp'): ?>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="settled" <?php echo $status === 'settled' ? 'selected' : ''; ?>>Settled</option>
                            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="referred" <?php echo $status === 'referred' ? 'selected' : ''; ?>>Referred</option>
                        <?php else: ?>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="<?php echo $case_type === 'kp' ? 'Search by name or KP number...' : 'Search by name or subject...'; ?>" 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <?php if ($case_type === 'kp'): ?>
                            <th>Rec. No</th>
                            <th>KP Number</th>
                            <th>Nature of Dispute</th>
                            <th>Complainant(s)</th>
                            <th>Complainant Street</th>
                            <th>Complainant Barangay</th>
                            <th>Respondent(s)</th>
                            <th>Respondent Street</th>
                            <th>Respondent Barangay</th>
                            <th>Date Filed</th>
                            <th>Status</th>
                            <th>Actions</th>
                        <?php else: ?>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Date Filed</th>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr>
                            <td colspan="<?php echo $case_type === 'kp' ? '12' : '6'; ?>" class="text-center">
                                No <?php echo $case_type === 'kp' ? 'KP cases' : 'complaints'; ?> found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <?php if ($case_type === 'kp'): ?>
                                    <td><?php echo htmlspecialchars($case['rec_number']); ?></td>
                                    <td><?php echo htmlspecialchars($case['kp_number']); ?></td>
                                    <td><?php echo htmlspecialchars($case['nature_of_dispute']); ?></td>
                                    <td><?php echo htmlspecialchars($case['complainant_name']); ?></td>
                                    <td><?php echo htmlspecialchars($case['complainant_street']); ?></td>
                                    <td><?php echo htmlspecialchars($case['complainant_barangay']); ?></td>
                                    <td><?php echo htmlspecialchars($case['respondent_name']); ?></td>
                                    <td><?php echo htmlspecialchars($case['respondent_street']); ?></td>
                                    <td><?php echo htmlspecialchars($case['respondent_barangay']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($case['date_filed'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $case['status']); ?>">
                                            <?php echo ucwords($case['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-admin-edit" onclick="openKPModal(<?php echo $case['id']; ?>)">
                                            <i class="fas fa-edit"></i> Manage
                                        </button>
                                    </td>
                                <?php else: ?>
                                    <td>#<?php echo $case['id']; ?></td>
                                    <td><?php echo htmlspecialchars($case['subject']); ?></td>
                                    <td><?php echo $case['full_name']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $case['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($case['created_at'])); ?></td>
                                    <td>
                                        <a href="complaints-details.php?id=<?php echo $case['id']; ?>" class="btn-admin-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- KP Case Management Modal -->
        <div id="kpModal" class="admin-modal">
            <div class="modal-content" style="max-width: 800px;">
                <span class="close-modal" onclick="closeKPModal()">&times;</span>
                <h2>Manage Katarungang Pambarangay Case</h2>
                <form method="post">
                    <input type="hidden" name="case_id" id="modalCaseId">
                    <input type="hidden" name="update_kp_case" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_mediation_date">1st Mediation Date</label>
                            <input type="date" id="first_mediation_date" name="first_mediation_date">
                        </div>
                        <div class="form-group">
                            <label for="second_mediation_date">2nd Mediation Date</label>
                            <input type="date" id="second_mediation_date" name="second_mediation_date">
                        </div>
                        <div class="form-group">
                            <label for="third_mediation_date">3rd Mediation Date</label>
                            <input type="date" id="third_mediation_date" name="third_mediation_date">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_conference_date">1st Conference Date</label>
                            <input type="date" id="first_conference_date" name="first_conference_date">
                        </div>
                        <div class="form-group">
                            <label for="second_conference_date">2nd Conference Date</label>
                            <input type="date" id="second_conference_date" name="second_conference_date">
                        </div>
                        <div class="form-group">
                            <label for="third_conference_date">3rd Conference Date</label>
                            <input type="date" id="third_conference_date" name="third_conference_date">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="modalStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="settled">Settled</option>
                                <option value="closed">Closed</option>
                                <option value="referred">Referred</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settled_thru">Settled Through</label>
                            <select id="settled_thru" name="settled_thru">
                                <option value="">Select method</option>
                                <option value="mediation">Mediation</option>
                                <option value="conciliation">Conciliation</option>
                                <option value="arbitration">Arbitration</option>
                                <option value="withdrawn">Withdrawn</option>
                                <option value="dismissed">Dismissed</option>
                            </select>
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="cfa_issued" name="cfa_issued" value="1"> CFA Issued
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="referred_to_agency">Referred to Agency</label>
                            <input type="text" id="referred_to_agency" name="referred_to_agency" 
                                   placeholder="e.g., LGU, PNP, Court">
                        </div>
                        <div class="form-group">
                            <label for="date_settled_closed">Date Settled/Closed</label>
                            <input type="date" id="date_settled_closed" name="date_settled_closed">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="case_documents_link">Link to Case Documents</label>
                        <input type="url" id="case_documents_link" name="case_documents_link" 
                               placeholder="https://drive.google.com/...">
                    </div>
                    
                    <div class="form-group">
                        <label for="case_notes">Case Notes</label>
                        <textarea id="case_notes" name="case_notes" rows="4" 
                                  placeholder="Additional notes about the case..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeKPModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openKPModal(caseId) {
            document.getElementById('modalCaseId').value = caseId;
           
            document.getElementById('kpModal').style.display = 'flex';
        }
        
        function closeKPModal() {
            document.getElementById('kpModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('kpModal');
            if (event.target === modal) {
                closeKPModal();
            }
        }
    </script>
</body>
</html>