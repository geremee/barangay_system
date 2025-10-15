<?php

$password = 'hashed_password';


$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


echo $hashedPassword;
?>
