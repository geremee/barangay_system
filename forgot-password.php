<?php
require_once 'includes/config.php';


require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

$error = '';
$success = '';

$check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($check_columns->num_rows === 0) {
    die("Database configuration error: Required columns missing.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $token, $expires, $user['id']);

                if ($update_stmt->execute()) {
                    // Send email with reset link
                    $mail = new PHPMailer(true);

                    try {
                        //Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'barangay.storosariokanluran@gmail.com';  // Your Gmail
                        $mail->Password   = 'dlvk ausg kuxx nvpd'; // Replace with your generated App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        //Recipients
                        $mail->setFrom('barangay.storosariokanluran@gmail.com', 'Barangay System');
                        $mail->addAddress($email, $user['full_name']);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request';
                        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=$token";

                        $mail->Body = "
                            <p>Hi " . htmlspecialchars($user['full_name']) . ",</p>
                            <p>You requested a password reset. Click the link below to reset your password:</p>
                            <p><a href='$reset_link'>$reset_link</a></p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request this, please ignore this email.</p>
                            <br>
                            <p>Regards,<br>Barangay System Team</p>
                        ";

                        $mail->send();
                        $success = "A password reset link has been sent to your email address.";
                    } catch (Exception $e) {
                        error_log("Mailer Error: " . $mail->ErrorInfo);
                        $error = "Failed to send reset email. Please try again later.";
                    }
                } else {
                    $error = "Database error. Please try again.";
                }
                $update_stmt->close();
            } else {
                $error = "No account found with that email address";
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | BRGY System</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .login-container {
            max-width: 500px;
        }
        .instructions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img src="images/logo.jpg" alt="Barangay Logo">
            <h1>Password Recovery</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <p><a href="login.php">Return to login</a></p>
        <?php elseif (!$error): ?>
            <div class="instructions">
                <p>Enter your email address and you'll receive a password reset link.</p>
            </div>

            <form action="forgot-password.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <button type="submit" class="btn-login">Continue</button>

                <div class="login-footer">
                    <p>Remember your password? <a href="login.php">Login here</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
