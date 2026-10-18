<?php
// identificazione_restituzione.php
require_once 'config/header.php';
require 'config/connect.php';
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

// Dati di una persona collegata al rinvenimento (2 = proprietario, 3 = ritirante); null se assente
function ir_persona($conn, $idrinvenimento, $tipo) {
    $sql = "SELECT cognome, nome, DATE_FORMAT(datanascita, '%d/%m/%Y') AS datanascita, luogonascita,
                   residenza, via, documento, documentonumero, rilasciatoda,
                   DATE_FORMAT(rilasciatoil, '%d/%m/%Y') AS rilasciatoil, telefono
            FROM oggetti2.persone
            WHERE idrinvenimento = " . intval($idrinvenimento) . " AND tipopersona = " . intval($tipo);
    $res = mysqli_query($conn, $sql);
    $p = $res ? mysqli_fetch_assoc($res) : null;
    if (!$p) {
        return null;
    }
    foreach ($p as $k => $v) {
        // decodifica le entità (&#39; scritte da convertiApici): nel campo deve comparire l'apostrofo
        $v = html_entity_decode((string)$v, ENT_QUOTES, 'UTF-8');
        $p[$k] = ($v === '00/00/0000') ? '' : $v;
    }
    return $p;
}

// Campi del modulo: nome => (etichetta, obbligatorio, placeholder, classi, attributi)
$campi = array(
    'cognome'         => array('Cognome',           true,  '',           'cognome',         ''),
    'nome'            => array('Nome',              true,  '',           'nome',            ''),
    'datanascita'     => array('Data di nascita',   false, 'gg/mm/aaaa', 'data_mask',       "maxlength='10' inputmode='numeric'"),
    'luogonascita'    => array('Luogo di nascita',  false, '',           '',                ''),
    'residenza'       => array('Residenza',         false, '',           '',                ''),
    'via'             => array('Via',               false, '',           '',                ''),
    'documento'       => array('Documento',         false, '',           'documento',       "list='elenco-documenti'"),
    'documentonumero' => array('Numero documento',  false, '',           'documentonumero', ''),
    'rilasciatoda'    => array('Rilasciato da',     false, '',           '',                ''),
    'rilasciatoil'    => array('Rilasciato il',     false, 'gg/mm/aaaa', 'data_mask',       "maxlength='10' inputmode='numeric'"),
    'telefono'        => array('Telefono',          false, '',           'telefono',        "inputmode='tel'"),
);

// Stampa i campi di una persona ($pfx = 'proprietario' | 'ritirante')
function ir_campi($pfx, $campi) {
    echo "<div class='fm-grid'>";
    foreach ($campi as $nome => $d) {
        list($label, $req, $ph, $cls, $attr) = $d;
        echo "<label class='fm-field'><span" . ($req ? " class='fm-req'" : "") . ">" . h($label) . "</span>"
           . "<input type='text' autocomplete='off' name='$nome' id='{$pfx}_{$nome}' class='input-$pfx $cls'"
           . ($ph !== '' ? " placeholder='" . h($ph) . "'" : "") . " $attr></label>";
    }
    echo "</div>";
}

echo "<tr><td>";

// ---------------------------------------------------------------------------
// Dati del rinvenimento e delle persone già registrate
// ---------------------------------------------------------------------------
$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$url_dettagli   = 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento;

$res  = mysqli_query($conn, "SELECT brevedescrizioneoggetto FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $res ? mysqli_fetch_assoc($res) : null;
if (!$rinv) {
    vr_errore('Rinvenimento non trovato.');
}

$cfg = array(
    'id'       => $idrinvenimento,
    'prossimo' => 'verbale_restituzione_init.php?idrinvenimento=' . $idrinvenimento,
    'campi'    => array_keys($campi),
    'prop'     => ir_persona($conn, $idrinvenimento, 2),
    'rit'      => ir_persona($conn, $idrinvenimento, 3),
);
?>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Identificazione - rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo h($rinv['brevedescrizioneoggetto']); ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">&larr; Dettagli rinvenimento</a>
        </div>
    </div>

    <form id="form" novalidate autocomplete="off">

        <div class="rv-card">
            <h3>Chi ritira gli oggetti?</h3>
            <div class="fm-toggle">
                <input type="radio" name="ritiro" id="rit-stesso" value="stesso"><label for="rit-stesso">Il ritirante è il proprietario</label>
                <input type="radio" name="ritiro" id="rit-delega" value="delega"><label for="rit-delega">Ritiro con delega (altra persona)</label>
            </div>
            <p class="fm-help" style="margin-top:8px">Il verbale riporterà «quale legittimo proprietario» oppure «quale delegato al ritiro».</p>
        </div>

        <div class="id-grid" id="id-grid">
            <div class="rv-card" id="card-prop">
                <div class="id-head"><h3 id="titolo-prop">Dati del proprietario</h3></div>
                <?php ir_campi('proprietario', $campi); ?>
            </div>

            <div class="rv-card" id="card-rit" style="display:none">
                <div class="id-head">
                    <h3>Dati del ritirante</h3>
                    <button type="button" id="copiaDati" class="fm-sm">Copia dati del proprietario</button>
                </div>
                <?php ir_campi('ritirante', $campi); ?>
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
                <span class="fm-help fm-nota">I dati vengono salvati e si passa ai dati della restituzione.</span>
                <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">Annulla</a>
                <button type="button" id="avanti">Salva e continua</button>
            </div>
        </div>
    </form>
</div>

<div id="dialog-form" title="Documento non indicato" style="display:none">
    <div class="fm-dialog">
        <p>Per il ritirante non sono stati indicati il tipo o il numero del documento.</p>
        <p>Vuoi continuare comunque?</p>
    </div>
</div>

<script src="js/input_format.js"></script>
<script>
var cfg = <?php echo json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

$(document).ready(function() {
    var campi = cfg.campi;
    var inviato = false;

    // Formattazione dei campi (come prima: cognome maiuscolo, nome con iniziale maiuscola)
    if ($.fn.upperCase)     { $('.cognome').upperCase(); }
    if ($.fn.upperFirstAll) { $('.nome').upperFirstAll(); }
    // Numero del documento in maiuscolo; telefono: solo cifre e simboli di un numero (anche con +39)
    if ($.fn.upperCase)     { $('.documentonumero').upperCase(); }
    if ($.fn.onlyPhone)     { $('.telefono').onlyPhone(); }

    // Maschera data gg/mm/aaaa
    $('.data_mask').on('input', function() {
        var v = $(this).val().replace(/\D/g, '').substring(0, 8);
        if (v.length > 4)      { v = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4); }
        else if (v.length > 2) { v = v.slice(0, 2) + '/' + v.slice(2); }
        $(this).val(v);
    });

    // --- Lettura e scrittura dei campi ------------------------------------
    function val(pfx, c)  { return $.trim($('#' + pfx + '_' + c).val()); }
    function leggi(pfx)   { var o = {}; $.each(campi, function(i, c) { o[c] = val(pfx, c); }); return o; }
    function scrivi(pfx, o) {
        $.each(campi, function(i, c) { $('#' + pfx + '_' + c).val(o && o[c] != null ? o[c] : ''); });
    }
    function uguali(a, b) {
        var ok = true;
        $.each(campi, function(i, c) { if ((a[c] || '') !== (b[c] || '')) { ok = false; } });
        return ok;
    }

    // --- Proprietario / ritirante -----------------------------------------
    function mostra(tipo) {
        var stesso = (tipo === 'stesso');
        $('#card-rit').toggle(!stesso);
        $('#id-grid').toggleClass('single', stesso);
        $('#titolo-prop').text(stesso ? 'Dati del proprietario (che ritira)' : 'Dati del proprietario');
    }

    $('input[name=ritiro]').on('change', function() {
        var tipo = $(this).val();
        $('.fm-toggle').removeClass('fm-err');
        $('#fm-errori').hide();
        // Se i dati del ritirante sono solo una copia di quelli del proprietario li svuoto,
        // per non lasciarli per errore su una persona diversa
        if (tipo === 'delega' && uguali(leggi('ritirante'), leggi('proprietario'))) {
            scrivi('ritirante', null);
        }
        mostra(tipo);
    });

    $('#copiaDati').on('click', function() { scrivi('ritirante', leggi('proprietario')); });

    // Dati già presenti nel database
    if (cfg.prop) { scrivi('proprietario', cfg.prop); }
    if (cfg.rit)  { scrivi('ritirante', cfg.rit); }
    if (cfg.rit && cfg.rit.cognome) {
        var tipoIniziale = uguali(leggi('proprietario'), leggi('ritirante')) ? 'stesso' : 'delega';
        $('#rit-' + tipoIniziale).prop('checked', true);
        mostra(tipoIniziale);
    }

    // Enter passa al campo successivo (non invia nulla per errore)
    $('#form').on('keydown', 'input[type=text]', function(e) {
        if (e.which !== 13) { return; }
        e.preventDefault();
        var lista = $('#form input[type=text]:visible');
        var i = lista.index(this);
        if (i > -1 && i < lista.length - 1) { lista.eq(i + 1).focus(); } else { $('#avanti').focus(); }
    });
    $('#form').on('input', 'input', function() { $(this).removeClass('fm-err'); });

    // --- Controlli ----------------------------------------------------------
    function dataValida(s) {
        if (s === '') { return true; }
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s);
        if (!m) { return false; }
        var g = +m[1], me = +m[2], a = +m[3], d = new Date(a, me - 1, g);
        if (d.getFullYear() !== a || d.getMonth() !== me - 1 || d.getDate() !== g) { return false; }
        return a >= 1900 && d <= new Date();
    }

    function valida() {
        var msgs = [];
        $('.fm-err').removeClass('fm-err');
        var tipo = $('input[name=ritiro]:checked').val();

        if (!tipo) {
            $('.fm-toggle').addClass('fm-err');
            msgs.push('Indica se il ritirante è il proprietario o se il ritiro avviene con delega.');
        }
        var persone = [['proprietario', 'Proprietario']];
        if (tipo === 'delega') { persone.push(['ritirante', 'Ritirante']); }

        $.each(persone, function(i, p) {
            var pfx = p[0], et = p[1];
            $.each([['cognome', 'cognome'], ['nome', 'nome']], function(j, c) {
                if (val(pfx, c[0]) === '') {
                    $('#' + pfx + '_' + c[0]).addClass('fm-err');
                    msgs.push(et + ': ' + c[1] + ' mancante.');
                }
            });
            $.each([['datanascita', 'data di nascita'], ['rilasciatoil', 'data di rilascio']], function(j, c) {
                if (!dataValida(val(pfx, c[0]))) {
                    $('#' + pfx + '_' + c[0]).addClass('fm-err');
                    msgs.push(et + ': ' + c[1] + ' non valida (formato gg/mm/aaaa, non nel futuro).');
                }
            });
        });

        var $box = $('#fm-errori').empty();
        if (msgs.length === 0) { $box.hide(); return true; }
        $.each(msgs, function(i, m) { $box.append($('<div></div>').text(m)); });
        $box.show();
        $('html, body').animate({scrollTop: $box.offset().top - 20}, 200);
        return false;
    }

    // --- Salvataggio ----------------------------------------------------------
    function post(tipo, dati) {
        return $.ajax({
            type: 'POST',
            url: 'identificazione_restituzione_submit.php',
            dataType: 'text',
            data: $.extend({idrinvenimento: cfg.id, tipopersona: tipo}, dati)
        });
    }
    function esito(t) { try { return $.parseJSON(t); } catch (e) { return null; } }

    function errore(msg) {
        inviato = false;
        $('#avanti').prop('disabled', false).text('Salva e continua');
        $('#fm-errori').empty().append($('<div></div>').text(msg)).show();
        $('html, body').animate({scrollTop: $('#fm-errori').offset().top - 20}, 200);
    }

    function invia() {
        if (inviato) { return; }                       // protegge dal doppio clic
        inviato = true;
        $('#avanti').prop('disabled', true).text('Salvataggio…');

        var tipo = $('input[name=ritiro]:checked').val();
        var prop = leggi('proprietario');
        var rit  = (tipo === 'stesso') ? prop : leggi('ritirante');

        post(2, prop).done(function(t1) {
            var r1 = esito(t1);
            if (!r1 || !r1.success) {
                errore((r1 && (r1.error || r1.message)) || 'Risposta non valida dal server (dati del proprietario).');
                return;
            }
            post(3, rit).done(function(t2) {
                var r2 = esito(t2);
                if (r2 && r2.success) {
                    $('#avanti').text('Dati salvati…');
                    window.location.href = cfg.prossimo;
                } else {
                    errore((r2 && (r2.error || r2.message)) || 'Salvataggio dei dati del ritirante non riuscito.');
                }
            }).fail(function() { errore('Errore di comunicazione con il server (dati del ritirante).'); });
        }).fail(function() { errore('Errore di comunicazione con il server (dati del proprietario).'); });
    }

    // Conferma se manca il documento del ritirante
    $('#dialog-form').dialog({
        autoOpen: false, width: 440, modal: true, resizable: false, dialogClass: 'cl-dialog',
        buttons: {
            "Continua": function() { $(this).dialog('close'); invia(); },
            "Indietro": function() {
                $(this).dialog('close');
                $('#' + ($('input[name=ritiro]:checked').val() === 'stesso' ? 'proprietario' : 'ritirante') + '_documento').focus();
            }
        }
    });

    $('#avanti').on('click', function() {
        if (!valida()) { return; }
        var tipo = $('input[name=ritiro]:checked').val();
        var r = leggi(tipo === 'stesso' ? 'proprietario' : 'ritirante');
        if (r.documento === '' || r.documentonumero === '') {
            $('#dialog-form').dialog('open');
        } else {
            invia();
        }
    });

    $('#proprietario_cognome').focus();
});
</script>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>