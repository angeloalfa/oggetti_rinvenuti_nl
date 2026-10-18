<?php

/**
 * Restituisce la stringa passata come argomento per effettuare un update
 * di valori nel DB, sostituendo il campi vuoti con NULL 
 * (es. SET campo1='' => SET campo1=NULL).
 * @param int $value stringa da codificare
 */
function clean_query_update($query)   {
    $query = str_replace("=''", "=NULL", $query);
    return $query;
}

/**
 * Restituisce la stringa passata come argomento per effettuare una insert
 * di valori nel DB, sostituendo il campi vuoti con NULL 
 * (es. VALUES ('','val1','','val2','') => VALUES (NULL,'val1',NULL,'val2',NULL)).
 * @param int $value stringa da codificare
 */
function clean_query_insert($query)   {
    $query = str_replace("'NULL'", "NULL", $query); 
    $query = str_replace("('',", "(NULL,", $query);
    $query = str_replace(",'')", ",NULL)", $query);
    $query = str_replace(",'',", ",NULL,", $query);
    $query = str_replace(",'',", ",NULL,", $query);
    $query = str_replace("('')", "(NULL)", $query);
    
    return $query;
}

// Testo del DB -> HTML sicuro. Decodifica prima le entità (es. &#39; scritto da convertiApici)
// per evitare la doppia codifica: vale sia per i dati vecchi (con entità) sia per i nuovi (grezzi).
function h($s) {
    return htmlspecialchars(html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
}

// Funzione che converte gli apici per inserire i valori recuperati dal DB nei value degli input
function convertiApici($testoDB) {
    $testoTMP = str_replace("'", '&#39;', $testoDB);
    return str_replace('"', '&#34;', $testoTMP);
}

//Funzione conversione data da  aaaa-mm-gg -> gg/mm/aaaa 
function convertiDataAE($datamysql) {
    $rsl = explode('-', $datamysql);
    $rsl = array_reverse($rsl);
    return implode($rsl, '/');
}

//Funzione conversione data da gg/mm/aaaa -> aaaa-mm-gg
function convertiDataEA($dataEur) {
    $rsl = explode('/', $dataEur);
    $rsl = array_reverse($rsl);
    return implode($rsl, '-');
}

//Funzione differenza date 
function datediff($tipo, $partenza, $fine) {
    switch ($tipo) {
        case "A" : $tipo = 365;
            break;
        case "M" : $tipo = (365 / 12);
            break;
        case "S" : $tipo = (365 / 52);
            break;
        case "G" : $tipo = 1;
            break;
    }
    $arr_partenza = explode("/", $partenza);
    $partenza_gg = $arr_partenza[0];
    $partenza_mm = $arr_partenza[1];
    $partenza_aa = $arr_partenza[2];
    $arr_fine = explode("/", $fine);
    $fine_gg = $arr_fine[0];
    $fine_mm = $arr_fine[1];
    $fine_aa = $arr_fine[2];
    $date_diff = mktime(12, 0, 0, $fine_mm, $fine_gg, $fine_aa) - mktime(12, 0, 0, $partenza_mm, $partenza_gg, $partenza_aa);
    $date_diff = floor(($date_diff / 60 / 60 / 24) / $tipo);
    return $date_diff;
}

function ControlloData($data) {
    if (!ereg("^[0-9]{2}/[0-9]{2}/[0-9]{4}$", $data)) {
        return false;
    } else {
        $arrayData = explode("/", $data);
        $Giorno = $arrayData[0];
        $Mese = $arrayData[1];
        $Anno = $arrayData[2];
        if (!checkdate($Mese, $Giorno, $Anno)) {
            return false;
        } else {
            return true;
        }
    }
}

function convertiOra($ora) {
    $ora_s = explode(':', $ora);
    $ora_t = mktime($ora_s[0], $ora_s[1]);
    return date('H:i', $ora_t);
}

//Funzione formattazione numeri
function number_pad($number, $n) {
    return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
}

//Funzione parsing degli Items
function itemparser($contents) {
    echo strpos($contents, 'prova');
    echo $pos;
}

//Funzione creazione Token
function uid($id) {
    $id_part = base_convert($id, 10, 36);
    $rand_part = str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789');
    return sprintf("%010s%s", $id_part, $rand_part);
}

// Change stato 
function change_state($idrinvenimento, $idstato, $stato) {
    //connessione al database   
    require($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/connect.php');

    $query = "Update oggetti2.rinvenimenti SET statorinvenimento='$stato',idstato=$idstato WHERE idrinvenimento=$idrinvenimento";
    mysqli_query($conn,$query);

    // ricava data, ora e operatore corrente
    $time = time();
    $datainserimento = date('d/m/Y', $time);
    $datainserimento = convertiDataEA($datainserimento);  // conversione formato DB
    $orainserimento = date('H:i', $time);
    $cognomeoperatore = addslashes($_SESSION['cognome']);
    $nomeoperatore = $_SESSION['nome'];
    $operatore = $cognomeoperatore . " " . $nomeoperatore;
    $idorganigramma = $_SESSION['idorganigramma'];

    // Viene poi inserita una movimentazione che tiene traccia di chi ha eseguito il cambio stato
    $query_mov = "INSERT INTO oggetti2.movimentazionioggetti (idrinvenimento,operatore,datamovimentazione,oramovimentazione,statomovimentazione,notemovimentazione,idorganigramma) 
                 VALUES('$idrinvenimento','$operatore','$datainserimento','$orainserimento','$stato','Rinvenimento $stato','$idorganigramma')";
    $query_mov = clean_query_update($query_mov);
    mysqli_query($conn,$query_mov);

    return 1;
}