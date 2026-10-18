<?php

require_once 'config/header.php';

//connessione al database            
require 'config/connect.php';

require_once 'functions/function.php';

$idrinvenimento = $_REQUEST['idrinvenimento'];

// ricava data, ora e operatore corrente
$time = time();
$datainserimento = date('d/m/Y', $time);
$datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
$orainserimento = date('H:i', $time);
$cognomeoperatore = addslashes($_SESSION['cognome']);
$nomeoperatore = $_SESSION['nome'];
$abbrevqualifica = $_SESSION['abbrevqualifica'];
$operatore = $abbrevqualifica . " " . $cognomeoperatore . " " . $nomeoperatore;

// ricava i valori inseriti nel form
$note = $_REQUEST['notemovimentazioni'];

$query = mysqli_query($conn,"SELECT * FROM oggetti2.movimentazionioggetti WHERE idrinvenimento=$idrinvenimento ORDER BY idmovimentazionioggetto DESC LIMIT 1");
$movimentazioni = mysqli_fetch_array($query);
$lastposizione = $movimentazioni['posizione'];
$laststato = $movimentazioni['statomovimentazione'];

// Inserisci un nuovo record nel DB movimentazioni oggetti
mysqli_query($conn,"INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,posizione,datamovimentazione,oramovimentazione,statomovimentazione,notemovimentazione) 
                 VALUES('$idrinvenimento','$operatore','$lastposizione','$datainserimento','$orainserimento','$laststato','$note')");

mysqli_query($conn,"UPDATE oggetti2.rinvenimenti SET note_rinv = '$note' WHERE idrinvenimento='$idrinvenimento'");


echo " 
                   <tr>
                      <td>
          ";

echo "<div><meta http-equiv='refresh' content='0;url=rinvenimento_view.php?idrinvenimento=$idrinvenimento'/></div>";

echo "
                      </td>
                   </tr>

         ";

require_once 'config/footer.php';
?>
