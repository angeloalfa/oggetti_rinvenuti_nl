<!--Questo modulo viene utilizzato solo dal Settore Operativo e si occupa di inviare 
    più oggetti presso l'ufficio oggetti rinvenuti di Bologna, si occupa di recuperare 
    tutti gli idrinvenimenti selezionati attraverso i check nella Home Page e imposta, 
    per ognuno di essi un nuovo record nel DB trasmissioni, inoltre si occupa di cambiare 
    lo stato in 'trasmesso' e ne salva l'id di trasmissione nel DB rinvenimenti--> 

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');
require 'config/connect.php';
require_once 'functions/function.php';
require_once 'functions/utility.php';

echo " 
                   <tr>
                      <td>
          ";

// Gestisce la variabile lista_idrinv_da_inviare in modo tale da avere memoria della scelta fatta
session_start();
if (isset($_REQUEST['inviare']))
    $lista_idrinv_da_inviare = $_REQUEST['inviare'];
elseif (isset($_SESSION['lista_idrinv_da_inviare']))
    $lista_idrinv_da_inviare = $_SESSION['lista_idrinv_da_inviare'];
else
    $lista_idrinv_da_inviare = NULL;
$_SESSION['lista_idrinv_da_inviare'] = $lista_idrinv_da_inviare;

//$lista_idrinv_da_inviare = $_REQUEST['inviare'];
//print_r($lista_idrinv_da_inviare);
// Indirizzo Oggetti Rinvenuti Bologna
$idufficio_trasm = 0;
$ufficio_trasm = "All\'Ufficio Oggetti Rinvenuti del Comune";
$citta_trasm = "BOLOGNA";
$indirizzo_trasm = "Via Dell\'Industria, 2 ";

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

$count = 0;
foreach ($lista_idrinv_da_inviare as $key => $idrinv) {
    // Recupero un eventuale idtrasmissione dal rinvenimento in esame
    $query_rinv = "SELECT idtrasmissione FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinv";
    $result_rinv = mysqli_query($conn,$query_rinv);
    $row_rinv = mysqli_fetch_assoc($result_rinv);
    $idtrasmissione = $row_rinv['idtrasmissione'];
    if ($idtrasmissione == 0) {
        // Inserisco nel DB trasmissioni il recapito degli oggetti rinvenuti di Bologna
        $query_ins_trasm = "INSERT INTO oggetti2.trasmissioni (ufficio_trasm,citta_trasm,indirizzo_trasm) 
                                    VALUES ('$idufficio_trasm','$citta_trasm','$indirizzo_trasm')";
        mysqli_query($conn,$query_ins_trasm) or die("Query non valida: " . $query_ins_trasm . " " . mysqli_error($conn));
        // Ricavo l'id del nuovo inserimento e lo aggiungo a rinvenimenti
        $idtrasmissione = mysqli_insert_id($conn);;
        $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET idtrasmissione='$idtrasmissione',statorinvenimento='trasmesso' WHERE idrinvenimento=$idrinv";
        mysqli_query($conn,$query_update_rinv) or die("Query non valida: " . $query_update_rinv . " " . mysqli_error($conn));
    } else {
        // Se esiste già un riferimento ad una trasmissione effettuo solo l'update
        $query_update_trasm = "UPDATE oggetti2.trasmissioni SET ufficio_trasm='$idufficio_trasm',citta_trasm='$citta_trasm',indirizzo_trasm='$indirizzo_trasm' 
                                WHERE idtrasmissioni='$idtrasmissione'";
        mysqli_query($conn,$query_update_trasm) or die("Query non valida: " . $query_update_trasm . " " . mysqli_error($conn));
        // Rimette lo stato del rinvenimento in trasmesso
        $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET statorinvenimento='trasmesso' WHERE idrinvenimento=$idrinv";
        mysqli_query($conn,$query_update_rinv) or die("Query non valida: " . $query_update_rinv . " " . mysqli_error($conn));
    }
    // Registro la movimentazione effettuata
    $query_mov = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,datamovimentazione,oramovimentazione,
                                                          statomovimentazione,idorganigramma,notemovimentazione) 
                 VALUES('$idrinv','$operatore','$datainserimento','$orainserimento','trasmesso','$idorganigramma','$note')";
    mysqli_query($conn,$query_mov) or handle_error("Errore nella inserimento movimentazione in rinvenimento_view", $query_mov);
}

echo "
                      </td>
                   </tr>
         ";

mysqli_close($conn);
echo "<meta http-equiv='refresh' content='0;url=verbale_trasmissione_BO.php'/>";
?>