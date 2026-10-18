<?php
// verbale_rinvenimento.php
require_once 'config/header.php';

require 'config/connect.php';
require_once 'functions/function.php';
require_once 'functions/utility.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Log diagnostici: solo errori e anomalie, mai dati personali
function debug_log($msg) {
    error_log("[VERBALE] " . $msg);
}

// Messaggio d'errore dentro la pagina (senza mostrare query o dettagli tecnici)
function vb_errore($msg) {
    echo "<div class='vb'><div class='vb-errore'>" . h($msg) . "</div></div></td></tr>";
    require_once 'config/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// Stringa descrittiva di una persona (testo semplice, da escapare all'uso)
// ---------------------------------------------------------------------------
function get_stringa_persona($conn, $idpersona) {
    if (empty($idpersona)) {
        return 'Sconosciuto';
    }

    $idpersona = intval($idpersona);
    $result = mysqli_query($conn, "SELECT * FROM oggetti2.persone WHERE idpersona=$idpersona");
    if (!$result) {
        debug_log("ERRORE query persone (idpersona=$idpersona): " . mysqli_error($conn));
        return 'Sconosciuto';
    }

    $rows = mysqli_fetch_assoc($result);
    if (!$rows) {
        debug_log("Nessuna persona trovata per idpersona=$idpersona");
        return 'Sconosciuto';
    }

    $cognome         = isset($rows['cognome']) ? $rows['cognome'] : '';
    $nome            = isset($rows['nome']) ? $rows['nome'] : '';
    $datanascita     = convertiDataAE(isset($rows['datanascita']) ? $rows['datanascita'] : '');
    $luogonascita    = isset($rows['luogonascita']) ? $rows['luogonascita'] : '';
    $residenza       = isset($rows['residenza']) ? $rows['residenza'] : '';
    $via             = isset($rows['via']) ? $rows['via'] : '';
    $documento       = isset($rows['documento']) ? $rows['documento'] : '';
    $documentonumero = isset($rows['documentonumero']) ? $rows['documentonumero'] : '';
    $rilasciatoda    = isset($rows['rilasciatoda']) ? $rows['rilasciatoda'] : '';
    $rilasciatoil    = convertiDataAE(isset($rows['rilasciatoil']) ? $rows['rilasciatoil'] : '');
    $telefono        = isset($rows['telefono']) ? $rows['telefono'] : '';

    if ($cognome === '') {
        debug_log("Persona idpersona=$idpersona trovata ma cognome vuoto");
        return 'Sconosciuto';
    }

    $persona = $cognome . ' ' . $nome;

    if ($datanascita !== '') {
        $persona .= ' nato/a il ' . $datanascita . ' a ' . $luogonascita;
    }
    if ($residenza !== '') {
        $persona .= ' e residente a ' . $residenza . ' in via ' . $via;
    }
    if ($documento !== '') {
        if (strtolower(trim($documento)) === 'sedicente') {
            $persona .= ', sedicente';
        } else {
            $persona .= ', in possesso di ' . $documento
                . ' nr. ' . $documentonumero
                . ' rilasciato da ' . $rilasciatoda
                . ' in data ' . $rilasciatoil;
        }
    }
    if ($telefono !== '') {
        $persona .= ', telefono: ' . $telefono;
    }

    return $persona;
}

// ---------------------------------------------------------------------------
// Lettura rinvenimento
// ---------------------------------------------------------------------------
echo "<tr><td>";

$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);

$result = mysqli_query($conn, "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
if (!$result) {
    debug_log("ERRORE query rinvenimenti: " . mysqli_error($conn));
    vb_errore("Errore nella lettura del rinvenimento.");
}
$rinv = mysqli_fetch_assoc($result);
if (!$rinv) {
    debug_log("Nessun rinvenimento trovato per id=$idrinvenimento");
    vb_errore("Rinvenimento non trovato.");
}

$iddocumento_rinv        = $rinv['iddocumento_rinv'];
$brevedescrizioneoggetto = $rinv['brevedescrizioneoggetto'];
$datarinvenimento        = $rinv['datarinvenimento'];
$note_rinv               = isset($rinv['note_rinv']) ? $rinv['note_rinv'] : '';

// ---------------------------------------------------------------------------
// Creazione del documento, se non esiste ancora
// ---------------------------------------------------------------------------
if (empty($iddocumento_rinv)) {

    $luogo          = isset($_SESSION['citta']) ? $_SESSION['citta'] : '';
    $idorganigramma = intval(isset($_SESSION['idorganigramma']) ? $_SESSION['idorganigramma'] : 0);

    // Lista oggetti dalla view listaoggettiplus
    $listaoggetti = '<ul type="disc">';
    $n_oggetti = 0;
    $result_ogg = mysqli_query($conn, "SELECT * FROM oggetti2.listaoggettiplus WHERE idrinvenimento=$idrinvenimento");
    if (!$result_ogg) {
        debug_log("ERRORE query listaoggettiplus: " . mysqli_error($conn));
    } else {
        while ($o = mysqli_fetch_assoc($result_ogg)) {
            $listaoggetti .= '<li>' . h($o['tipooggetto']) . ': ' . h($o['descrizioneoggetto']) . ';</li>';
            $n_oggetti++;
        }
    }
    $listaoggetti .= '</ul>';

    if ($n_oggetti === 0) {
        // Il verbale verrebbe creato senza oggetti: controllo diretto sulla tabella
        $check = mysqli_query($conn, "SELECT COUNT(*) AS tot FROM oggetti2.listaoggetti WHERE idrinvenimento=$idrinvenimento");
        $crow = $check ? mysqli_fetch_assoc($check) : array('tot' => '?');
        debug_log("ATTENZIONE: listaoggettiplus vuota per idrinvenimento=$idrinvenimento (righe in listaoggetti: " . $crow['tot'] . ")");
    }

    // Dati dell'atto (già letti sopra: nessuna seconda query)
    $dataatto_DB       = isset($rinv['data']) ? $rinv['data'] : '';
    $dataatto          = convertiDataAE($dataatto_DB);
    $oraatto           = convertiOra(isset($rinv['ora']) ? $rinv['ora'] : '');
    $luogorinvenimento = isset($rinv['luogorinvenimento']) ? $rinv['luogorinvenimento'] : '';
    $idproprietario    = isset($rinv['idproprietario']) ? $rinv['idproprietario'] : null;
    $idconsegnante     = isset($rinv['idconsegnante']) ? $rinv['idconsegnante'] : null;
    $operatore         = isset($rinv['operatore']) ? $rinv['operatore'] : '';

    $datarinvenimento_fmt = convertiDataAE($datarinvenimento);
    $str_proprietario     = get_stringa_persona($conn, $idproprietario);
    $str_consegnante      = get_stringa_persona($conn, $idconsegnante);

    // Contenuto del verbale (HTML per TCPDF): tutti i valori sono escapati
    $contenuto = '<table cellpadding="3" cellspacing="0" width="100%">'
        . '<tr><td width="18%"><b>Oggetto:</b></td>'
        . '<td width="82%">Verbale di rinvenimento di <b>' . h($brevedescrizioneoggetto) . '</b></td></tr>'
        . '<tr><td><b>Proprietario:</b></td><td>' . h($str_proprietario) . '</td></tr>'
        . '<tr><td><b>Consegnante:</b></td><td>' . h($str_consegnante) . '</td></tr>';

    if (trim((string)$note_rinv) !== '') {
        $contenuto .= '<tr><td><b>Note:</b></td><td>' . nl2br(h($note_rinv)) . '</td></tr>';
    }
    $contenuto .= '</table><hr/><br/>';

    $contenuto .= '<div style="text-align:justify;">In data ' . h($dataatto)
        . ' alle ore ' . h($oraatto)
        . ' il sottoscritto ' . h($operatore)
        . ', Agt./Uff. di Polizia Giudiziaria, in forza presso l\'Ufficio in intestazione,'
        . ' da atto che in data ' . h($datarinvenimento_fmt)
        . ' in ' . h($luogorinvenimento)
        . ' è stato rinvenuto quanto citato in oggetto e descritto dettagliatamente di seguito:</div>'
        . $listaoggetti;

    // Articolazione dell'organigramma
    $result_org = mysqli_query($conn, "SELECT * FROM home.organigramma WHERE idOrganigramma=$idorganigramma");
    if (!$result_org) {
        debug_log("ERRORE query organigramma (idorganigramma=$idorganigramma): " . mysqli_error($conn));
    }
    $rows_org = $result_org ? mysqli_fetch_assoc($result_org) : null;
    $idarticolazione = intval(isset($rows_org['idArticolazione']) ? $rows_org['idArticolazione'] : 0);

    $idstato         = 10;
    $idtipodocumento = 10;

    // Escape per il database (il contenuto resta codificato con htmlspecialchars come prima)
    $e_luogo       = mysqli_real_escape_string($conn, $luogo);
    $e_data        = mysqli_real_escape_string($conn, $dataatto_DB);
    $e_ora         = mysqli_real_escape_string($conn, $oraatto);
    $e_descr       = mysqli_real_escape_string($conn, $brevedescrizioneoggetto);
    $e_contenuto   = mysqli_real_escape_string($conn, htmlspecialchars($contenuto, ENT_QUOTES));

    $query_insert = "INSERT INTO orme.documento
                         (idarticolazione, idorganigramma, idprotocollo, idstato, idtipodocumento,
                          luogo, datariferimento, orariferimento, descrizione, contenuto)
                     VALUES ('$idarticolazione','$idorganigramma','$idrinvenimento','$idstato','$idtipodocumento',
                             '$e_luogo','$e_data','$e_ora','$e_descr','$e_contenuto')";

    if (!mysqli_query($conn, $query_insert)) {
        debug_log("ERRORE INSERT orme.documento: " . mysqli_error($conn));
        vb_errore("Impossibile creare il verbale. Riprova o contatta l'assistenza.");
    }
    $iddocumento_rinv = mysqli_insert_id($conn);

    $query_update = "UPDATE oggetti2.rinvenimenti SET iddocumento_rinv='" . intval($iddocumento_rinv)
        . "' WHERE idrinvenimento=$idrinvenimento";
    if (!mysqli_query($conn, $query_update)) {
        debug_log("ERRORE UPDATE iddocumento_rinv: " . mysqli_error($conn));
        vb_errore("Verbale creato ma non collegato al rinvenimento. Contatta l'assistenza.");
    }
}

$pdf_url = 'verbale_rinvenimento_pdf.php?iddocumento_rinv=' . urlencode($iddocumento_rinv);
?>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Verbale di rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo h($brevedescrizioneoggetto); ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento; ?>">&larr; Dettagli rinvenimento</a>
            <a class="btn" href="identificazione.php?tipopersona=1&amp;idrinvenimento=<?php echo $idrinvenimento; ?>">Modifica</a>
            <button type="button" id="stampa-pdf">Stampa</button>
            <a class="btn" target="_blank" rel="noopener" href="<?php echo h($pdf_url); ?>">Apri in nuova scheda</a>
        </div>
    </div>

    <div class="vb-stage">
        <div id="pdf-loading">
            <div class="vb-spinner"></div>
            <div>Creazione del verbale in corso…</div>
        </div>
        <iframe id="pdf-frame" title="Verbale di rinvenimento"
                src="<?php echo h($pdf_url); ?>#view=FitH"></iframe>
    </div>

</div>

<script>
$(document).ready(function() {
    var pdfUrl = <?php echo json_encode($pdf_url); ?>;

    // Nasconde l'indicatore quando il PDF è caricato
    $('#pdf-frame').on('load', function() { $('#pdf-loading').fadeOut(150); });

    // Stampa direttamente il PDF; se il browser non lo consente, lo apre in una nuova scheda
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