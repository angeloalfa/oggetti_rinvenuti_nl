<?php

require_once 'config/header.php';
require 'config/connect.php';    //connessione al database            
require_once 'functions/function.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];

// ricava data, ora e operatore corrente
$time = time();
$datainserimento = date('d/m/Y', $time);
$datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
$orainserimento = date('H:i', $time);
$idorganigramma = $_SESSION['idorganigramma'];
$cognomeoperatore = addslashes($_SESSION['cognome']);
$nomeoperatore = $_SESSION['nome'];
$operatore = $cognomeoperatore . " " . $nomeoperatore;

// ricava i valori inseriti nel form
$lastposizione = $_REQUEST['lastposizione'];
$posizione = $_REQUEST['posizione'];
$note = $_REQUEST['notemovimentazioni'];

// Se viene cambiata effetivamente la posizione
if ($lastposizione != $posizione) {
    // Inserisci un nuovo record nel DB
    mysqli_query($conn,"INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,posizione,datamovimentazione,oramovimentazione,notemovimentazione,idorganigramma) 
                 VALUES('$idrinvenimento','$operatore','$posizione','$datainserimento','$orainserimento','$note','$idorganigramma')");

    // Sposta la disponibilità del rinvenimento da un posto polfer ad un altro
    $update_rinv = "UPDATE oggetti2.rinvenimenti SET luogoverbale='$posizione' WHERE idrinvenimento=$idrinvenimento";
    mysqli_query($conn,$update_rinv);
}

echo " 
                   <tr>
                      <td>
          ";

echo "<div><meta http-equiv='refresh' content='0;url=listamovimentazione.php?idrinvenimento=$idrinvenimento'/></div>";

echo "
                      </td>
                   </tr>

         ";

require_once 'config/footer.php';
?>
