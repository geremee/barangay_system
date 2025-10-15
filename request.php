<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$show_success_popup = false;
$success_message = '';

    if (isset($_SESSION['show_success_popup']) && $_SESSION['show_success_popup']) {
        $show_success_popup = true;
        $success_message = $_SESSION['success_message'] ?? '';
        unset($_SESSION['show_success_popup']);
        unset($_SESSION['success_message']);
    }


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    function generateRandomTrackingNumber($conn) {
        $maxAttempts = 10; 
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $random_id = mt_rand(10000, 99999); // nagggenerate ng 5 no
            
            // check ng trckng no if existed
            $check_stmt = $conn->prepare("SELECT id FROM documents WHERE tracking_number = ?");
            $check_stmt->bind_param("s", $random_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows === 0) {
                $check_stmt->close();
                return $random_id;
            }
            
            $check_stmt->close();
            $attempts++;
        }
        
        // Fallback
        return mt_rand(10000, 99999) . substr(time(), -3);
    }
    
    $document_type = sanitizeInput($_POST['document_type']);
    $purpose = isset($_POST['purpose']) ? sanitizeInput($_POST['purpose']) : '';
    $business_name = isset($_POST['business_name']) ? sanitizeInput($_POST['business_name']) : '';
    
    // random tracking no
    $tracking_number = generateRandomTrackingNumber($conn);
    
    $stmt = $conn->prepare("INSERT INTO documents (user_id, document_type, purpose, business_name, tracking_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_SESSION['user_id'], $document_type, $purpose, $business_name, $tracking_number);
    
    if ($stmt->execute()) {
        $document_id = $stmt->insert_id; // auto incre ID
        
        $upload_dir = 'uploads/documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_errors = [];
        
        // mga required na files based sa doc
        $file_types = [];
        
        if ($document_type === 'barangay-indigency') {
            $file_types = ['income-proof', 'family-composition', 'id-file'];
        } elseif ($document_type === 'business-permit') {
            $file_types = ['business-permit-file', 'id-file'];
        } elseif ($document_type === 'barangay-id') {
            $file_types = ['id-file', 'proof-of-residency'];
        } elseif ($document_type === 'barangay-clearance') {
            $file_types = ['id-file', 'proof-of-residency'];
        } elseif ($document_type === 'business-clearance') {
            $file_types = ['business-registration', 'business-permit', 'proof-of-address', 'id-file'];
        } elseif ($document_type === 'certificate-indigency') {
            $file_types = ['id-file', 'proof-of-residency', 'income-proof'];
        } elseif ($document_type === 'certificate-last-residency') {
            $file_types = ['id-file', 'proof-previous-residence'];
        } elseif ($document_type === 'certificate-non-residency') {
            $file_types = ['id-file', 'declaration-non-residency'];
        } elseif ($document_type === 'proof-residency') {
            $file_types = ['id-file', 'address-document'];
        } elseif ($document_type === 'nbi-clearance') {
            $file_types = ['id-file', 'nbi-application', 'birth-certificate'];
        } elseif ($document_type === 'postal-id') {
            $file_types = ['id-file', 'proof-of-residency', 'birth-certificate'];
        } elseif ($document_type === 'bail-bond') {
            $file_types = ['id-file', 'court-order', 'authorization-doc', 'case-documents'];
        } elseif ($document_type === 'marriage-license') {
            $file_types = ['birth-certificate-both', 'cenomar', 'id-both', 'barangay-clearance-both'];
        } elseif ($document_type === 'drivers-license') {
            $file_types = ['id-file', 'medical-certificate', 'proof-of-residency'];
        } elseif ($document_type === 'legal-purposes') {
            $file_types = ['id-file', 'affidavit', 'supporting-docs'];
        } elseif ($document_type === 'sss-requirement') {
            $file_types = ['id-file', 'sss-number', 'income-proof'];
        } elseif ($document_type === 'government-ids') {
            $file_types = ['id-file', 'proof-of-residency', 'income-proof', 'application-forms'];
        } elseif ($document_type === 'owwa-requirement') {
            $file_types = ['id-file', 'employment-contract', 'owwa-record', 'application-form'];
        } elseif ($document_type === 'firearms-license') {
            $file_types = ['nbi-clearance', 'police-clearance', 'medical-certificate', 'neuro-test', 'birth-certificate', 'id-file', 'firearm-registration'];
        } elseif ($document_type === 'bank-loan') {
            $file_types = ['id-file', 'income-proof', 'barangay-clearance', 'proof-of-residency', 'financial-docs'];
        } elseif ($document_type === 'housing-loan') {
            $file_types = ['id-file', 'income-proof', 'proof-of-residency', 'property-docs', 'loan-application'];
        } elseif ($document_type === 'solo-parent') {
            $file_types = ['id-file', 'birth-certificates', 'proof-of-residency', 'income-proof', 'dswd-form'];
        } else {
            $file_types = ['id-file'];
        }
        
        // maghahandle ng uoloads
        foreach ($file_types as $file_type) {
            if (isset($_FILES[$file_type]) && $_FILES[$file_type]['error'] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES[$file_type]['name']);
                $file_path = $upload_dir . uniqid() . '_' . $file_name;
                
                if (move_uploaded_file($_FILES[$file_type]['tmp_name'], $file_path)) {
                    $stmt_file = $conn->prepare("INSERT INTO document_files (document_id, file_type, file_path) VALUES (?, ?, ?)");
                    $stmt_file->bind_param("iss", $document_id, $file_type, $file_path);
                    $stmt_file->execute();
                    $stmt_file->close();
                } else {
                    $upload_errors[] = "Failed to upload {$file_type} file.";
                }
            } elseif (isset($_FILES[$file_type])) {
                $upload_errors[] = "Error uploading {$file_type} file: " . $_FILES[$file_type]['error'];
            }
        }
       
        if (empty($upload_errors)) {
            $_SESSION['success_message'] = "Your tracking number is #{$tracking_number}.";
            $_SESSION['show_success_popup'] = true;
            // prevent form resubmission
            redirect('request.php');
        } else {
            $error = "Document request submitted but with some file upload errors: " . implode(" ", $upload_errors);
        }
    } else {
        $error = "Failed to submit document request. Please try again.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Documents | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <style>
        .pop-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-box {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .popup-box h2 {
            color: #2c5aa0;
            margin-bottom: 15px;
        }

        .popup-box p {
            margin-bottom: 20px;
            color: #666;
        }

        .popup-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .popup-buttons button {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        #cancelBtn {
            background-color: #6c757d;
            color: white;
        }

        #cancelBtn:hover {
            background-color: #5a6268;
        }

        #confirmBtn {
            background-color: #28a745;
            color: white;
        }

        #confirmBtn:hover {
            background-color: #218838;
        }

        .success-popup {
            text-align: center;
        }

        .popup-icon {
            font-size: 50px;
            color: #28a745;
            margin-bottom: 15px;
        }

        .popup-icon .fas {
            animation: bounce 0.6s;
        }

        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            80% { transform: translateY(-5px); }
        }

        .btn-success {
            background-color: #28a745;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-success:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="content-header">
            <h1>Document Request</h1>
            <p>Please complete the required details below</p>
            <h2 style="color: red;">Disclaimer, The process of requesting documents takes up to 3-5 business days</h2>
        </div>
        
        <div class="content-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Added ID to the form -->
            <form id="requestForm" action="request.php" method="POST" enctype="multipart/form-data" class="request-form">
                <div class="form-group">
                    <label for="document_type">Document Type</label>
                    <select id="document_type" name="document_type" required>
                        <option value="">-- Select Document Type --</option>
                        <option value="barangay-indigency">Barangay Indigency Certificate</option>
                        <option value="barangay-certificate">Barangay Certificate</option>
                        <option value="business-permit">Business Permit</option>
                        <option value="barangay-id">Barangay ID</option>
                        <option value="barangay-clearance">Barangay Clearance</option>
                        <option value="business-clearance">Barangay Business Clearance</option>
                        <option value="certificate-indigency">Certificate of Indigency</option>
                        <option value="certificate-last-residency">Certificate of Last Residency</option>
                        <option value="certificate-non-residency">Certificate of Non-Residency</option>
                        <option value="proof-residency">Proof of Residency</option>
                        <option value="marriage-license">Marriage License Requirement</option>
                        <option value="firearms-license">Firearms License (LTOPF) / Renewal</option>
                    </select>
                </div>
                
                <div id="additional-fields">
                </div>
                    
                
                <button type="button" class="btn-submit" onclick="confirmDocs()">Submit Request</button>
            </form>
        </div>
    </div>

    
    <div id="confirmationPop" class="pop-overlay" style="display: none;">
        <div class="popup-box">
            <h2>Confirm Your Request</h2>
            <p>Please review your information before submitting. The processing of documents takes 3-5 business days.</p>
            <div class="popup-buttons">
                <button id="cancelBtn">Recheck Details</button>
                <button id="confirmBtn">Confirm & Submit</button>
            </div>
        </div>
    </div>

    <!-- Success Popup -->
    <div id="successPop" class="pop-overlay" style="display: none;">
        <div class="popup-box success-popup">
            <div class="popup-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Request Submitted Successfully!</h1>
            <h3>Please take a screenshot and present it to Barangay Hall</h3>
            <p id="successMessage"></p>
            <div class="popup-buttons">
                <button id="successOkBtn" class="btn-success">Done</button>
            </div>
        </div>
    </div>

    <script>
        
        function confirmDocs(event) {
            
            const documentType = document.getElementById('document_type').value;
            if (!documentType) {
                alert('Please select a document type.');
                return;
            }
            
            
            document.getElementById('confirmationPop').style.display = 'flex';
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            
            document.getElementById('confirmBtn').addEventListener('click', function() {
                document.getElementById('requestForm').submit();
            });

            
            document.getElementById('cancelBtn').addEventListener('click', function() {
                document.getElementById('confirmationPop').style.display = 'none';
            });

            // MAGCCLOSE NG POPUP
            document.getElementById('confirmationPop').addEventListener('click', function(e) {
                if (e.target === this) {
                    document.getElementById('confirmationPop').style.display = 'none';
                }
            });

            // SUCCESS POPUP EVENT LISTENERS
            document.getElementById('successOkBtn').addEventListener('click', function() {
                document.getElementById('successPop').style.display = 'none';
            });
            
            document.getElementById('successPop').addEventListener('click', function(e) {
                if (e.target === this) {
                    document.getElementById('successPop').style.display = 'none';
                }
            });
            
            
            <?php if ($show_success_popup): ?>
            setTimeout(function() {
                document.getElementById('successMessage').textContent = "<?php echo addslashes($success_message); ?>";
                document.getElementById('successPop').style.display = 'flex';
            }, 500);
            <?php endif; ?>
        });

    
        document.getElementById('document_type').addEventListener('change', function() {
            const documentType = this.value;
            const additionalFields = document.getElementById('additional-fields');
            
            
            additionalFields.innerHTML = '';
            
            const commonFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID (PDF/Image)</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const barangayIndigencyFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="income-proof">Proof of Income or Declaration of No Income (PDF/Image)</label>
                    <input type="file" id="income-proof" name="income-proof" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="family-composition">Family Composition (PDF/Image)</label>
                    <input type="file" id="family-composition" name="family-composition" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID (PDF/Image)</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const businessFields = `
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Business Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="business-permit-file">Business Permit Document (PDF/Image)</label>
                    <input type="file" id="business-permit-file" name="business-permit-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID (PDF/Image)</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const barangayIdFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID (Government-issued or School ID)</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="proof-of-residency">Proof of Residency (Utility bill, lease agreement)</label>
                    <input type="file" id="proof-of-residency" name="proof-of-residency" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const barangayClearanceFields = `
                <div class="form-group">
                    <label for="purpose">Purpose of Clearance</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="proof-of-residency">Proof of Residency (Utility bill, lease contract, etc.)</label>
                    <input type="file" id="proof-of-residency" name="proof-of-residency" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const businessClearanceFields = `
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Business Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="business-registration">DTI or SEC Registration</label>
                    <input type="file" id="business-registration" name="business-registration" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="business-permit">Mayor's Permit or Business Permit</label>
                    <input type="file" id="business-permit" name="business-permit" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-group">
                    <label for="proof-of-address">Proof of Business Address</label>
                    <input type="file" id="proof-of-address" name="proof-of-address" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            const certificateIndigencyFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Valid ID</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="proof-of-residency">Proof of Residency</label>
                    <input type="file" id="proof-of-residency" name="proof-of-residency" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="income-proof">Proof or Declaration of Low/No Income</label>
                    <input type="file" id="income-proof" name="income-proof" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            `;

            // const nbiClearanceFields = `
            //     <div class="form-group">
            //         <label for="purpose">Purpose</label>
            //         <input type="text" id="purpose" name="purpose" required>
            //     </div>
            //     <div class="form-group">
            //         <label for="id-file">Valid ID</label>
            //         <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
            //     </div>
            //     <div class="form-group">
            //         <label for="nbi-application">Completed NBI Online Application Form</label>
            //         <input type="file" id="nbi-application" name="nbi-application" accept=".pdf,.jpg,.jpeg,.png" required>
            //     </div>
            //     <div class="form-group">
            //         <label for="birth-certificate">Birth Certificate (if needed)</label>
            //         <input type="file" id="birth-certificate" name="birth-certificate" accept=".pdf,.jpg,.jpeg,.png">
            //     </div>
            // `;

            const marriageLicenseFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="birth-certificate-both">Birth Certificates of Both Applicants</label>
                    <input type="file" id="birth-certificate-both" name="birth-certificate-both" accept=".pdf,.jpg,.jpeg,.png" required multiple>
                </div>
                <div class="form-group">
                    <label for="cenomar">Certificate of No Marriage (CENOMAR)</label>
                    <input type="file" id="cenomar" name="cenomar" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="id-both">Valid IDs of Both Parties</label>
                    <input type="file" id="id-both" name="id-both" accept=".pdf,.jpg,.jpeg,.png" required multiple>
                </div>
                <div class="form-group">
                    <label for="barangay-clearance-both">Barangay Clearance of Both Applicants</label>
                    <input type="file" id="barangay-clearance-both" name="barangay-clearance-both" accept=".pdf,.jpg,.jpeg,.png" required multiple>
                </div>
            `;

            const firearmsLicenseFields = `
                <div class="form-group">
                    <label for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" required>
                </div>
                <div class="form-group">
                    <label for="nbi-clearance">NBI Clearance</label>
                    <input type="file" id="nbi-clearance" name="nbi-clearance" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="police-clearance">Police Clearance</label>
                    <input type="file" id="police-clearance" name="police-clearance" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="medical-certificate">Medical Certificate</label>
                    <input type="file" id="medical-certificate" name="medical-certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="neuro-test">Neuro-Psychiatric Test Result</label>
                    <input type="file" id="neuro-test" name="neuro-test" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="birth-certificate">Birth Certificate</label>
                    <input type="file" id="birth-certificate" name="birth-certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="id-file">Government-issued ID</label>
                    <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="firearm-registration">Firearm Registration (for renewal)</label>
                    <input type="file" id="firearm-registration" name="firearm-registration" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            `;

            switch(documentType) {
                case 'barangay-indigency':
                    additionalFields.innerHTML = barangayIndigencyFields;
                    break;
                case 'business-permit':
                    additionalFields.innerHTML = businessFields;
                    break;
                case 'barangay-id':
                    additionalFields.innerHTML = barangayIdFields;
                    break;
                case 'barangay-clearance':
                    additionalFields.innerHTML = barangayClearanceFields;
                    break;
                case 'business-clearance':
                    additionalFields.innerHTML = businessClearanceFields;
                    break;
                case 'certificate-indigency':
                    additionalFields.innerHTML = certificateIndigencyFields;
                    break;
                case 'nbi-clearance':
                    additionalFields.innerHTML = nbiClearanceFields;
                    break;
                case 'marriage-license':
                    additionalFields.innerHTML = marriageLicenseFields;
                    break;
                case 'firearms-license':
                    additionalFields.innerHTML = firearmsLicenseFields;
                    break;
                case 'barangay-certificate':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label for="purpose">Purpose of the Certificate</label>
                            <input type="text" id="purpose" name="purpose" required>
                        </div>
                        <div class="form-group">
                            <label for="id-file">Valid ID (PDF/Image)</label>
                            <input type="file" id="id-file" name="id-file" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    `;
                    break;
                default:
                    additionalFields.innerHTML = commonFields;
            }
        });
    </script>
</body>
</html>

<!-- BARANGAY CLEARANCE PURPOSES/USES
Certificate of Good Character
employment application requirement
business partnership validation 
financial institution
complementary certificates for specific applications  -->
