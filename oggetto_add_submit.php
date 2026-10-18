<?php
// oggetto__submit.php

require_once 'config/header.php';
require 'config/connect.php';
require_once 'functions/function.php';

// Richiesta delle variabile inviate da oggetto_add.php
$idrinvenimento = $_REQUEST['idrinvenimento'];

// ricava data, ora e operatore corrente per la movimentazione oggetto
$time = time();
$datainserimento = date('d/m/Y', $time);
// Modifica della data per il DB
$datainserimento = convertiDataEA($datainserimento);
$orainserimento = date('H:i', $time);
$cognomeoperatore = addslashes($_SESSION['cognome']);
$nomeoperatore = $_SESSION['nome'];
$abbrevqualifica = $_SESSION['abbrevqualifica'];
$operatore = $abbrevqualifica . " " . $cognomeoperatore . " " . $nomeoperatore;
$idorganigramma = $_SESSION['idorganigramma'];
$luogo = $_SESSION['citta'];

$luogorinvenimento = convertiApici($_REQUEST['luogorinvenimento']) . " - " . convertiApici($_REQUEST['luogospecifico']);
$datarinvenimento = convertiDataEA($_REQUEST['datarinvenimento']);
$brevedescrizioneoggetto = convertiApici($_REQUEST['brevedescrizioneoggetto']);
$note_rinv = convertiApici($_REQUEST['note_rinv']);

// Aggiornamento del DB rinvenimenti ponendo lo stato del rinvenimento a "in carico"
$query = "UPDATE oggetti2.rinvenimenti 
              SET luogorinvenimento='$luogorinvenimento', 
                  datarinvenimento='$datarinvenimento',
                  brevedescrizioneoggetto='$brevedescrizioneoggetto', 
                  statorinvenimento='in carico',
                  idstato=10,
                  note_rinv='$note_rinv'
              WHERE idrinvenimento='$idrinvenimento'";
// Questo replace serve quando un campo e vuoto, in modo da sotituirlo con NULL
$query = clean_query_update($query);
mysqli_query($conn,$query) or die("Query non valida:<br>" . $query . "<br>" .  mysqli_error($conn));

// Nel caso in cui si effettui una modifica bisogna prima eliminare la lista oggetti presente.
$query_del = "DELETE FROM oggetti2.listaoggetti WHERE idrinvenimento=$idrinvenimento";
mysqli_query($conn,$query_del) or die("Query non valida:<br>" . $query_del . "<br>" .  mysqli_error($conn));
$nrdelete = mysqli_affected_rows($conn);

// Inserimento della lista oggetti associata al rinvenimento
$tipooggetto = $_REQUEST['tipooggetto'];
$descroggetto = $_REQUEST['descroggetto'];
foreach ($tipooggetto as $key => $tipooggetto_value) {
    $descrizioneoggetto = addslashes($descroggetto[$key]);
    // Aggiornamento del DB lista oggetti con l'inserimento di tutti gli oggetti presenti ed impostazione del idstato a 1 (in carico)
    $query_LO_ins = "INSERT INTO oggetti2.listaoggetti (idrinvenimento,tipooggetto,descrizioneoggetto,idstatooggetto) 
                        VALUES('$idrinvenimento','$tipooggetto_value','$descrizioneoggetto','1')";
    mysqli_query($conn,$query_LO_ins) or die("Query non valida:<br>" . $query_LO_ins . "<br>" .  mysqli_error($conn));
}

// ------------------ Aggiungere if ($nrdelete == 0)

// Creazione di un nuovo record nel DB movimentazione oggetto, con data, ora, operatore loggato automatici, che assegna gli oggetti del nuovo
// rinvenimento 
$query_add_mov = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,posizione,datamovimentazione,oramovimentazione,statomovimentazione,notemovimentazione,idorganigramma) 
                 VALUES('$idrinvenimento','$operatore','$luogo','$datainserimento','$orainserimento','in carico','Inserimento nuovo Rinvenimento','$idorganigramma')";
mysqli_query($conn,$query_add_mov) or die("Query non valida:<br>" . $query_add_mov . "<br>" .  mysqli_error($conn));

// Nel caso di una modifica aggiunge alle note che è stata effettuata una modifica inserendo il link al documento prima della modifica
$idmove = mysqli_insert_id($conn);;    // Recupero l'idmovimentazione
if ($nrdelete > 0)  {
    $query_iddoc_rinv = "SELECT iddocumento_rinv FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento";
    $result = mysqli_query($conn,$query_iddoc_rinv) or die("Query non valida:<br>" . $query_iddoc_rinv . "<br>" . mysqli_error($conn));
    $rows = mysqli_fetch_assoc($result);
    $iddocumento_rinv = $rows['iddocumento_rinv'];
    $notemove = "Rinvenimento Modificato, documento originale: $iddocumento_rinv";
    $query_update_move = "UPDATE oggetti2.movimentazionioggetti SET notemovimentazione='$notemove' WHERE idmovimentazionioggetto=$idmove";
    mysqli_query($conn,$query_update_move) or die("Query non valida:<br>" . $query_update_move . "<br>" .  mysqli_error($conn));
    // Elimino da rinvenimenti l'id del documento rinvenimento dal database orme.documento, in modo che venga rigenerato
    $query_update_rinv = "UPDATE oggetti2.rinvenimenti SET iddocumento_rinv=NULL WHERE idrinvenimento=$idrinvenimento";
    mysqli_query($conn,$query_update_rinv) or die("Query non valida:<br>" . $query_update_rinv . "<br>" .  mysqli_error($conn));

}

echo "<meta http-equiv='refresh' content='0;url=verbale_rinvenimento.php?idrinvenimento=$idrinvenimento'/>";
?>