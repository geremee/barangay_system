<?php
require_once 'includes/config.php';

// User must be logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get logged in user’s ID (assuming stored in session)
$userId = $_SESSION['user_id'];  // Or whatever your session uses

$errors = [];
$success = "";

// Fetch existing user data
$stmt = $conn->prepare("
    SELECT username, email, full_name, street_address, place_of_birth, address,
           contact_number, date_of_birth, gender, civil_status, pwd,
           nationality, registered_voter, occupation, head_name, children_info, password
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    // Unexpected: user not found
    die("User not found.");
}
$user = $result->fetch_assoc();
$stmt->close();

// Decode children array (if exists)
$existingChildren = [];
if (!empty($user['children_info'])) {
    $existingChildren = json_decode($user['children_info'], true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $street_address = sanitizeInput($_POST['street_address']);
    $place_of_birth = sanitizeInput($_POST['place_of_birth']);
    $address = sanitizeInput($_POST['address']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $civil_status = sanitizeInput($_POST['civil_status']);
    $pwd = isset($_POST['pwd']) ? 1 : 0;
    $nationality = sanitizeInput($_POST['nationality']);
    $registered_voter = isset($_POST['registered_voter']) ? 1 : 0;
    $occupation = sanitizeInput($_POST['occupation']);
    
    // Head of family
    $head_of_family = isset($_POST['head_of_family']) ? $_POST['head_of_family'] : '';
    $head_name = ($head_of_family === 'no') ? sanitizeInput($_POST['head_name']) : '';
    $num_children = ($head_of_family === 'yes') ? intval($_POST['num_children']) : 0;
    $children = ($head_of_family === 'yes' && isset($_POST['children'])) ? $_POST['children'] : [];
    $children_serialized = ($head_of_family === 'yes') ? json_encode(array_values($children)) : null;

    // Password fields
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($email)) $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($street_address)) $errors[] = "Barangay street address is required.";
    if (empty($place_of_birth)) $errors[] = "Place of birth is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($contact_number)) $errors[] = "Contact number is required.";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($civil_status)) $errors[] = "Civil status is required.";
    if (empty($nationality)) $errors[] = "Nationality is required.";
    if (empty($occupation)) $errors[] = "Occupation is required.";

    // Password validation if password is entered
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
    }

    // Check if email is changed and unique
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = "Email is already in use by another account.";
        }
        $stmt->close();
    }

    // If no errors, update
    if (empty($errors)) {
        if (!empty($password)) {
            // Hash the new password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE users
                SET email = ?,
                    full_name = ?,
                    street_address = ?,
                    place_of_birth = ?,
                    address = ?,
                    contact_number = ?,
                    date_of_birth = ?,
                    gender = ?,
                    civil_status = ?,
                    pwd = ?,
                    nationality = ?,
                    registered_voter = ?,
                    occupation = ?,
                    head_name = ?,
                    children_info = ?,
                    password_hash = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssssssisissssi",
                $email,
                $full_name,
                $street_address,
                $place_of_birth,
                $address,
                $contact_number,
                $date_of_birth,
                $gender,
                $civil_status,
                $pwd,
                $nationality,
                $registered_voter,
                $occupation,
                $head_name,
                $children_serialized,
                $password_hash,
                $userId
            );
        } else {
            // No password change
            $stmt = $conn->prepare("
                UPDATE users
                SET email = ?,
                    full_name = ?,
                    street_address = ?,
                    place_of_birth = ?,
                    address = ?,
                    contact_number = ?,
                    date_of_birth = ?,
                    gender = ?,
                    civil_status = ?,
                    pwd = ?,
                    nationality = ?,
                    registered_voter = ?,
                    occupation = ?,
                    head_name = ?,
                    children_info = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssssssisisssi",
                $email,
                $full_name,
                $street_address,
                $place_of_birth,
                $address,
                $contact_number,
                $date_of_birth,
                $gender,
                $civil_status,
                $pwd,
                $nationality,
                $registered_voter,
                $occupation,
                $head_name,
                $children_serialized,
                $userId
            );
        }

        if ($stmt->execute()) {
            $success = "Your profile has been updated successfully.";
            // Optionally update session full_name if changed
            $_SESSION['full_name'] = $full_name;
        } else {
            $errors[] = "Failed to update. Please try again.";
        }

        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Settings | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <style>
        /* Update button */
        .btn-update {
            background-color: #007BFF; /* Bootstrap primary blue */
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 10px;
            font-size: 1rem;
        }

        .btn-update:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        /* Cancel button inside the form (reset or secondary style) */
        form button:not(.btn-update) {
            background-color: #6c757d; /* Bootstrap secondary gray */
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1rem;
        }

        form button:not(.btn-update):hover {
            background-color: #5a6268; /* Darker gray on hover */
        }

    </style>
</head>
<body class="settings-page">
<div class="main-content">
    <div class="container">

        <h2>Your Settings / Profile</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err) echo "<p>" . htmlspecialchars($err) . "</p>"; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="user-settings.php" class="settings-form" autocomplete="off">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username (cannot change)</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> New Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="full_name"><i class="fas fa-id-card"></i> Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="street_address"><i class="fas fa-road"></i> Barangay Street Address</label>
                <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($user['street_address']); ?>" required>
            </div>

            <div class="form-group">
                <label for="place_of_birth"><i class="fas fa-map-marker-alt"></i> Place of Birth</label>
                <input type="text" id="place_of_birth" name="place_of_birth" value="<?php echo htmlspecialchars($user['place_of_birth']); ?>" required>
            </div>

            <div class="form-group">
                <label for="address"><i class="fas fa-home"></i> Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact_number"><i class="fas fa-phone"></i> Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" required>
            </div>

            <div class="form-group">
                <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">--Select Gender--</option>
                    <option value="Male" <?php if ($user['gender'] === 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if ($user['gender'] === 'Female') echo 'selected'; ?>>Female</option>
                    <option value="Other" <?php if ($user['gender'] === 'Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="civil_status"><i class="fas fa-ring"></i> Civil Status</label>
                <select id="civil_status" name="civil_status" required>
                    <option value="">--Select Civil Status--</option>
                    <?php
                    $statuses = ['Single','Married','Widowed','Separated'];
                    foreach ($statuses as $st) {
                        $sel = ($user['civil_status'] === $st) ? 'selected' : '';
                        echo "<option value=\"$st\" $sel>$st</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="pwd" <?php if ($user['pwd']) echo 'checked'; ?>> Are you a Person with Disability (PWD)?</label>
            </div>

            <div class="form-group">
                <label for="nationality"><i class="fas fa-flag"></i> Nationality</label>
                <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($user['nationality']); ?>" required>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="registered_voter" <?php if ($user['registered_voter']) echo 'checked'; ?>> Are you a registered voter?</label>
            </div>

            <div class="form-group">
                <label for="occupation"><i class="fas fa-briefcase"></i> Occupation</label>
                <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($user['occupation']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-users"></i> Are you the head of the family?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="head_of_family" value="yes" onclick="toggleFamilyFields()" <?php echo (!empty($existingChildren) ? 'checked' : ''); ?>> Yes
                    </label>
                    <label>
                        <input type="radio" name="head_of_family" value="no" onclick="toggleFamilyFields()" <?php echo (empty($existingChildren) ? 'checked' : ''); ?>> No
                    </label>
                </div>
            </div>

            <div class="form-group" id="childrenSection" style="display: none;">
                <label for="num_children">Number of Children</label>
                <input type="number" id="num_children" name="num_children" min="0"
                       value="<?php echo !empty($existingChildren) ? count($existingChildren) : ''; ?>"
                       onchange="generateChildInputs()">
                <div id="childrenInputs">
                    <?php
                    if (!empty($existingChildren)) {
                        foreach ($existingChildren as $i => $c) {
                            $idx = $i + 1;
                            echo '
                            <div class="child-info">
                                <label>Child ' . $idx . ' Name:</label>
                                <input type="text" name="children[' . $idx . '][name]" value="' .
                                htmlspecialchars($c['name']) . '" required>
                                <label>Age:</label>
                                <input type="number" name="children[' . $idx . '][age]" min="0" value="' .
                                htmlspecialchars($c['age']) . '" required>
                            </div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="form-group" id="headNameSection" style="display: none;">
                <label for="head_name">Name of Head of the Family</label>
                <input type="text" id="head_name" name="head_name"
                       value="<?php echo htmlspecialchars($user['head_name']); ?>">
            </div>
            <br>
            <button type="submit" class="btn-update">Update Profile</button>
            <a href="index.php"><button type="button">Cancel</button></a>
        </form>
    </div>
</div>
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
