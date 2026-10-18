<?php

require_once 'config/header.php';

require 'config/connect.php'; //connessione al database 
require_once 'functions/function.php';

// Non viene visualizzato nulla all'utente, ma crea un nuovo recordo nel DB rinvenimenti, ricavando l'idrinvenimento
// e inserendo l'ora, la data e l'operatore loggato, l'ufficio e il luogo del verbale in modo automatico.
$time = time();

$datainserimento = date('d/m/Y', $time);
$datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
$orainserimento = date('H:i', $time);
$idorganigramma = $_SESSION['idorganigramma'];
$cognomeoperatore = addslashes($_SESSION['cognome']);
$nomeoperatore = $_SESSION['nome'];
$abbrevqualifica = $_SESSION['abbrevqualifica'];
$operatore = $abbrevqualifica . " " . $cognomeoperatore . " " . $nomeoperatore;
$ufficio = addslashes($_SESSION['ufficio']);
$luogoverbale = addslashes($_SESSION['citta']);
$idart2nd = $_SESSION['idarticolazione2ndlevel'];

if ($idart2nd == 21)    {
    $idart = $_SESSION['idarticolazione'];
}
else    {
    $idart = $idart2nd;
}

// Creo il nuovo record 
$query = "INSERT INTO oggetti2.rinvenimenti (data,ora,ufficio,luogoverbale,operatore,idorganigramma_in,idart,idstato) 
            VALUES('$datainserimento','$orainserimento','$ufficio','$luogoverbale','$operatore','$idorganigramma','$idart',10)";

$query = clean_query_insert($query);
mysqli_query($conn,$query) or die("Query non valida: " . $query . "<br/>" . mysqli_error($conn));

// ne ricavo l'id
$idrinvenimento = mysqli_insert_id($conn);;

// Poi identifico il consegnante
echo "<meta http-equiv='refresh' content='0;url=identificazione.php?tipopersona=1&idrinvenimento=$idrinvenimento'/>";