<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $street_address = sanitizeInput($_POST['street_address']); // NEW
    $place_of_birth = sanitizeInput($_POST['place_of_birth']); // NEW
    $address = sanitizeInput($_POST['address']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $civil_status = sanitizeInput($_POST['civil_status']);
    $pwd = isset($_POST['pwd']) ? 1 : 0;
    $nationality = sanitizeInput($_POST['nationality']);
    $registered_voter = isset($_POST['registered_voter']) ? 1 : 0;
    $occupation = sanitizeInput($_POST['occupation']);
    $terms_agreement = isset($_POST['terms_agreement']) ? 1 : 0;

    // head of family field
    $head_of_family = isset($_POST['head_of_family']) ? $_POST['head_of_family'] : '';
    $head_name = ($head_of_family === 'no') ? sanitizeInput($_POST['head_name']) : '';
    $num_children = ($head_of_family === 'yes') ? intval($_POST['num_children']) : 0;
    $children = ($head_of_family === 'yes' && isset($_POST['children'])) ? $_POST['children'] : [];
    
    // JSON encode children array, ensure array_values to avoid weird keys
    $children_serialized = ($head_of_family === 'yes') ? json_encode(array_values($children)) : null;

    // Basic validations
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
        if ($emailDomain !== 'gmail.com') {
            $errors[] = "Only Gmail addresses are allowed (e.g., yourname@gmail.com)";
        }
    }

    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($street_address)) $errors[] = "Barangay street address is required";
    if (empty($place_of_birth)) $errors[] = "Place of birth is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($contact_number)) $errors[] = "Contact number is required";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($civil_status)) $errors[] = "Civil status is required";
    if (empty($nationality)) $errors[] = "Nationality is required";
    if (empty($occupation)) $errors[] = "Occupation is required";
    if (!$terms_agreement) $errors[] = "You must agree to the terms and conditions";

    // Head of family validation
    if ($head_of_family === 'yes') {
        if ($num_children > 0 && empty($children)) {
            $errors[] = "Please provide information for your children.";
        }
        // Validate children data fields
        foreach ($children as $index => $child) {
            if (empty($child['name']) || empty($child['age'])) {
                $errors[] = "Child #" . ($index + 1) . " must have a name and age.";
            }
        }
    } elseif ($head_of_family === 'no') {
        if (empty($head_name)) $errors[] = "Please provide the name of the head of the family.";
    } else {
        $errors[] = "Please select if you are the head of the family.";
    }

    // File upload handling
    $proof_path = '';
    if (isset($_FILES['proof_of_residency']) && $_FILES['proof_of_residency']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $proof_file = $_FILES['proof_of_residency'];
        $proof_filename = basename($proof_file['name']);
        $proof_tmp = $proof_file['tmp_name'];
        $proof_path = $upload_dir . uniqid() . "_" . $proof_filename;

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = mime_content_type($proof_tmp);

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, and PDF files are allowed for proof of residency.";
        } elseif (!move_uploaded_file($proof_tmp, $proof_path)) {
            $errors[] = "Failed to upload proof of residency.";
        }
    } else {
        $errors[] = "Proof of residency file is required.";
    }

    // Check if username or email exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }

        $stmt->close();
    }

    // INSERT INTO database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $is_head = ($head_of_family === 'yes') ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO users (
            username, password, email, full_name, street_address, place_of_birth,
            address, contact_number, date_of_birth, gender, civil_status,
            pwd, nationality, registered_voter, occupation,
            proof_of_residency, is_head_of_family, head_name, children_info,
            user_type, is_approved
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', FALSE)");

        $stmt->bind_param("sssssssssssisisssss",
            $username, $hashed_password, $email, $full_name, $street_address, $place_of_birth,
            $address, $contact_number, $date_of_birth, $gender, $civil_status,
            $pwd, $nationality, $registered_voter, $occupation,
            $proof_path, $is_head, $head_name, $children_serialized
        );

        if ($stmt->execute()) {
            $success = "Registration successful! Your account will be available in 3-5 business days after admin approval.";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="register-page">
    <div class="imgg"></div>
    <div class="register-container">
        <div class="register-logo">
            <img src="images/logo.jpg" alt="Barangay Logo">
            <h1>Barangay Sto. Rosario Kanluran</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center">
                <a href="login.php" class="btn-login">Go to Login</a>
            </div>
        <?php else: ?>
            <form id="registerForm" action="register.php" method="POST" class="register-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword" style="cursor: pointer;"></i>
                    </div>
                </div>


                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword" style="cursor: pointer;"></i>
                    </div>
                </div>


                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required placeholder="Please use valid email address">
                </div>

                <div class="form-group">
                    <label for="full_name"><i class="fas fa-id-card"></i> Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="address"><i class="fas fa-home"></i> Address</label>
                    <input type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>

                    
                    <div class="form-group">
                        <label for="street_address"><i class="fas fa-road"></i> Barangay Street Address</label>
                        <input type="text" id="street_address" name="street_address" required value="<?php echo isset($_POST['street_address']) ? htmlspecialchars($_POST['street_address']) : ''; ?>">
                    </div>

                    
                    <div class="form-group">
                        <label for="place_of_birth"><i class="fas fa-map-marker-alt"></i> Place of Birth</label>
                        <input type="text" id="place_of_birth" name="place_of_birth" required value="<?php echo isset($_POST['place_of_birth']) ? htmlspecialchars($_POST['place_of_birth']) : ''; ?>">
                    </div>

                    
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Are you the head of the family?</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="head_of_family" value="yes" onclick="toggleFamilyFields()" <?php echo (isset($_POST['head_of_family']) && $_POST['head_of_family'] === 'yes') ? 'checked' : ''; ?>>
                                Yes
                            </label>
                            <label>
                                <input type="radio" name="head_of_family" value="no" onclick="toggleFamilyFields()" <?php echo (isset($_POST['head_of_family']) && $_POST['head_of_family'] === 'no') ? 'checked' : ''; ?>>
                                No
                            </label>
                        </div>
                    </div>


                    
                    <div class="form-group" id="childrenSection" style="display: none;">
                        <label for="num_children">Number of Children</label>
                        <input type="number" id="num_children" name="num_children" min="0" value="<?php echo isset($_POST['num_children']) ? htmlspecialchars($_POST['num_children']) : ''; ?>" onchange="generateChildInputs()">
                        
                        <div id="childrenInputs">
                            
                        </div>
                    </div>

                    
                    <div class="form-group" id="headNameSection" style="display: none;">
                        <label for="head_name">Name of Head of the Family</label>
                        <input type="text" id="head_name" name="head_name" value="<?php echo isset($_POST['head_name']) ? htmlspecialchars($_POST['head_name']) : ''; ?>">
                    </div>


                </div>

                <div class="form-group">
                    <label for="contact_number"><i class="fas fa-phone"></i> Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">--Select Gender--</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="civil_status"><i class="fas fa-ring"></i> Civil Status</label>
                    <select id="civil_status" name="civil_status" required>
                        <option value="">--Select Civil Status--</option>
                        <option value="Single" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                        <option value="Separated" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="pwd" <?php echo (isset($_POST['pwd'])) ? 'checked' : ''; ?>> Are you a Person with Disability (PWD)?</label>
                </div>

                <div class="form-group">
                    <label for="nationality"><i class="fas fa-flag"></i> Nationality</label>
                    <input type="text" id="nationality" name="nationality" value="<?php echo isset($_POST['nationality']) ? htmlspecialchars($_POST['nationality']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="registered_voter" <?php echo (isset($_POST['registered_voter'])) ? 'checked' : ''; ?>> Are you a registered voter?</label>
                </div>

                <div class="form-group">
                    <label for="occupation"><i class="fas fa-briefcase"></i> Occupation (Type "Student" if you are a Student)</label>
                    <input type="text" id="occupation" name="occupation" value="<?php echo isset($_POST['occupation']) ? htmlspecialchars($_POST['occupation']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="proof_of_residency"><i class="fas fa-id-card"></i> Proof of Residency (ID)</label>
                    <input type="file" id="proof_of_residency" name="proof_of_residency" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-group">
                     <label class="checkbox-label">
                        <input type="checkbox" name="terms_agreement" required class="terms_agreement" <?php echo (isset($_POST['terms_agreement'])) ? 'checked' : ''; ?>>
                        I agree to the terms and conditions and data processing for account registration.
                    </label>
                </div>

                <button type="button" class="btn-register" onclick="confirmRegistration()">Register</button>

                <div class="register-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div id="confirmationPopup" class="popup-overlay">
      <div class="popup-box">
        <h2>Confirm Registration</h2>
        <p>Please confirm/check your information before registering.</p>
        <div class="popup-buttons">
          <button id="confirmBtn">Proceed</button>
          <button id="cancelBtn">Cancel</button>
        </div>
      </div>
    </div>

    <script>
        function confirmRegistration() {
            document.getElementById('confirmationPopup').style.display = 'flex';
        }

        document.getElementById('confirmBtn').addEventListener('click', function() {
            document.getElementById('registerForm').submit();
        });

        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('confirmationPopup').style.display = 'none';
        });

    //password eye
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // confirm pass
        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const confirmPasswordField = document.getElementById('confirm_password');
            const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
            confirmPasswordField.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

    </script>

    <style>
    .popup-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .popup-box {
      background: #fff;
      padding: 25px 35px;
      border-radius: 8px;
      text-align: center;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }

    .popup-box h2 {
      margin-bottom: 10px;
      color: #333;
    }

    .popup-box p {
      color: #555;
      margin-bottom: 20px;
    }

    .popup-buttons {
      display: flex;
      justify-content: space-around;
    }

    .popup-buttons button {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    #confirmBtn {
      background-color: #28a745;
      color: #fff;
    }

    #cancelBtn {
      background-color: #dc3545;
      color: #fff;
    }
    
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

    /* para dun sa head of the fam */
    
    .register-form .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
    }

    .register-form .form-group input[type="text"],
    .register-form .form-group input[type="number"],
    .register-form .form-group input[type="date"],
    .register-form .form-group input[type="email"],
    .register-form .form-group input[type="password"],
    .register-form .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background-color: #f9f9f9;
        font-size: 14px;
    }

    /
    .register-form .form-group input[type="radio"] {
        margin-right: 6px;
        margin-left: 10px;
    }

    
    #childrenSection {
        margin-top: 15px;
        background-color: #f0f8ff;
        padding: 15px;
        border-left: 4px solid #007bff;
        border-radius: 6px;
    }

    #childrenSection label {
        font-weight: bold;
    }

    #childrenInputs {
        margin-top: 10px;
    }

    .child-entry {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .child-entry input {
        flex: 1;
    }

    
    #headNameSection {
        margin-top: 15px;
        background-color: #fff3cd;
        padding: 15px;
        border-left: 4px solid #ffc107;
        border-radius: 6px;
    }

    #headNameSection label {
        font-weight: bold;
    }


    .radio-group {
    display: flex;
    gap: 20px;
    margin-top: 8px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: normal;
    cursor: pointer;
}

.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding-right: 30px;
    padding-left: 10px;
    font-size: 16px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;  
    color: #555;  
}


    </style>

        <script>
        function toggleFamilyFields() {
            const isHead = document.querySelector('input[name="head_of_family"]:checked').value === 'yes';
            document.getElementById('childrenSection').style.display = isHead ? 'block' : 'none';
            document.getElementById('headNameSection').style.display = isHead ? 'none' : 'block';
        }

        function generateChildInputs() {
            const num = parseInt(document.getElementById('num_children').value);
            const container = document.getElementById('childrenInputs');
            container.innerHTML = '';

            for (let i = 1; i <= num; i++) {
                container.innerHTML += `
                    <div class="child-info">
                        <label>Child ${i} Name:</label>
                        <input type="text" name="children[${i}][name]" required>
                        <label>Age:</label>
                        <input type="number" name="children[${i}][age]" min="0" required>
                    </div>
                `;
            }
        }


        document.addEventListener("DOMContentLoaded", function() {
            toggleFamilyFields();
        });
        </script>

</body>
</html>

