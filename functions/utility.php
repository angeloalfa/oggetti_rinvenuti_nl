<?php

function handle_error($msg) {
    print_r($msg);
}

/**
 * Restituisce il perid associato all'idorganigramma passato come parametro
 * @param int $idorganigramma idorganigramma del dipendente
 * @return int perid
 */
function get_perid($conn,$idorganigramma) {
    $result = mysqli_query($conn,"SELECT * FROM home.organigramma WHERE idorganigramma=$idorganigramma AND dataFine is NULL");
    while ($row = mysqli_fetch_array($result)) {
        $perid = $row['perID'];
    }
    return $perid;
}

/**
 * Restituisce un array con Cognome, Nome, Qualifica e AbbrQualifica del dipendente
 * associato al perid passato come parametro
 * @param int $idorganigramma idorganigramma del dipendente
 * @return array dipendente
 */
function get_array_dipendente($conn,$perid) {
    $result1 = mysqli_query($conn,"SELECT * FROM anagrafica.anagrafica WHERE PerID=$perid");
    $row1 = mysqli_fetch_assoc($result1);
    $result2 = mysqli_query($conn,"SELECT qualifica,abbrevqualifica FROM dipendenti.vistadati
                                JOIN tabelle.qualifiche USING(codqualifica)
                                WHERE perid=$perid");
    $row2 = mysqli_fetch_array($result2);
    return array('Cognome' => $row1['Cognome'], 'Nome' => $row1['Nome'], 'Qualifica' => $row2['qualifica'], 'AbbrQualifica' => $row2['abbrevqualifica']);
}

/**
 * Restituisce una stringa con AbbrQualifica, Nome e Cognome del dipendente
 * associato al perid passato come parametro
 * @param int $idorganigramma idorganigramma del dipendente
 * @return string dipendente
 */
function get_stringa_dipendente($conn,$perid) {
    $result1 = mysqli_query($conn,"SELECT * FROM anagrafica.anagrafica WHERE PerID=$perid");
    $row1 = mysqli_fetch_assoc($result1);
    $result2 = mysqli_query($conn,"SELECT qualifica,abbrevqualifica FROM dipendenti.vistadati
                                JOIN tabelle.qualifiche USING(codqualifica)
                                WHERE perid=$perid");
    $row2 = mysqli_fetch_array($result2);
    return $row2['abbrevqualifica'] . " " . $row1['Nome'] . " " . $row1['Cognome'];
}

/**
 * Restituisce la denominazione dell'ufficio a cui appartiene il dipendente
 * associato al idorganigramma passato come parametro
 * @param int $idorganigramma idorganigramma del dipendente
 * @return string ufficio dipendente
 */
function get_ufficio($conn,$idorganigramma) {
    $result1 = mysqli_query($conn,"SELECT idArticolazione FROM home.organigramma WHERE idorganigramma=$idorganigramma AND dataFine is NULL");
    while ($row = mysqli_fetch_array($result1)) {
        $idarticolazione = $row['idArticolazione'];
    }
    // variabile di sicurezza, dopo 5 giri esce comunque, per non restare bloccati nel ciclo
    $count = 5;
    // Risale le articolazioni fino a trovare un skip==0
    do {
        $result2 = mysqli_query($conn,"SELECT * FROM home.articolazione WHERE idArticolazione=$idarticolazione");
        while ($row = mysqli_fetch_array($result2)) {
            $descrizione = $row['descrizione'];
            $idarticolazione = $row['idArticolazione'];
            $skip = $row['skip'];
            // Ufficio da saltare 
            if ($skip == 1) {
                $idarticolazione = $row['idParent'];
            }
        }
        $count = $count - 1;
    } while (($skip == 1) && ($count > 0));
    // Recupero la descrizione dell'ufficio associato all'idarticolazione ricavata
    return $descrizione;
}

function rinvenimento_change_state($conn,$idrinvenimento, $newstate) {
    $query = "UPDATE oggetti2.rinvenimenti SET statorinvenimento='$newstate' WHERE idrinvenimento=$idrinvenimento";
    mysqli_query($conn,$query) or die("Query non valida:<br/>" . $query . "<br/>" . mysqli_error($conn));
    return true;
}

function get_persona($rows, $label) {
    foreach ($rows as $x => $x_value) {
        ${$x} = h($x_value);
    }
    // Coverto i valori delle date dal formato DB
    $datanascita = convertiDataAE($datanascita);
    $rilasciatoil = convertiDataAE($rilasciatoil);
    // Costruisco la stringa con i dati della persona
    $persona = "<fieldset><legend>" . h($label) . "</legend><b>$cognome $nome</b> ";
    if ($datanascita != '')
        $persona .= "nato il $datanascita a $luogonascita ";
    if ($residenza != '')
        $persona .= "e residente a $residenza in via $via  ";
    if ($documento != '')
        $persona .= "in possesso di $documento nr.$documentonumero rilasciata dal $rilasciatoda in data $rilasciatoil ";
    if ($telefono != '')
        $persona .= "<br/>telefono: <b>$telefono</b> ";
    if ($note != '')
        $persona .= "<br/>note: $note";
    $persona .= "</fieldset>";
    return $persona;
}

/**
 * Consente di inserire nel DB movimentazionioggetti una nuova movimentazione
 * @param int $idrinvenimento idrinvenimento del rinvenimento
 * @param int $idorganigramma idorganigramma del dipendente
 * @param string $note note relative alla movimentazione
 * @param string $posizione posizione del oggetto rinvenuto
 */
function move_add($conn,$idrinvenimento, $idorganigramma, $note, $posizione = '') {
    // ricava data, ora corrente
    $time=  time();
    $datainserimento=convertiDataEA(date('d/m/Y', $time)); // generazione e conversione data in formato DB
    $orainserimento=date('H:i', $time);
    
    // Se non viene passata come parametro una posizione si recupera l'ultima posizione da DB
    if ($posizione === '') {
        $query_select_move = "SELECT * FROM oggetti2.movimentazionioggetti WHERE idrinvenimento=$idrinvenimento ORDER BY idmovimentazionioggetto DESC LIMIT 1";
        $result_select_move = mysqli_query($conn,$query_select_move);
        $row_select_move = mysqli_fetch_array($result_select_move);
        $posizione = $row_select_move['posizione'];
    }
    
    $operatore = get_stringa_dipendente($conn,get_perid($conn,$idorganigramma));
    
    $query_ins_move = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,posizione,datamovimentazione,oramovimentazione,notemovimentazione,idorganigramma) 
                 VALUES('$idrinvenimento','$operatore','$posizione','$datainserimento','$orainserimento','$note','$idorganigramma')";
    mysqli_query($conn,$query_ins_move) or die("Query non valida:<br/>" . $query_ins_move . "<br/>" . mysqli_error($conn));
}

?>
