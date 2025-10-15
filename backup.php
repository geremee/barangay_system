<?php

$host = 'localhost';  
$dbname = 'barangay_system'; 
$username = 'root'; 
$password = ''; 


$backupFile = 'backups/' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';


$command = "mysqldump --opt -h $host -u $username -p$password $dbname > $backupFile";


exec($command, $output, $result);


if ($result === 0) {
    echo "Database backup successful! Backup saved as $backupFile";
} else {
    echo "Error during backup!";
}
?>
