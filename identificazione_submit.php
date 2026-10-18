<?php

require_once 'config/header.php';
require_once 'functions/function.php';

// funzione interfaccia con il DB
function add_persona($conn,$idrinvenimento, $tipopersona, $array_value) {
    
    // Individua il tipo di persona da trattare
    if ($tipopersona == 1)
        $label_id = "idconsegnante";
    if ($tipopersona == 2)
        $label_id = "idproprietario";
    if ($tipopersona == 3)
        $label_id = "idritirante";

    // Recupero dal DB i tre tipi di persone associati al rinvenimento in esame
    $query = "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento='$idrinvenimento'";
    $listapersone = mysqli_query($conn,$query) or die("Query non valida 1: " . $query . " " . mysqli_error($conn));
    $row = mysqli_fetch_array($listapersone);
    $idconsegnante = $row['idconsegnante'];
    $idproprietario = $row['idproprietario'];
    $idritirante = $row['idritirante'];

    // Se è sconosciuto settiamo a NULL il valore dell'idpersona del tipo passato come parametro nel DB Rinvenimenti
    if ($array_value['radio'] == 'sconosciuto') {
        $query = "UPDATE oggetti2.rinvenimenti SET $label_id=NULL WHERE idrinvenimento='$idrinvenimento'";
        mysqli_query($conn,$query) or die("Query non valida 2: " . $query . " " . mysqli_error($conn));
    } elseif ($array_value['radio'] == 'proprietario') {
        // Se è stato selezionato proprietario associa al idritirante l'idproprietario perchè coincidono
        $query = "UPDATE oggetti2.rinvenimenti SET $label_id='$idproprietario' WHERE idrinvenimento='$idrinvenimento'";
        mysqli_query($conn,$query) or die("Query non valida 3: " . $query . " " . mysqli_error($conn));
    } else {
        // In questo caso abbiamo dei valori di persona, allora bisogna controllare se esiste già quel tipo di persona 
        // associato al rinvenimento in questione, in tal caso effettuare un update, altrimeni bisogna inserire un nuovo record
        if (${$label_id} == NULL) {
            $array_value['datanascita'] = empty($array_value['datanascita']) ? "NULL" : "$array_value[datanascita]";
            $array_value['rilasciatoil'] = empty($array_value['rilasciatoil']) ? "NULL" : "$array_value[rilasciatoil]";
            $query_insert = "INSERT INTO oggetti2.persone 
                    (tipopersona, idrinvenimento, cognome, nome, datanascita, luogonascita, residenza, 
                     via, documento, documentonumero, rilasciatoda, rilasciatoil, telefono, note) 
                 VALUES 
                    ('$tipopersona', '$idrinvenimento', '$array_value[cognome]', '$array_value[nome]', '$array_value[datanascita]', 
                     '$array_value[luogonascita]', '$array_value[residenza]', '$array_value[via]', '$array_value[documento]', 
                     '$array_value[documentonumero]', '$array_value[rilasciatoda]', '$array_value[rilasciatoil]', '$array_value[telefono]', 
                     '$array_value[note]') 
                 ON DUPLICATE KEY UPDATE 
                    cognome = VALUES(cognome),
                    nome = VALUES(nome),
                    datanascita = VALUES(datanascita),
                    luogonascita = VALUES(luogonascita),
                    residenza = VALUES(residenza),
                    via = VALUES(via),
                    documento = VALUES(documento),
                    documentonumero = VALUES(documentonumero),
                    rilasciatoda = VALUES(rilasciatoda),
                    rilasciatoil = VALUES(rilasciatoil),
                    telefono = VALUES(telefono),
                    note = VALUES(note);";
            // Questo replace serve quando un campo e' vuoto, in modo da sotituirlo con NULL
            $query_insert = clean_query_insert($query_insert);

            mysqli_query($conn,$query_insert) or die("Query non valida 4:<br/>" . $query_insert . "<br/>" . mysqli_error($conn));
            // Recupera l'id del record inserito
            ${$label_id} = mysqli_insert_id($conn);
            // lo aggiunge al DB rinvenimenti
            $query = "UPDATE oggetti2.rinvenimenti SET $label_id='${$label_id}' WHERE idrinvenimento=$idrinvenimento";
            mysqli_query($conn,$query) or die("Query non valida 5:<br/>" . $query . "<br/>" . mysqli_error($conn));
        }
        // altrimenti creiamo un nuovo record in persone e poi recuperiamo l'idpersona e facciamo un update in rinvenimenti
        else {
            $query = "UPDATE oggetti2.persone SET cognome='$array_value[cognome]',nome='$array_value[nome]',
                              datanascita='$array_value[datanascita]',luogonascita='$array_value[luogonascita]',
                              residenza='$array_value[residenza]',via='$array_value[via]',documento='$array_value[documento]',
                              documentonumero='$array_value[documentonumero]',rilasciatoda='$array_value[rilasciatoda]',
                              rilasciatoil='$array_value[rilasciatoil]',telefono='$array_value[telefono]',note='$array_value[note]'
                      WHERE idrinvenimento='$idrinvenimento' AND tipopersona='$tipopersona'";
            // Questo replace serve quando il campo data è vuoto, in modo da sostituirlo con NULL
            $query = clean_query_update($query);
            mysqli_query($conn,$query) or die("Query non valida in UPDATE: " . $query . " - " . mysqli_error($conn));
        }
    }
}

// -----------------------------------------------------------------------------
// Recupero dati dalla form ----------------------------------------------------
$tipopersona = $_REQUEST['tipopersona'];
$idrinvenimento = $_REQUEST['idrinvenimento'];
$ajax = $_REQUEST['ajax'];

// indica se si sta modificando il proprietario
$edit_own = $_REQUEST['edit_own'];
if ($edit_own == '')
    $edit_own = FALSE;

// indica se si sta modificando il verbale di restituzione
$edit_rest = $_REQUEST['edit_rest'];
if ($edit_rest == '')
    $edit_rest = FALSE;

$array_value['radio'] = $_REQUEST['radio'];
$array_value['cognome'] = addslashes($_REQUEST['cognome']);
$array_value['nome'] = addslashes($_REQUEST['nome']);
$array_value['datanascita'] = convertiDataEA($_REQUEST['datanascita']);
$array_value['luogonascita'] = addslashes($_REQUEST['luogonascita']);
$array_value['residenza'] = addslashes($_REQUEST['residenza']);
$array_value['via'] = addslashes($_REQUEST['via']);
$array_value['documento'] = addslashes($_REQUEST['documento']);
$array_value['documentonumero'] = $_REQUEST['documentonumero'];
$array_value['rilasciatoda'] = addslashes($_REQUEST['rilasciatoda']);
$array_value['rilasciatoil'] = convertiDataEA($_REQUEST['rilasciatoil']);
$array_value['telefono'] = $_REQUEST['telefono'];
$array_value['note'] = addslashes($_REQUEST['note']);
//print_r($array_value);
// -----------------------------------------------------------------------------

// Aggiungiamo o aggiorniamo la persona identificata usando la funzione dichiarata all'inizio di questo modulo
add_persona($conn,$idrinvenimento, $tipopersona, $array_value);

// Trattiamo il consegnante ----------------------------------------------------
if ($tipopersona == 1) {
    // Passo all'identificazione dell'eventuale proprietario
    echo "<meta http-equiv='refresh' content='0;url=identificazione.php?tipopersona=2&idrinvenimento=$idrinvenimento'/>";
}
// -----------------------------------------------------------------------------

// Trattiamo il proprietario ---------------------------------------------------
if ($tipopersona == 2) {
    if (!$edit_own) {
        // Passo all'elenco degli oggetti
        echo "<meta http-equiv='refresh' content='0;url=oggetto_add.php?idrinvenimento=$idrinvenimento'/>";
    } else {
        // Aggiungi una movimentazione che indichi l'aggiunta o modifica del proprietario
        // ricava data, ora e operatore corrente
        $time = time();
        $datainserimento = date('d/m/Y', $time);
        $datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
        $orainserimento = date('H:i', $time);
        $idorganigramma = $_SESSION['idorganigramma'];
        $cognomeoperatore = addslashes($_SESSION['cognome']);
        $nomeoperatore = $_SESSION['nome'];
        $operatore = $cognomeoperatore . " " . $nomeoperatore;
        $query_mov_own = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,datamovimentazione,oramovimentazione,notemovimentazione,idorganigramma) 
                 VALUES('$idrinvenimento','$operatore','$datainserimento','$orainserimento','Aggiunto o modificato il proprietario','$idorganigramma')";
        $query_mov_own = clean_query_update($query_mov_own);
        mysqli_query($conn,$query_mov_own) or handle_error("Errore nella inserimento movimentazione in rinvenimento_view", $query_mov_own);
        // Ritorno al rinvenimento_view
        echo "<meta http-equiv='refresh' content='0;url=rinvenimento_view.php?idrinvenimento=$idrinvenimento'/>";
    }
}
// -----------------------------------------------------------------------------

// Trattiamo il ritirante ------------------------------------------------------
if ($tipopersona == 3) {
    if (($array_value['radio'] == 'proprietario') and ($ajax === 1))
        echo "<meta http-equiv='refresh' content='0;url=identificazione.php?tipopersona=3&idrinvenimento=$idrinvenimento'/>";
    else {
        // Verifica se esiste già un iddenuncia nel rinvenimento
        $result = mysqli_query($conn,"SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento='$idrinvenimento'");
        while ($rows = mysqli_fetch_array($result)) {
            $iddoc_rest = $rows['iddocumento_rest'];
        }
        // Se è stato già stampato il verbale di restituzione e non si sta facendo 
        // una modifica di un verbale di resistituzione esistente, salta l'inizializzazione 
        // e genera il verbale, altrimenti chiede l'inizializzazione
        if (($iddoc_rest != '') && (!$edit_rest))   {
            echo "<meta http-equiv='refresh' content='0;url=verbale_restituzione.php?idrinvenimento=$idrinvenimento'/>";
        }
        else    {
            echo "<meta http-equiv='refresh' content='0;url=verbale_restituzione_init.php?idrinvenimento=$idrinvenimento'/>";
        }
    }
}
// -----------------------------------------------------------------------------
?>