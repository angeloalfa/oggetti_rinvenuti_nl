<!--Questo modulo viene utilizzato i--> 

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');
require_once 'config/config.php';
require 'config/connect.php';
require_once 'functions/function.php';
require_once 'functions/utility.php';

$luogo = $_SESSION['citta'];
// ricava data, ora e operatore corrente
$time = time();
$datainserimento = date('d/m/Y', $time);
$datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
$orainserimento = date('H:i', $time);
$idorganigramma = $_SESSION['idorganigramma'];
$cognomeoperatore = $_SESSION['cognome'];
$nomeoperatore = $_SESSION['nome'];
$operatore = $cognomeoperatore . " " . $nomeoperatore;

$note = "Oggetto rimesso in carico a seguito di annullamento della trasmissione";

// Recupera dal DB gli idrinvenimenti dell'ultima trasmissione effettuata in modo da poterla annullare, cioè 
// rimettere tutti gli oggetti interessatti alla trasmissione in carico
$query_last_trasm = "SELECT idrinvenimento
            FROM oggetti2.trasmissioni 
            LEFT JOIN oggetti2.rinvenimenti ON rinvenimenti.idtrasmissione=trasmissioni.idtrasmissioni 
            WHERE rinvenimenti.idtrasmissione=(SELECT MAX(trasmissioni.idtrasmissioni) FROM oggetti2.trasmissioni)";
$result_last_trasm = mysqli_query($conn,$query_last_trasm);
while($rows_last_trasm = mysqli_fetch_assoc($result_last_trasm)) {
    $idrinv_trasm = $rows_last_trasm['idrinvenimento'];
    $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET idtrasmissione=NULL,statorinvenimento='in carico' WHERE idrinvenimento=$idrinv_trasm";
    mysqli_query($conn,$query_update_rinv) or die("Query non valida: " . $query_update_rinv . " " . mysqli_error($conn));
    // Registro la movimentazione effettuata
    $query_mov = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,datamovimentazione,oramovimentazione,
                                                          statomovimentazione,idorganigramma,notemovimentazione) 
                 VALUES('$idrinv_trasm','$operatore','$datainserimento','$orainserimento','in carico','$idorganigramma','$note')";
    mysqli_query($conn,$query_mov) or handle_error("Errore nella inserimento movimentazione in rinvenimento_view", $query_mov);
}

mysqli_close($conn);
echo "<meta http-equiv='refresh' content='0;url=oggetti_rinvenuti_list.php'/>";
?>