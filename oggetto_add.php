<?php
// oggetto_add.php
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

echo "<tr><td>";

// ---------------------------------------------------------------------------
// Dati del rinvenimento (se già presenti: modifica)
// ---------------------------------------------------------------------------
$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);

$res  = mysqli_query($conn, "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $res ? mysqli_fetch_assoc($res) : null;
if (!$rinv) {
    vr_errore('Rinvenimento non trovato.', array('Lista rinvenimenti' => 'oggetti_rinvenuti_list.php'));
}

// "luogo - luogo specifico": si divide solo al primo trattino, il resto resta nel luogo specifico
$parti           = explode('-', (string)$rinv['luogorinvenimento'], 2);
$luogo_rinv      = trim($parti[0]);
$luogo_rinv_spec = isset($parti[1]) ? trim($parti[1]) : '';

$data_rinv = convertiDataAE((string)$rinv['datarinvenimento']);
if ($data_rinv === '' || $data_rinv === '00/00/0000') {
    $data_rinv = date('d/m/Y');
}

$brevedescrizioneoggetto = (string)$rinv['brevedescrizioneoggetto'];
$note_rinv               = (string)$rinv['note_rinv'];

$in_modifica    = (trim($brevedescrizioneoggetto) !== '');
$url_indietro   = $in_modifica ? 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento : 'oggetti_rinvenuti_list.php';
$testo_indietro = $in_modifica ? 'Dettagli rinvenimento' : 'Lista rinvenimenti';

// Tipi di oggetto (una sola query)
$opzioni = '<option value="">Seleziona…</option>';
$res = mysqli_query($conn, "SELECT idtipioggetti, tipooggetto FROM oggetti2.tipioggetti ORDER BY ordine ASC");
if ($res) {
    while ($t = mysqli_fetch_assoc($res)) {
        $opzioni .= '<option value="' . intval($t['idtipioggetti']) . '">' . h($t['tipooggetto']) . '</option>';
    }
}

// Oggetti già registrati (il campo tipooggetto della tabella contiene l'id del tipo)
$oggetti = array();
$res = mysqli_query($conn, "SELECT tipooggetto, descrizioneoggetto FROM oggetti2.listaoggetti
                            WHERE idrinvenimento=$idrinvenimento ORDER BY idlistaoggetti");
if ($res) {
    while ($o = mysqli_fetch_assoc($res)) {
        $oggetti[] = array(
            'tipo'  => intval($o['tipooggetto']),
            'descr' => html_entity_decode((string)$o['descrizioneoggetto'], ENT_QUOTES, 'UTF-8'),
        );
    }
}

$cfg = array(
    'id'            => $idrinvenimento,
    'opzioni'       => $opzioni,
    'oggetti'       => $oggetti,
    'tipiDocumento' => array(1, 2, 3, 4, 26),   // tipi per cui serve il promemoria SDI
);
?>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Rinvenimento nr. <?php echo $idrinvenimento; ?> - dati e oggetti rinvenuti</h2>
            <span class="vb-sub"><?php echo $in_modifica ? h($brevedescrizioneoggetto) : 'Nuovo rinvenimento'; ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="<?php echo h($url_indietro); ?>">&larr; <?php echo h($testo_indietro); ?></a>
        </div>
    </div>

    <form id="form" name="datapost" method="post" novalidate
          action="oggetto_add_submit.php?idrinvenimento=<?php echo $idrinvenimento; ?>">
        <input id="hidden" type="hidden" name="maxidoggetto" value="0">

        <!-- Dati del rinvenimento -->
        <div class="rv-card">
            <h3>Dati del rinvenimento</h3>
            <div class="rn-grid">
                <label class="fm-field"><span class="fm-req">Luogo del rinvenimento</span>
                    <input type="text" id="luogo" name="luogorinvenimento" maxlength="60" autocomplete="off"
                           value="<?php echo h($luogo_rinv); ?>">
                </label>
                <label class="fm-field"><span>Luogo specifico</span>
                    <input type="text" name="luogospecifico" maxlength="57" autocomplete="off"
                           value="<?php echo h($luogo_rinv_spec); ?>">
                    <span class="fm-help">Precisa il punto (binario, convoglio, zona…).</span>
                </label>
                <label class="fm-field"><span class="fm-req">Data del rinvenimento</span>
                    <input type="text" id="datepicker" name="datarinvenimento" maxlength="10" inputmode="numeric"
                           autocomplete="off" placeholder="gg/mm/aaaa" value="<?php echo h($data_rinv); ?>">
                </label>
                <label class="fm-field fm-full"><span class="fm-req">Breve descrizione oggetti</span>
                    <input type="text" id="breve" name="brevedescrizioneoggetto" maxlength="60" autocomplete="off"
                           value="<?php echo h($brevedescrizioneoggetto); ?>">
                    <span class="fm-help">Una descrizione BREVE ma esplicativa del rinvenimento (es. «Zaino nero con portafoglio»).</span>
                    <span id="breve-conta" class="nt-conta"></span>
                </label>
            </div>
        </div>

        <!-- Oggetti rinvenuti -->
        <div class="rv-card">
            <h3>Oggetti rinvenuti</h3>

            <div id="sdi-info" class="fm-info" style="display:none">
                <b>Documenti: informazioni da inserire</b><br>
                Si prega di inserire nella descrizione: il numero del documento, il luogo di rilascio, la data di rilascio
                e il proprietario se è diverso dal proprietario del rinvenimento. Tali informazioni sono necessarie per
                l'implementazione della banca dati SDI.
            </div>

            <table class="fm-table og-table">
                <thead>
                    <tr><th style="width:36px">#</th><th style="width:28%">Tipo oggetto</th><th>Descrizione</th><th style="width:60px"></th></tr>
                </thead>
                <tbody id="og-body"></tbody>
            </table>
            <button type="button" id="og-add">+ Aggiungi oggetto</button>
        </div>

        <!-- Note -->
        <div class="rv-card">
            <h3>Note</h3>
            <label class="fm-field">
                <input type="text" id="note" name="note_rinv" maxlength="200" autocomplete="off"
                       placeholder="Facoltative" value="<?php echo h($note_rinv); ?>">
                <span id="note-conta" class="nt-conta"></span>
            </label>
        </div>

        <div id="fm-errori" class="fm-msg-err" style="display:none"></div>

        <div class="rv-actionbar">
            <div class="rv-actions fm-actions">
                <span class="fm-help fm-nota">Dopo il salvataggio viene generato il verbale di rinvenimento.</span>
                <a class="btn btn-ghost" href="<?php echo h($url_indietro); ?>">Annulla</a>
                <button id="button" type="submit">Genera verbale di rinvenimento</button>
            </div>
        </div>
    </form>
</div>

<script>
var cfg = <?php echo json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

$(document).ready(function() {
    var inviato = false, modificato = false;
    var $err  = $('#fm-errori');
    var $body = $('#og-body');

    // --- Data: maschera gg/mm/aaaa + calendario ----------------------------------
    $('#datepicker').datepicker({
        changeMonth: true,
        changeYear: true,
        maxDate: 'today',
        dateFormat: 'dd/mm/yy'
    }).on('input', function() {
        var v = $(this).val().replace(/\D/g, '').substring(0, 8);
        if (v.length > 4)      { v = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4); }
        else if (v.length > 2) { v = v.slice(0, 2) + '/' + v.slice(2); }
        $(this).val(v);
    });

    function dataValida(s) {
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s);
        if (!m) { return false; }
        var g = +m[1], me = +m[2], a = +m[3], d = new Date(a, me - 1, g);
        if (d.getFullYear() !== a || d.getMonth() !== me - 1 || d.getDate() !== g) { return false; }
        return a >= 1900 && d <= new Date();
    }

    // --- Contatori di caratteri -----------------------------------------------------
    function contatore(campo, box, max) {
        var $c = $(campo), $b = $(box);
        function agg() {
            var n = $c.val().length;
            $b.text(n + ' / ' + max + ' caratteri')
              .toggleClass('vicino', n >= max * 0.9 && n < max)
              .toggleClass('pieno', n >= max);
        }
        $c.on('input keyup', agg);
        agg();
    }
    contatore('#breve', '#breve-conta', 60);
    contatore('#note', '#note-conta', 200);

    // --- Oggetti ---------------------------------------------------------------------
    function segnalaDocumenti() {
        var trovato = false;
        $body.find('select').each(function() {
            if ($.inArray(parseInt($(this).val(), 10), cfg.tipiDocumento) > -1) { trovato = true; }
        });
        $('#sdi-info').toggle(trovato);
    }

    function nuovaRiga(tipo, descr) {
        var $sel = $('<select name="tipooggetto[]" class="type_obj"></select>').html(cfg.opzioni);
        if (tipo !== '') { $sel.val(String(tipo)); }
        var $in = $('<input type="text" name="descroggetto[]" maxlength="200" autocomplete="off" placeholder="Descrizione dell\'oggetto">').val(descr);
        var $x  = $('<button type="button" class="og-del" title="Rimuovi questo oggetto">&times;</button>');
        var $r  = $('<tr class="og-row"></tr>')
            .append('<td class="og-n"></td>')
            .append($('<td></td>').append($sel))
            .append($('<td></td>').append($in))
            .append($('<td class="og-x"></td>').append($x));
        $body.append($r);
        return $r;
    }

    $body.on('change', 'select', function() { $(this).removeClass('fm-err'); segnalaDocumenti(); });
    $body.on('input', 'input', function()   { $(this).removeClass('fm-err'); });
    $body.on('click', '.og-del', function() {
        $(this).closest('tr').remove();
        segnalaDocumenti();
        modificato = true;
    });
    $('#og-add').on('click', function() {
        nuovaRiga('', '').find('select').focus();
        modificato = true;
    });

    $.each(cfg.oggetti, function(i, o) { nuovaRiga(o.tipo, o.descr); });
    if (cfg.oggetti.length === 0) { nuovaRiga('', ''); }
    segnalaDocumenti();

    // --- Comportamento del modulo -------------------------------------------------------
    // Invio passa al campo successivo (non genera il verbale per errore)
    $('#form').on('keydown', 'input[type=text]', function(e) {
        if (e.which !== 13) { return; }
        e.preventDefault();
        var lista = $('#form input[type=text]:visible, #form select:visible');
        var i = lista.index(this);
        if (i > -1 && i < lista.length - 1) { lista.eq(i + 1).focus(); } else { $('#button').focus(); }
    });

    $('#form').on('input change', function() { modificato = true; });
    $('#luogo, #datepicker, #breve').on('input', function() { $(this).removeClass('fm-err'); });

    // Avvisa se si lascia la pagina con modifiche non salvate
    $(window).on('beforeunload', function() {
        if (modificato && !inviato) { return 'Ci sono modifiche non salvate.'; }
    });

    // --- Controlli prima dell'invio ---------------------------------------------------------
    function valida() {
        var msgs = [];
        $('.fm-err').removeClass('fm-err');

        // Le righe completamente vuote (se ce n'è più di una) vengono scartate
        $body.find('tr').each(function() {
            var $r = $(this);
            if ($body.find('tr').length > 1 && $r.find('select').val() === '' && $.trim($r.find('input').val()) === '') {
                $r.remove();
            }
        });

        if ($.trim($('#luogo').val()) === '') {
            $('#luogo').addClass('fm-err');
            msgs.push('Indica il luogo del rinvenimento.');
        }
        var d = $.trim($('#datepicker').val());
        if (d === '') {
            $('#datepicker').addClass('fm-err');
            msgs.push('Indica la data del rinvenimento.');
        } else if (!dataValida(d)) {
            $('#datepicker').addClass('fm-err');
            msgs.push('Data del rinvenimento non valida (formato gg/mm/aaaa, non nel futuro).');
        }
        if ($.trim($('#breve').val()) === '') {
            $('#breve').addClass('fm-err');
            msgs.push('Scrivi la breve descrizione del rinvenimento.');
        }
        $body.find('tr').each(function(i) {
            var $s = $(this).find('select'), $t = $(this).find('input');
            if ($s.val() === '')              { $s.addClass('fm-err'); msgs.push('Oggetto ' + (i + 1) + ': scegli il tipo.'); }
            if ($.trim($t.val()) === '')      { $t.addClass('fm-err'); msgs.push('Oggetto ' + (i + 1) + ': scrivi la descrizione.'); }
        });

        $err.empty();
        if (msgs.length === 0) { $err.hide(); return true; }
        $.each(msgs, function(i, m) { $err.append($('<div></div>').text(m)); });
        $err.show();
        $('html, body').animate({scrollTop: $err.offset().top - 20}, 200);
        return false;
    }

    $('#form').on('submit', function(e) {
        if (inviato)   { e.preventDefault(); return; }     // protegge dal doppio clic
        if (!valida()) { e.preventDefault(); return; }
        inviato = true;
        $('#button').prop('disabled', true).text('Salvataggio…');
    });

    if ($.trim($('#luogo').val()) === '')      { $('#luogo').focus(); }
    else if ($.trim($('#breve').val()) === '') { $('#breve').focus(); }
});
</script>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>