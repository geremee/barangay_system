<?php
function send_email($to, $subject, $message) {


    
    $headers = "From: Barangay System <noreply@barangay.example.com>\r\n";
    $headers .= "Reply-To: noreply@barangay.example.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>