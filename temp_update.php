<?php
require 'config/db.php';
$pdo->query("UPDATE pdf_settings SET 
    college_name = 'NATIONAL COLLEGE OF TECHNOLOGY', 
    college_address = 'National Building Near UBL Bank University Road Sargodha',
    college_email = 'ncet.sgd@gmail.com',
    college_phone = '0316-7772003 | 0316-7772004',
    header_color = '#000080'
    WHERE id = 1");
echo "DB Updated";
?>
