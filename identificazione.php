<?php
// identificazione.php
// Identificazione di consegnante (1), proprietario (2) e ritirante (3) di un rinvenimento.
// Parametri: idrinvenimento, tipopersona, edit_own (modifica del solo proprietario),
//            edit_rest (modifica per il verbale di restituzione)
require_once 'config/header.php';
require 'config/connect.php';   // connessione al database
require_once 'functions/function.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars(html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); }
}

// Errore dentro la pagina, con eventuali link di uscita (etichetta => indirizzo)
if (!function_exists('vr_errore')) {
    function vr_errore($msg, $links = array()) {
        echo "<div class='vb'><div class='vb-errore'>" . h($msg);
        if (count($links) > 0) {
            echo "<div class='vb-errore-azioni'>";
            foreach ($links as $label => $href) {
                echo "<a class='btn' href='" . h($href) . "'>" . h($label) . "</a>";
            }
            echo "</div>";
        }
        echo "</div></div></td></tr>";
        require_once 'config/footer.php';
        exit;
    }
}

// Stampa i campi indicati ($spec: chiave => etichetta, obbligatorio, id, lunghezza max, classi, attributi,
// larghezza in colonne su 12). La griglia che li contiene è nella pagina.
function id_campi($chiavi, $spec, $dati) {
    foreach ($chiavi as $k) {
        list($label, $req, $id, $max, $cls, $attr, $col) = $spec[$k];
        echo "<label class='fm-field id-c$col'><span" . ($req ? " class='fm-req'" : "") . ">" . h($label) . "</span>"
            . "<input type='text' autocomplete='off' id='$id' name='$k' maxlength='$max'"
            . ($cls !== '' ? " class='$cls'" : "") . " $attr value=\"" . h($dati[$k]) . "\"></label>";
     }
}

echo "<tr><td>";

// ---------------------------------------------------------------------------
// Parametri
// ---------------------------------------------------------------------------
$idrinvenimento  = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$tipopersona_req = intval(isset($_REQUEST['tipopersona']) ? $_REQUEST['tipopersona'] : 0);
$edit_own  = (isset($_REQUEST['edit_own'])  && !is_array($_REQUEST['edit_own']))  ? trim((string)$_REQUEST['edit_own'])  : '';
$edit_rest = (isset($_REQUEST['edit_rest']) && !is_array($_REQUEST['edit_rest'])) ? trim((string)$_REQUEST['edit_rest']) : '';
$own  = ($edit_own  !== '' && $edit_own  !== '0' && strcasecmp($edit_own,  'false') !== 0);
$rest = ($edit_rest !== '' && $edit_rest !== '0' && strcasecmp($edit_rest, 'false') !== 0);

$url_dettagli = 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento;

// Tipi di persona: titolo, colonna di rinvenimenti con l'id, valore del checkbox "radio" e testi
$tipi = array(
    1 => array('titolo' => 'Identificazione del consegnante', 'campo' => 'idconsegnante',
               'valore' => 'sconosciuto', 'opzione' => 'Sconosciuto o Operatore',
               'aiuto'  => 'Selezionalo se il consegnante non è identificabile o se si tratta di un operatore: non verranno richiesti i dati anagrafici.'),
    2 => array('titolo' => 'Identificazione del proprietario', 'campo' => 'idproprietario',
               'valore' => 'sconosciuto', 'opzione' => 'Sconosciuto',
               'aiuto'  => 'Selezionalo se il proprietario non è identificabile: non verranno richiesti i dati anagrafici.'),
    3 => array('titolo' => 'Identificazione del ritirante', 'campo' => 'idritirante',
               'valore' => 'proprietario', 'opzione' => 'Il ritirante è il proprietario',
               'aiuto'  => ''),
);
if (!isset($tipi[$tipopersona_req])) {
    vr_errore('Tipo di persona non valido.', array('Lista rinvenimenti' => 'oggetti_rinvenuti_list.php'));
}
$tipo = $tipi[$tipopersona_req];

// ---------------------------------------------------------------------------
// Dati del rinvenimento e della persona da identificare
// ---------------------------------------------------------------------------
$res  = mysqli_query($conn, "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $res ? mysqli_fetch_assoc($res) : null;
if (!$rinv) {
    vr_errore('Rinvenimento non trovato.', array('Lista rinvenimenti' => 'oggetti_rinvenuti_list.php'));
}
$idpersona = intval($rinv[$tipo['campo']]);

$campi_db = array('cognome', 'nome', 'datanascita', 'luogonascita', 'residenza', 'via',
                  'documento', 'documentonumero', 'rilasciatoda', 'rilasciatoil', 'telefono');
$dati = array_fill_keys($campi_db, '');
if ($idpersona > 0) {
    $res = mysqli_query($conn, "SELECT " . implode(', ', $campi_db) . " FROM oggetti2.persone WHERE idpersona=$idpersona");
    if ($res && ($p = mysqli_fetch_assoc($res))) {
        foreach ($campi_db as $k) {
            $v = (string)$p[$k];
            if ($k === 'datanascita' || $k === 'rilasciatoil') {
                $v = convertiDataAE($v);                      // date in formato utente
                if ($v === '00/00/0000') { $v = ''; }
            }
            $dati[$k] = $v;
        }
    }
}

// Ritirante: serve il proprietario, se già identificato (per la copia dei dati)
$prop = null;
if ($tipopersona_req === 3) {
    $idprop = intval($rinv['idproprietario']);
    if ($idprop > 0) {
        $res = mysqli_query($conn, "SELECT cognome, nome FROM oggetti2.persone WHERE idpersona=$idprop");
        $pp  = $res ? mysqli_fetch_assoc($res) : null;
        if ($pp && trim((string)$pp['cognome']) !== '') {
            $prop = array(
                'cognome' => html_entity_decode((string)$pp['cognome'], ENT_QUOTES, 'UTF-8'),
                'nome'    => html_entity_decode((string)$pp['nome'], ENT_QUOTES, 'UTF-8'),
            );
        }
    }
}

// Descrizione dell'opzione "scorciatoia" (checkbox)
$opzione_html = h($tipo['opzione']);
$aiuto        = $tipo['aiuto'];
$opzione_off  = false;
if ($tipopersona_req === 3) {
    if ($prop) {
        $opzione_html .= ' <b>(' . h($prop['cognome'] . ' ' . $prop['nome']) . ')</b>';
        $aiuto = 'I dati del proprietario verranno copiati sul ritirante: potrai controllarli prima di continuare.';
    } else {
        $opzione_off = true;
        $aiuto = 'Il proprietario non è ancora stato identificato: non è possibile copiarne i dati.';
    }
}

// Contesto della modifica
$contesto = '';
if ($own)  { $contesto = 'Modifica del solo proprietario'; }
if ($rest) { $contesto = 'Modifica per il verbale di restituzione'; }
$testo_avanti = $own ? 'Salva' : 'Avanti';

// Form di invio (stessi parametri di prima: jax=0 non va modificato)
$action = 'identificazione_submit.php?tipopersona=' . $tipopersona_req . '&amp;idrinvenimento=' . $idrinvenimento . '&amp;jax=0';

// Definizione dei campi: etichetta, obbligatorio, id, lunghezza massima, classi, attributi
$spec = array(
    'cognome'         => array('Cognome',           true,  'Cognome',      45, '',          '', 3),
    'nome'            => array('Nome',              true,  'Nome',         45, '',          '', 3),
    'datanascita'     => array('Data di nascita',   false, 'datanascita',  10, 'data_mask', "placeholder='gg/mm/aaaa' inputmode='numeric'", 2),
    'luogonascita'    => array('Luogo di nascita',  false, 'luogonascita', 45, 'luogo',     '', 4),
    'residenza'       => array('Residenza',         false, 'residenza',    60, 'luogo',     '', 4),
    'via'             => array('Via',               false, 'via',          60, 'luogo',     '', 5),
    'telefono'        => array('Telefono',          false, 'tel',          45, '',          "inputmode='tel'", 3),
    'documento'       => array('Documento',         false, 'doc',          45, '',          "list='elenco-documenti'", 3),
    'documentonumero' => array('Numero/Matricola',  false, 'docnro',       45, '',          '', 3),
    'rilasciatoda'    => array('Rilasciato da',     false, 'rilasciatoda', 45, 'luogo',     '', 4),
    'rilasciatoil'    => array('Rilasciato il',     false, 'rilasciatoil', 10, 'data_mask', "placeholder='gg/mm/aaaa' inputmode='numeric'", 2),
);

$cfg = array(
    'id'       => $idrinvenimento,
    'tipo'     => $tipopersona_req,
    'prop'     => $prop,
    'urlAjax'  => 'identificazione_submit.php?idrinvenimento=' . $idrinvenimento
                  . '&tipopersona=' . $tipopersona_req . '&ajax=1',
    'avanti'   => $testo_avanti,
);
?>

<div class="vb vb-wide">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2><?php echo h($tipo['titolo']); ?> - rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo trim((string)$rinv['brevedescrizioneoggetto']) !== '' ? h($rinv['brevedescrizioneoggetto']) : 'Nuovo rinvenimento'; ?></span>
            <?php if ($contesto !== '') { echo '<span class="vb-sub">' . h($contesto) . '</span>'; } ?>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">&larr; Dettagli rinvenimento</a>
        </div>
    </div>

    <div id="fm-ok" class="fm-ok" style="display:none"></div>

    <form id="form" name="datapost" method="post" novalidate autocomplete="off" action="<?php echo $action; ?>">
        <input type="hidden" name="edit_own"  value="<?php echo h($edit_own); ?>">
        <input type="hidden" name="edit_rest" value="<?php echo h($edit_rest); ?>">

        <!-- Scorciatoia: sconosciuto / operatore / proprietario -->
        <div class="rv-card id-top">
            <label class="id-opt<?php echo $opzione_off ? ' disabilitato' : ''; ?>">
                <input type="checkbox" id="chk-speciale" name="radio" value="<?php echo h($tipo['valore']); ?>"<?php echo $opzione_off ? ' disabled' : ''; ?>>
                <span><?php echo $opzione_html; ?></span>
            </label>
            <?php if ($aiuto !== '') { echo '<span class="fm-help">' . h($aiuto) . '</span>'; } ?>
        </div>

        <div id="dati" class="rv-card">
            <div class="id-form">
                <div class="id-sez">Dati anagrafici</div>
                <?php id_campi(array('cognome', 'nome', 'datanascita', 'luogonascita', 'residenza', 'via', 'telefono'), $spec, $dati); ?>
                <div class="id-sez">Documento di riconoscimento</div>
                <?php id_campi(array('documento', 'documentonumero', 'rilasciatoda', 'rilasciatoil'), $spec, $dati); ?>
            </div>
        </div>

        <datalist id="elenco-documenti">
            <option value="Carta d'identità">
            <option value="Patente di guida">
            <option value="Passaporto">
            <option value="Permesso di soggiorno">
            <option value="Tessera sanitaria">
        </datalist>

        <div id="fm-errori" class="fm-msg-err" style="display:none"></div>

        <div class="rv-actionbar">
            <div class="rv-actions fm-actions">
                <?php if ($own) { ?>
                    <span class="fm-help fm-nota">Dopo il salvataggio si torna al dettaglio del rinvenimento.</span>
                <?php } ?>
                <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">Annulla</a>
                <button type="submit" id="avanti"><?php echo h($testo_avanti); ?></button>
            </div>
        </div>
    </form>
</div>

<div id="dialog-form" title="Sostituire i dati del ritirante?" style="display:none">
    <div class="fm-dialog">
        <p id="dialog-testo"></p>
        <p>Vuoi continuare?</p>
    </div>
</div>

<script src="js/input_format.js"></script>
<script>
var cfg = <?php echo json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

$(document).ready(function() {
    var inviato = false, confermato = false;
    var $err = $('#fm-errori');

    // Formattazione dei campi (invariata rispetto a prima)
    if ($.fn.upperFirstAll) { $('#Nome').upperFirstAll(); $('#doc').upperFirstAll(); }
    if ($.fn.upperCase)     { $('#Cognome').upperCase();  $('#docnro').upperCase(); }
    if ($.fn.onlyPhone)       { $('#tel').onlyPhone(); }        // accetta anche il + dei prefissi
    else if ($.fn.onlyNumber) { $('#tel').onlyNumber(); }

    // Maschera data gg/mm/aaaa
    $('.data_mask').on('input', function() {
        var v = $(this).val().replace(/\D/g, '').substring(0, 8);
        if (v.length > 4)      { v = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4); }
        else if (v.length > 2) { v = v.slice(0, 2) + '/' + v.slice(2); }
        $(this).val(v);
    });

    function dataValida(s) {
        if (s === '') { return true; }
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s);
        if (!m) { return false; }
        var g = +m[1], me = +m[2], a = +m[3], d = new Date(a, me - 1, g);
        if (d.getFullYear() !== a || d.getMonth() !== me - 1 || d.getDate() !== g) { return false; }
        return a >= 1900 && d <= new Date();
    }

    function errore(msg) {
        $err.empty().append($('<div></div>').text(msg)).show();
        $('html, body').animate({scrollTop: $err.offset().top - 20}, 200);
    }

    // --- Ritirante = proprietario: copia dei dati tramite il submit (come prima) -----------
    function copia() {
        var dati = $('#form').serialize();            // prima di disabilitare: i campi disabilitati non vengono inviati
        $('#chk-speciale, #avanti').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: cfg.urlAjax,
            data: dati,
            success: function() {
                try { sessionStorage.setItem('idCopiato', String(cfg.id)); } catch (e) {}
                location.reload(true);
            },
            error: function() {
                $('#chk-speciale').prop('disabled', false).prop('checked', false);
                $('#avanti').prop('disabled', false);
                errore('Copia dei dati del proprietario non riuscita. Riprova oppure inserisci i dati a mano.');
            }
        });
    }

    $('#dialog-form').dialog({
        autoOpen: false, width: 460, modal: true, resizable: false, dialogClass: 'cl-dialog',
        buttons: {
            "Sì, sostituisci": function() { confermato = true; $(this).dialog('close'); copia(); },
            "Annulla":         function() { $(this).dialog('close'); }
        },
        close: function() {
            if (!confermato) { $('#chk-speciale').prop('checked', false); }
            confermato = false;
        }
    });

    $('#chk-speciale').on('change', function() {
        var on = $(this).is(':checked');
        $('.fm-err').removeClass('fm-err');
        $err.hide();

        if (cfg.tipo === 3) {
            if (!on || !cfg.prop) { return; }
            var c = $.trim($('#Cognome').val()), n = $.trim($('#Nome').val());
            var diverso = c !== '' && (c.toLowerCase() !== cfg.prop.cognome.toLowerCase() ||
                                       n.toLowerCase() !== cfg.prop.nome.toLowerCase());
            if (diverso) {
                $('#dialog-testo').text('I dati del ritirante attuale (' + c + ' ' + n +
                                        ') verranno sostituiti con quelli del proprietario.');
                $('#dialog-form').dialog('open');
            } else {
                copia();
            }
        } else {
            // sconosciuto / operatore: i dati anagrafici non servono
            $('#dati').toggle(!on);
        }
    });

    // Dopo la copia (ricaricamento della pagina) conferma all'utente
    try {
        if (sessionStorage.getItem('idCopiato') === String(cfg.id)) {
            sessionStorage.removeItem('idCopiato');
            if ($.trim($('#Cognome').val()) !== '') {
                $('#fm-ok').text('I dati del proprietario sono stati copiati sul ritirante. Controllali e premi «' + cfg.avanti + '».').show();
            } else {
                errore('La copia non sembra riuscita: inserisci i dati del ritirante a mano.');
            }
        }
    } catch (e) {}

    // --- Comportamento del modulo -----------------------------------------------------------
    // Invio passa al campo successivo (non invia il modulo a metà compilazione)
    $('#form').on('keydown', 'input[type=text]', function(e) {
        if (e.which !== 13) { return; }
        e.preventDefault();
        var lista = $('#form input[type=text]:visible');
        var i = lista.index(this);
        if (i > -1 && i < lista.length - 1) { lista.eq(i + 1).focus(); } else { $('#avanti').focus(); }
    });
    $('#form').on('input', 'input', function() { $(this).removeClass('fm-err'); });

    function valida() {
        var msgs = [];
        $('.fm-err').removeClass('fm-err');
        // con "sconosciuto" i dati non servono
        if ($('#chk-speciale').is(':checked')) { $err.hide(); return true; }

        if ($.trim($('#Cognome').val()) === '') { $('#Cognome').addClass('fm-err'); msgs.push('Il cognome è obbligatorio.'); }
        if ($.trim($('#Nome').val()) === '')    { $('#Nome').addClass('fm-err');    msgs.push('Il nome è obbligatorio.'); }
        if (!dataValida($.trim($('#datanascita').val()))) {
            $('#datanascita').addClass('fm-err');
            msgs.push('Data di nascita non valida (formato gg/mm/aaaa, non nel futuro).');
        }
        if (!dataValida($.trim($('#rilasciatoil').val()))) {
            $('#rilasciatoil').addClass('fm-err');
            msgs.push('Data di rilascio non valida (formato gg/mm/aaaa, non nel futuro).');
        }

        $err.empty();
        if (msgs.length === 0) { $err.hide(); return true; }
        $.each(msgs, function(i, m) { $err.append($('<div></div>').text(m)); });
        $err.show();
        $('html, body').animate({scrollTop: $err.offset().top - 20}, 200);
        return false;
    }

    $('#form').on('submit', function(e) {
        if (inviato)   { e.preventDefault(); return; }    // protegge dal doppio clic
        if (!valida()) { e.preventDefault(); return; }
        inviato = true;
        $('#avanti').prop('disabled', true).text('Salvataggio…');
    });

    $('#Cognome').focus();
});
</script>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>