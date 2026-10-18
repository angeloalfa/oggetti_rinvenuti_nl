<?php

require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

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

// Indirizzo Oggetti Rinvenuti Bologna
$ufficio_trasm = "All\'Ufficio Oggetti Rinvenuti del Comune";
$citta_trasm = "BOLOGNA";
$indirizzo_trasm = "Via Dell\'Industria, 2 ";

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
                                    VALUES ('$ufficio_trasm','$citta_trasm','$indirizzo_trasm')";
        mysqli_query($conn,$query_ins_trasm) or die("Query non valida: " . $query_ins_trasm . " " . mysqli_error($conn));
        // Ricavo l'id del nuovo inserimento e lo aggiungo a rinvenimenti
        $idtrasmissione = mysqli_insert_id($conn);;
        $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET idtrasmissione='$idtrasmissione' WHERE idrinvenimento=$idrinv";
        mysqli_query($conn,$query_update_rinv) or die("Query non valida: " . $query_update_rinv . " " . mysqli_error($conn));
    } else {
        // Se esiste già un riferimento ad una trasmissione effettuo solo l'update
        $query_update_trasm = "UPDATE oggetti2.trasmissioni SET ufficio_trasm='$ufficio_trasm',citta_trasm='$citta_trasm',indirizzo_trasm='$indirizzo_trasm' 
                                WHERE idtrasmissioni='$idtrasmissione'";
        mysqli_query($conn,$query_update_trasm) or die("Query non valida: " . $query_update_trasm . " " . mysqli_error($conn));
    }
}

echo "
                      </td>
                   </tr>
         ";

mysqli_close($conn);
echo "<meta http-equiv='refresh' content='0;url=verbale_trasmissione_BO.php'/>";
?>