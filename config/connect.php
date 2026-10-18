<?php

    //parametri di connessione al database
    require($_SERVER['DOCUMENT_ROOT'] . '/commons/config/config.php');
    
    $conn = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password,$mysql_dbname)
        or die('Error connecting to mysql');
    mysqli_set_charset($conn,'utf8');
?>
