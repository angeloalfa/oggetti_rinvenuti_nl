<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');
require 'config/connect.php';
require_once 'functions/function.php';
require_once 'functions/utility.php';

// recupero i dati dalla form
$idrinv = $_REQUEST['idrinvenimento'];
$idufficio_trasm = $_REQUEST['ufficio_trasm'];
$tipo_uff_trasm = $_REQUEST['tipo_uff_trasm'];
$citta_trasm = $_REQUEST['citta_trasm'];
$indirizzo_trasm = $_REQUEST['indirizzo_trasm'];

// Estraggo il valore di idtrasmissione in rinevenimenti in modo da scegliere se 
// inserire un nuovo record o aggiornare quello esistente
$query = "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinv";
$result = mysqli_query($conn,$query);
$rows = mysqli_fetch_assoc($result);
$idtrasmissione = $rows['idtrasmissione'];

$time = time();
$data_out = date('Y-m-d H:i', $time);

// Se non è indicato nussun idtrasmissione bisogna creare un nuovo record ed aggiornare rinvenimenti
if (!isset($idtrasmissione)) {
    // Inserimento dei dati del DB trasmissioni
    $query_ins = "INSERT INTO oggetti2.trasmissioni (ufficio_trasm,tipo_uff_trasm,citta_trasm,indirizzo_trasm) 
                        VALUES('$idufficio_trasm','$tipo_uff_trasm','$citta_trasm','$indirizzo_trasm')";
    $query_ins = clean_query_insert($query_ins);
    mysqli_query($conn,$query_ins);

    // ne ricavo l'id
    $idtrasmissione = mysqli_insert_id($conn);;

    // Aggiorno il DB Rinvenimenti con l'idtrasmissione creato
    $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET idtrasmissione=$idtrasmissione WHERE idrinvenimento=$idrinv";
    $query_update_rinv = clean_query_update($query_update_rinv);
    mysqli_query($conn,$query_update_rinv) or handle_error("Errore nell'aggiornamento di oggetti2.rinvenimenti in trasmissione_submit", $query_update_rinv);
}
// altrimenti aggiorna il DB trasmissioni
else {
    $query_update_trasm = "UPDATE oggetti2.trasmissioni SET ufficio_trasm='$idufficio_trasm',tipo_uff_trasm='$tipo_uff_trasm',
                           citta_trasm='$citta_trasm',indirizzo_trasm='$indirizzo_trasm' WHERE idtrasmissioni=$idtrasmissione";
    $query_update_trasm = clean_query_update($query_update_trasm);
    mysqli_query($conn,$query_update_trasm) or handle_error("Errore nell'aggiornamento di oggetti2.rinvenimenti in trasmissione_submit", $query_update_trasm);
}

//// Cambia stato del rinvenimento in trasmesso
$query_stato_rinv = "UPDATE oggetti2.rinvenimenti SET statorinvenimento='trasmesso',data_uscita='$data_out' WHERE idrinvenimento=$idrinv";
mysqli_query($conn,$query_stato_rinv) or handle_error("Errore nell'aggiornamento di oggetti2.rinvenimenti in trasmissione_submit", $query_stato_rinv);

//// Aggiungi movimentazione
//// ricava data, ora e operatore corrente
$time = time();
$datainserimento = date('d/m/Y', $time);
$datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
$orainserimento = date('H:i', $time);
$idorganigramma = $_SESSION['idorganigramma'];
$cognomeoperatore = $_SESSION['cognome'];
$nomeoperatore = $_SESSION['nome'];
$operatore = $cognomeoperatore . " " . $nomeoperatore;
$ufficio_trasm = $ufficio_trasmissione[$idufficio_trasm];
$note = "Oggetto trasmesso presso:$ufficio_trasm $tipo_uff_trasm $citta_trasm $indirizzo_trasm";

echo $query_mov = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,datamovimentazione,oramovimentazione,
                                                          statomovimentazione,idorganigramma,notemovimentazione) 
                 VALUES('$idrinv','$operatore','$datainserimento','$orainserimento','trasmesso','$idorganigramma','$note')";
mysqli_query($conn,$query_mov) or handle_error("Errore nella inserimento movimentazione in rinvenimento_view", $query_mov);

// redirect a invio_manager
header('Location: verbale_trasmissione.php?idrinvenimento=' . $idrinv . '&idtrasmissione=' . $idtrasmissione);
?>
