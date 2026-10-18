<?php

require($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/connect.php');

//session_start();
// Recupero il perid del utente loggato
$perid = $_SESSION['user_id'];
if (!isset($perid))
    $perid = 108818;

// Controlla se il perid dell'utente ha le autorizzazioni necessarie
$query_auth = "SELECT * FROM oggetti2.autorizzazioni WHERE perid='$perid'";
$result = mysqli_query($conn,$query_auth);
while ($rows = mysqli_fetch_array($result)) {
    $_SESSION['livello'] = $rows['livello'];
}

// Un utente normale ha livello 1000
if ($_SESSION['livello'] == '')
    $_SESSION['livello'] = 1000;
?>
