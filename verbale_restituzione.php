<?php
// verbale_restituzione.php
require_once 'config/header.php';
require_once 'functions/function.php';
require_once 'functions/utility.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Log diagnostici: solo errori, mai dati personali
if (!function_exists('debug_log')) {
    function debug_log($msg) { error_log("[VERBALE RESTITUZIONE] " . $msg); }
}

// Messaggio d'errore dentro la pagina (senza mostrare query o dettagli tecnici)
if (!function_exists('vb_errore')) {
    function vb_errore($msg) {
        echo "<div class='vb'><div class='vb-errore'>" . h($msg) . "</div></div></td></tr>";
        require_once 'config/footer.php';
        exit;
    }
}

// Riga della tabella persone (null se non trovata)
function vr_persona($conn, $id) {
    $id = intval($id);
    if ($id <= 0) {
        return null;
    }
    $res = mysqli_query($conn, "SELECT * FROM oggetti2.persone WHERE idpersona=$id");
    return $res ? mysqli_fetch_assoc($res) : null;
}

// Valore di un campo libero: escapato, con a capo, oppure il testo "vuoto" indicato
function vr_val($v, $vuoto) {
    $v = trim((string)$v);
    return $v === '' ? h($vuoto) : nl2br(h($v));
}

echo "<tr><td>";

// ---------------------------------------------------------------------------
// PREPARAZIONE DEI DATI
// ---------------------------------------------------------------------------
$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);

$result = mysqli_query($conn, "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $result ? mysqli_fetch_assoc($result) : null;
if (!$rinv) {
    debug_log("Rinvenimento non trovato: id=$idrinvenimento");
    vb_errore("Rinvenimento non trovato.");
}

$brevedescrizioneoggetto = $rinv['brevedescrizioneoggetto'];
$iddenuncia              = $rinv['iddenuncia'];
$dichiarazioni           = $rinv['dichiarazioneritirante'];
$mancanti                = $rinv['oggettimancanti'];
$noteritiro              = $rinv['noteritiro'];
$idritirante             = $rinv['idritirante'];
$idproprietario          = $rinv['idproprietario'];

// Sessione dell'operatore
if (!isset($_SESSION['idorganigramma'])) {
    vb_errore("Sessione non valida: effettua di nuovo l'accesso.");
}
$idorganigramma = intval($_SESSION['idorganigramma']);
$luogo = (isset($_SESSION['citta']) && $_SESSION['citta'] !== '') ? $_SESSION['citta'] : 'Bologna';

if (isset($_SESSION['idarticolazione'])) {
    $idarticolazione = intval($_SESSION['idarticolazione']);
} else {
    $result_org = mysqli_query($conn, "SELECT idArticolazione FROM home.organigramma WHERE idOrganigramma=$idorganigramma");
    $rows_org = $result_org ? mysqli_fetch_assoc($result_org) : null;
    $idarticolazione = intval(isset($rows_org['idArticolazione']) ? $rows_org['idArticolazione'] : 0);
}

$idstato         = 10;
$idtipodocumento = 11;

// Data e ora del momento della stampa
$time        = time();
$dataatto    = date('d/m/Y', $time);
$dataatto_DB = convertiDataEA($dataatto);
$oraatto     = date('H:i', $time);

// Grado, cognome e nome dell'operatore
$op = get_stringa_dipendente($conn, get_perid($conn, $idorganigramma));

// Ritirante (obbligatorio) e proprietario
$rit = vr_persona($conn, $idritirante);
if (!$rit) {
    debug_log("Ritirante non trovato per idrinvenimento=$idrinvenimento");
    vb_errore("Dati del ritirante non trovati: completa l'identificazione prima di generare il verbale.");
}
$prop = vr_persona($conn, $idproprietario);
$nome_prop = $prop ? trim($prop['cognome'] . ' ' . $prop['nome']) : '';

// Il modulo di identificazione salva proprietario e ritirante come due righe distinte anche quando
// sono la stessa persona: gli id non coincidono mai, quindi il confronto va fatto sui dati anagrafici.
$ritirante_e_proprietario = ($idritirante == $idproprietario);
if (!$ritirante_e_proprietario && $prop && trim((string)$prop['cognome']) !== '') {
    $ritirante_e_proprietario = true;
    foreach (array('cognome', 'nome', 'datanascita', 'luogonascita', 'documento', 'documentonumero') as $k) {
        if (strcasecmp(trim((string)$prop[$k]), trim((string)$rit[$k])) !== 0) {
            $ritirante_e_proprietario = false;
            break;
        }
    }
}

// Lista oggetti, escludendo gli oggetti non in carico
$listaoggetti = '<ul type="disc">';
$n_oggetti = 0;
$result_LO = mysqli_query($conn, "SELECT * FROM oggetti2.listaoggettiplus WHERE idrinvenimento=$idrinvenimento AND idstatooggetto<4");
if (!$result_LO) {
    debug_log("ERRORE query listaoggettiplus: " . mysqli_error($conn));
} else {
    while ($o = mysqli_fetch_assoc($result_LO)) {
        $listaoggetti .= '<li>' . h($o['tipooggetto']) . ': ' . h($o['descrizioneoggetto']) . ';</li>';
        $n_oggetti++;
    }
}
$listaoggetti .= '</ul>';
if ($n_oggetti === 0) {
    debug_log("ATTENZIONE: nessun oggetto in carico per idrinvenimento=$idrinvenimento");
}

// ---------------------------------------------------------------------------
// Introduzione del verbale
// ---------------------------------------------------------------------------
$intro = 'In data ' . h($dataatto) . ' alle ore ' . h($oraatto) . ' il sottoscritto ' . h($op)
    . ", Agt./Uff. di Polizia Giudiziaria, in forza presso l'Ufficio in intestazione, da atto che ha restituito a "
    . h($rit['cognome']) . ' ' . h($rit['nome'])
    . ' nato il ' . h(convertiDataAE($rit['datanascita'])) . ' a ' . h($rit['luogonascita'])
    . ' e residente a ' . h($rit['residenza']) . ' in ' . h($rit['via'])
    . ' in possesso di ' . h($rit['documento']) . ' nr. ' . h($rit['documentonumero'])
    . ' rilasciato da ' . h($rit['rilasciatoda']) . ' il ' . h(convertiDataAE($rit['rilasciatoil']))
    . ' telefono nr. ' . h($rit['telefono']) . ', ';

if ($ritirante_e_proprietario) {
    $intro .= 'quale legittimo proprietario, quanto segue:';
} else {
    $intro .= 'quale delegato al ritiro, quanto segue di proprietà di '
        . h($nome_prop !== '' ? $nome_prop : 'persona non identificata') . ':';
}

// ---------------------------------------------------------------------------
// Eventuale denuncia
// ---------------------------------------------------------------------------
$soggetto = $nome_prop !== '' ? $nome_prop : 'Il proprietario';
if (empty($iddenuncia)) {
    $den = h($soggetto) . ' non ha sporto denuncia per gli oggetti smarriti.';
} else {
    $result_den = mysqli_query($conn, "SELECT * FROM oggetti2.denunce WHERE iddenuncia=" . intval($iddenuncia));
    $d = $result_den ? mysqli_fetch_assoc($result_den) : null;
    $ufficiodenuncia = isset($d['ufficiodenuncia']) ? $d['ufficiodenuncia'] : '';
    $avvenutoa       = isset($d['avvenutoa']) ? $d['avvenutoa'] : '';
    $datadenuncia    = convertiDataAE(isset($d['datadenuncia']) ? $d['datadenuncia'] : '');
    $den = h($soggetto) . ' ha sporto regolare denuncia per gli oggetti smarriti/rubati, presso '
        . h($ufficiodenuncia) . ' di ' . h($avvenutoa) . ' in data ' . h($datadenuncia) . '.';
}

// ---------------------------------------------------------------------------
// Documenti duplicati
// ---------------------------------------------------------------------------
$doc_duplicati = '';
$tipi_dup = array();
$result_dup = mysqli_query($conn, "SELECT tipooggetto FROM oggetti2.listaoggettiplus WHERE duplicato='1' AND idrinvenimento=$idrinvenimento");
if ($result_dup) {
    while ($r = mysqli_fetch_assoc($result_dup)) {
        $tipi_dup[] = h($r['tipooggetto']);
    }
}
if (count($tipi_dup) > 0) {
    $doc_duplicati = 'Il proprietario dichiara di aver richiesto il duplicato dei seguenti documenti: '
        . implode(', ', $tipi_dup)
        . '. È fatto obbligo al titolare di distruggere i documenti per cui è stato richiesto il duplicato.';
}

// ---------------------------------------------------------------------------
// Contenuto del verbale (HTML per TCPDF): tutti i valori sono escapati
// ---------------------------------------------------------------------------
$contenuto = '<table cellpadding="3" cellspacing="0" width="100%">'
    . '<tr><td width="18%"><b>Oggetto:</b></td>'
    . '<td width="82%">Verbale di restituzione di <b>' . h($brevedescrizioneoggetto) . '</b></td></tr>'
    . '</table><hr/><br/>';

$contenuto .= '<div style="text-align:justify;">' . $intro . '</div>' . $listaoggetti;
$contenuto .= '<div style="text-align:justify;">' . $den . '</div>';

if ($doc_duplicati !== '') {
    $contenuto .= '<br/><div style="text-align:justify;">' . $doc_duplicati . '</div>';
}

$contenuto .= '<br/><table cellpadding="3" cellspacing="0" width="100%">'
    . '<tr><td width="24%"><b>Oggetti mancanti:</b></td><td width="76%">' . vr_val($mancanti, 'Nessuno') . '</td></tr>'
    . '<tr><td><b>Dichiarazioni:</b></td><td>' . vr_val($dichiarazioni, 'Nessuna') . '</td></tr>'
    . '<tr><td><b>Note:</b></td><td>' . vr_val($noteritiro, 'Nessuna') . '</td></tr>'
    . '</table>';

$contenuto .= '<br/><div style="text-align:justify;">Fatto, letto, confermato e sottoscritto in data, ora e luogo di cui sopra.</div>';

// ---------------------------------------------------------------------------
// Creazione del documento in orme
// ---------------------------------------------------------------------------
$e_luogo     = mysqli_real_escape_string($conn, $luogo);
$e_data      = mysqli_real_escape_string($conn, $dataatto_DB);
$e_ora       = mysqli_real_escape_string($conn, $oraatto);
$e_descr     = mysqli_real_escape_string($conn, $brevedescrizioneoggetto);
$e_contenuto = mysqli_real_escape_string($conn, htmlspecialchars($contenuto, ENT_QUOTES));

$query_insert = "INSERT INTO orme.documento
                     (idarticolazione, idorganigramma, idprotocollo, idstato, idtipodocumento,
                      luogo, datariferimento, orariferimento, descrizione, contenuto)
                 VALUES ('$idarticolazione','$idorganigramma','$idrinvenimento','$idstato','$idtipodocumento',
                         '$e_luogo','$e_data','$e_ora','$e_descr','$e_contenuto')";

if (!mysqli_query($conn, $query_insert)) {
    debug_log("ERRORE INSERT orme.documento: " . mysqli_error($conn));
    vb_errore("Impossibile creare il verbale. Riprova o contatta l'assistenza.");
}
$iddocumento_rest = mysqli_insert_id($conn);

$query_update = "UPDATE oggetti2.rinvenimenti SET iddocumento_rest='" . intval($iddocumento_rest)
    . "' WHERE idrinvenimento=$idrinvenimento";
if (!mysqli_query($conn, $query_update)) {
    debug_log("ERRORE UPDATE iddocumento_rest: " . mysqli_error($conn));
    vb_errore("Verbale creato ma non collegato al rinvenimento. Contatta l'assistenza.");
}

$pdf_url = 'verbale_restituzione_pdf.php?iddocumento_rest=' . urlencode($iddocumento_rest);
?>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Verbale di restituzione - rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo h($brevedescrizioneoggetto); ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento; ?>">&larr; Dettagli rinvenimento</a>
            <button type="button" id="stampa-pdf">Stampa</button>
            <a class="btn" target="_blank" rel="noopener" href="<?php echo h($pdf_url); ?>">Apri in nuova scheda</a>
        </div>
    </div>

    <div class="vb-stage">
        <div id="pdf-loading">
            <div class="vb-spinner"></div>
            <div>Creazione del verbale in corso…</div>
        </div>
        <iframe id="pdf-frame" title="Verbale di restituzione"
                src="<?php echo h($pdf_url); ?>#view=FitH"></iframe>
    </div>

</div>

<script>
$(document).ready(function() {
    var pdfUrl = <?php echo json_encode($pdf_url); ?>;

    $('#pdf-frame').on('load', function() { $('#pdf-loading').fadeOut(150); });

    $('#stampa-pdf').click(function() {
        var f = document.getElementById('pdf-frame');
        try {
            f.contentWindow.focus();
            f.contentWindow.print();
        } catch (e) {
            window.open(pdfUrl, '_blank');
        }
    });
});
</script>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>