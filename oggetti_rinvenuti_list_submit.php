<?php
require 'config/connect.php';      
require_once 'functions/function.php';

// Sanificazione minima degli input contro SQL Injection e Notice
$searchfield = isset($_REQUEST['searchfield']) ? mysqli_real_escape_string($conn, $_REQUEST['searchfield']) : "";
$search_prot = isset($_REQUEST['search_prot']) ? mysqli_real_escape_string($conn, $_REQUEST['search_prot']) : "";
$search_trasm = isset($_REQUEST['da_trasmettere']) ? $_REQUEST['da_trasmettere'] : "0";
$luogo = isset($_REQUEST['luogo']) ? mysqli_real_escape_string($conn, $_REQUEST['luogo']) : "Tutti";
$stato = isset($_REQUEST['stato']) ? (int)$_REQUEST['stato'] : 0;
$rapid = isset($_REQUEST['rapid']) ? $_REQUEST['rapid'] : '';

if ($luogo == "" && isset($_SESSION['citta'])) {
    $luogo = $_SESSION['citta'];
}

$query_vista = 0;

// Date per ricerca rapida
$now   = date("Y-m-d");
$week  = date("Y-m-d", strtotime("-7 days"));
$fifty = date("Y-m-d", strtotime("-15 days"));
$month = date("Y-m-d", strtotime("-1 month"));

// QUERY BASE 1: Ottimizzata (Seleziona solo lo stretto necessario)
$query_base1 = "SELECT r.idrinvenimento, r.luogoverbale AS posizione, r.datarinvenimento,
                       p.cognome, p.nome, r.brevedescrizioneoggetto, r.luogorinvenimento,
                       r.statorinvenimento, r.visto 
                FROM oggetti2.rinvenimenti r
                LEFT JOIN oggetti2.persone p ON r.idrinvenimento = p.idrinvenimento AND p.tipopersona = 2";

// QUERY BASE 2: Più veloce usando GROUP_CONCAT per evitare righe duplicate nel DOM
$query_base2 = "SELECT  r.idrinvenimento, r.luogoverbale AS posizione, r.datarinvenimento,
                        p.cognome, p.nome, r.brevedescrizioneoggetto, r.luogorinvenimento,
                        r.statorinvenimento, r.visto
                FROM oggetti2.rinvenimenti r
                LEFT JOIN oggetti2.persone p ON r.idrinvenimento = p.idrinvenimento AND p.tipopersona = 2
                LEFT JOIN oggetti2.listaoggetti lo ON r.idrinvenimento = lo.idrinvenimento
                LEFT JOIN oggetti2.tipioggetti t ON t.idtipioggetti = lo.tipooggetto";

// Clausole WHERE sullo stato
$where_clauses = [];
switch ($stato) {
    case 1: $where_clauses[] = "(r.statorinvenimento='in carico' OR r.statorinvenimento='in trattazione')"; break;
    case 2: $where_clauses[] = "r.statorinvenimento='riconsegnato'"; break;
    case 3: $where_clauses[] = "r.statorinvenimento='trasmesso'"; break;
    case 4: $where_clauses[] = "r.statorinvenimento='annullato'"; break;
}

function e($s) {
    return htmlspecialchars(
        html_entity_decode((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Filtro di ricerca testuale
if ($searchfield != "") {
    $query_vista = 1;
    $where_clauses[] = "(p.cognome LIKE '%$searchfield%' 
                        OR p.nome LIKE '%$searchfield%' 
                        OR r.brevedescrizioneoggetto LIKE '%$searchfield%' 
                        OR t.tipooggetto LIKE '%$searchfield%' 
                        OR lo.descrizioneoggetto LIKE '%$searchfield%')";
}

// Filtro Luogo
if ($luogo != "Tutti") {
    $where_clauses[] = "r.luogoverbale='$luogo'";
}

// Filtro Date Rapide
if ($rapid != '') {
    switch ($rapid) {
        case 1:  $where_clauses[] = "r.datarinvenimento='$now'"; break;
        case 7:  $where_clauses[] = "r.datarinvenimento>='$week'"; break;
        case 15: $where_clauses[] = "r.datarinvenimento>='$fifty'"; break;
        case 30: $where_clauses[] = "r.datarinvenimento>='$month'"; break;
        case 31: $where_clauses[] = "r.datarinvenimento<'$month'"; break;
    }
}

// Filtro Protocollo (Annulla gli altri filtri per specificità e velocità)
if ($search_prot != '') {
    $where_clauses = ["r.idrinvenimento=$search_prot"];
}

// Filtro da trasmettere
if ($search_trasm == 1) {
    $where_clauses[] = "r.visto=3";
}

// Composizione finale della query
$where = "";
if (count($where_clauses) > 0) {
    $where = " WHERE " . implode(" AND ", $where_clauses);
}

if ($query_vista === 1) {
    // Il GROUP BY impedisce il moltiplicarsi delle righe nel frontend se ci sono più oggetti associati
    $query = $query_base2 . $where . " GROUP BY r.idrinvenimento";
} else {
    $query = $query_base1 . $where;
}

$query .= " ORDER BY r.idrinvenimento DESC LIMIT 150"; // Limit di sicurezza per fluidità di caricamento

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Errore nella query: " . mysqli_error($conn));
}

// Render dei dati
$stati = [
    1 => 'In attesa di restituzione',
    2 => 'Accertamenti in corso',
    3 => 'Da trasmettere',
    0 => 'Da trattare',
];

// Mappatura delle icone SVG per ciascuno stato ($visto)
$icone_svg = [
    0 => '<svg class="chip-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm1-13h-2v6h6v-2h-4z"/></svg>', // Orologio / Da trattare
    1 => '<svg class="chip-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14h-2v-2h2zm0-4h-2V7h2z"/></svg>', // In attesa
    2 => '<svg class="chip-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>', // Lente / Accertamenti
    3 => '<svg class="chip-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>' // Freccia / Da trasmettere
];

while ($rows = mysqli_fetch_assoc($result)) {
    $id    = (int)$rows['idrinvenimento'];
    $data  = convertiDataAE($rows['datarinvenimento']);
    $visto = isset($stati[(int)$rows['visto']]) ? (int)$rows['visto'] : 0;
    $titolo = htmlspecialchars($stati[$visto], ENT_QUOTES);
    
    // Recupera l'icona corrispondente allo stato
    $icona_curr = $icone_svg[$visto] ?? $icone_svg[0];
    
    $cognome   = $rows['cognome'] ?? '';
    $nome      = $rows['nome'] ?? '';
    $posizione = $rows['posizione'] ?? '';

    echo "<tr class='tableheader' id='$id'>
        <td class='c'>$id</td>
        <td class='c'>$data</td>
        <td>" . e($rows['brevedescrizioneoggetto']) . "</td>
        <td>" . e($rows['luogorinvenimento']) . "</td>
        <td>" . htmlspecialchars(trim($cognome . " " . $nome), ENT_QUOTES) . "</td>
        <td>" . htmlspecialchars($posizione, ENT_QUOTES) . "</td>
        <td class='c'>
            <span class='check' style='display:none'>
                <input type='checkbox' name='inviare[]' value='$id'/>
            </span>
            <span class='vedi'>
                <span class='chip-stato chip-visto-$visto' title='$titolo'>
                    $icona_curr
                    <span>" . e($rows['statorinvenimento']) . "</span>
                </span>
            </span>
        </td>
    </tr>";
}
?>