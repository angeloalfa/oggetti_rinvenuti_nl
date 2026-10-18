<?php
// verbale_restituzione_init.php
require_once 'config/header.php';
require 'config/connect.php';   // connessione al database
require_once 'functions/function.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Errore dentro la pagina, con eventuali link di uscita (etichetta => indirizzo)
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

echo "<tr><td>";

// ---------------------------------------------------------------------------
// Dati del rinvenimento
// ---------------------------------------------------------------------------
$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$url_dettagli   = 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento;

$res  = mysqli_query($conn, "SELECT brevedescrizioneoggetto, statorinvenimento, idritirante
                             FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $res ? mysqli_fetch_assoc($res) : null;
if (!$rinv) {
    vr_errore('Rinvenimento non trovato.');
}

// Ritirante (già identificato nel passo precedente)
$ritirante = '';
if (!empty($rinv['idritirante'])) {
    $res = mysqli_query($conn, "SELECT cognome, nome FROM oggetti2.persone WHERE idpersona=" . intval($rinv['idritirante']));
    $p = $res ? mysqli_fetch_assoc($res) : null;
    if ($p) {
        $ritirante = trim($p['cognome'] . ' ' . $p['nome']);
    }
}
if ($ritirante === '') {
    vr_errore("Il ritirante non risulta ancora identificato.",
        array('Identifica il ritirante' => 'identificazione_restituzione.php?idrinvenimento=' . $idrinvenimento));
}

// Oggetti in carico (gli stessi che finiranno nel verbale)
$oggetti = array();
$n_doc   = 0;
$res = mysqli_query($conn, "SELECT idlistaoggetti, tipooggetto, descrizioneoggetto, documenti
                            FROM oggetti2.listaoggettiplus
                            WHERE idrinvenimento=$idrinvenimento AND idstatooggetto<4");
if ($res) {
    while ($o = mysqli_fetch_assoc($res)) {
        $oggetti[] = $o;
        if ($o['documenti'] == 1) { $n_doc++; }
    }
}
if (count($oggetti) === 0) {
    vr_errore("Non ci sono oggetti in carico da restituire per questo rinvenimento.",
        array('Dettagli rinvenimento' => $url_dettagli));
}

// Codice monouso: impedisce di ripetere l'operazione con un refresh o un doppio invio
if (!isset($_SESSION['vr_token']) || !is_array($_SESSION['vr_token'])) {
    $_SESSION['vr_token'] = array();
}
$token = bin2hex(random_bytes(16));
$_SESSION['vr_token'][$idrinvenimento] = $token;
?>

<script>
$(document).ready(function() {
    var inviato = false;

    // Calendario: solo date passate, scelta di mese e anno
    $('#datepicker').attr('readonly', 'readonly').datepicker({
        changeMonth: true,
        changeYear: true,
        maxDate: 'today',
        dateFormat: 'dd/mm/yy'
    });

    // Mostra o nasconde le sezioni in base alle scelte Sì/No
    function aggiorna() {
        $('#denuncia-campi').toggle($('input[name=denuncia]:checked').val() === '1');
        var dup = $('input[name=duplicati]:checked').val() === '1';
        $('#duplicati-lista').toggle(dup);
        if (!dup) {
            // le righe nascoste verrebbero comunque inviate: le deseleziono
            $('#duplicati-lista input[type=checkbox]').prop('checked', false);
        }
    }
    $('input[name=denuncia], input[name=duplicati]').on('change', aggiorna);
    aggiorna();

    // Un clic sulla riga del documento spunta il checkbox
    $('#duplicati-lista tbody tr').css('cursor', 'pointer').on('click', function(e) {
        if ($(e.target).is('input')) return;
        var cb = $(this).find('input[type=checkbox]');
        cb.prop('checked', !cb.prop('checked'));
    });

    // Controlli prima dell'invio
    function valida() {
        var msgs = [];
        $('.fm-err').removeClass('fm-err');

        if ($('input[name=denuncia]:checked').val() === '1') {
            var mancano = false;
            $('#tipodenuncia, #datepicker, #luogodenuncia, #ufficiodenuncia').each(function() {
                if ($.trim($(this).val()) === '') { $(this).addClass('fm-err'); mancano = true; }
            });
            if (mancano) msgs.push('Compila tutti i dati della denuncia.');
        }
        if ($('input[name=duplicati]:checked').val() === '1' &&
            $('#duplicati-lista input:checked').length === 0) {
            $('#duplicati-lista').addClass('fm-err');
            msgs.push('Seleziona almeno un documento per cui è stato richiesto il duplicato, oppure imposta «No».');
        }

        var $box = $('#fm-errori').empty();
        if (msgs.length === 0) { $box.hide(); return true; }
        $.each(msgs, function(i, m) { $box.append($('<div></div>').text(m)); });
        $box.show();
        $('html, body').animate({scrollTop: $box.offset().top - 20}, 200);
        return false;
    }

    function invia() {
        if (inviato) return;            // protegge dal doppio clic
        inviato = true;
        $('#avanti').prop('disabled', true).text('Elaborazione…');
        $('#form')[0].submit();
    }

    // Conferma se non sono stati indicati oggetti mancanti
    $('#dialog-form').dialog({
        autoOpen: false,
        width: 440,
        modal: true,
        resizable: false,
        dialogClass: 'cl-dialog',
        buttons: {
            "Sì, continua": function() { $(this).dialog('close'); invia(); },
            "Indietro":     function() { $(this).dialog('close'); $('#mancanti').focus(); }
        }
    });

    function avanti() {
        if (!valida()) return;
        var mancanti = $.trim($('#mancanti').val());
        if (mancanti === '' || /^nessun[oa]?\.?$/i.test(mancanti)) {
            $('#dialog-form').dialog('open');
        } else {
            invia();
        }
    }
    $('#avanti').on('click', avanti);
    $('#form').on('submit', function(e) { e.preventDefault(); avanti(); });
});
</script>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Restituzione - rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo h($rinv['brevedescrizioneoggetto']); ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">&larr; Dettagli rinvenimento</a>
        </div>
    </div>

    <?php if ($rinv['statorinvenimento'] === 'riconsegnato') { ?>
        <div class="fm-avviso">Attenzione: questo rinvenimento risulta già <b>riconsegnato</b>. Procedi solo se devi registrare una nuova restituzione.</div>
    <?php } ?>

    <form id="form" name="datapost" method="post" novalidate
          action="verbale_restituzione_init_submit.php?idrinvenimento=<?php echo $idrinvenimento; ?>">
        <input type="hidden" name="token" value="<?php echo h($token); ?>">

        <!-- Riepilogo -->
        <div class="rv-card">
            <h3>Restituzione a: <span class="rv-sub"><b><?php echo h($ritirante); ?></b></span></h3>
            <table class="fm-table">
                <thead><tr><th style="width:25%">Tipo oggetto</th><th>Descrizione</th></tr></thead>
                <tbody>
                <?php foreach ($oggetti as $o): ?>
                    <tr><td><?php echo h($o['tipooggetto']); ?></td><td><?php echo h($o['descrizioneoggetto']); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="fm-help">Oggetti in carico: <?php echo count($oggetti); ?>. Verifica con il ritirante che l'elenco sia completo.</p>
        </div>

        <!-- Denuncia -->
        <div class="rv-card">
            <h3>Denuncia</h3>
            <div class="fm-riga">
                <span>L'utente ha sporto denuncia?</span>
                <div class="fm-toggle">
                    <input type="radio" name="denuncia" id="den0" value="0" checked><label for="den0">No</label>
                    <input type="radio" name="denuncia" id="den1" value="1"><label for="den1">Sì</label>
                </div>
            </div>
            <div id="denuncia-campi" class="fm-grid" style="display:none">
                <label class="fm-field"><span class="fm-req">Tipo denuncia</span>
                    <select id="tipodenuncia" name="tipodenuncia">
                        <option value="">Seleziona…</option>
                        <option value="furto">Furto</option>
                        <option value="smarrimento">Smarrimento</option>
                    </select>
                </label>
                <label class="fm-field"><span class="fm-req">Data denuncia</span>
                    <input type="text" id="datepicker" name="datadenuncia" placeholder="gg/mm/aaaa" autocomplete="off">
                </label>
                <label class="fm-field"><span class="fm-req">Luogo della denuncia</span>
                    <input type="text" id="luogodenuncia" name="luogodenuncia">
                </label>
                <label class="fm-field"><span class="fm-req">Ufficio presso cui è stata sporta</span>
                    <input type="text" id="ufficiodenuncia" name="ufficiodenuncia">
                </label>
            </div>
        </div>

        <!-- Duplicati (solo se tra gli oggetti ci sono documenti) -->
        <?php if ($n_doc > 0) { ?>
        <div class="rv-card">
            <h3>Documenti duplicati</h3>
            <div class="fm-riga">
                <span>Sono stati richiesti duplicati di documenti?</span>
                <div class="fm-toggle">
                    <input type="radio" name="duplicati" id="dup0" value="0" checked><label for="dup0">No</label>
                    <input type="radio" name="duplicati" id="dup1" value="1"><label for="dup1">Sì</label>
                </div>
            </div>
            <div id="duplicati-lista" style="display:none">
                <p class="fm-help">Seleziona i documenti per cui è stato richiesto il duplicato. Non saranno restituiti: il titolare dovrà distruggere gli originali.</p>
                <table class="fm-table">
                    <thead><tr><th class="c">Duplicato</th><th style="width:25%">Tipo oggetto</th><th>Descrizione</th></tr></thead>
                    <tbody>
                    <?php foreach ($oggetti as $o): if ($o['documenti'] != 1) continue; ?>
                        <tr>
                            <td class="c"><input type="checkbox" name="duplicati_ids[]" value="<?php echo intval($o['idlistaoggetti']); ?>"></td>
                            <td><?php echo h($o['tipooggetto']); ?></td>
                            <td><?php echo h($o['descrizioneoggetto']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php } ?>

       <!-- Dichiarazioni e note -->
       <div class="rv-card">
            <h3>Dichiarazioni e note</h3>
            <div class="fm-grid">
                <div class="fm-field fm-full">
                    <label for="dichiarazioni"><span>Dichiarazioni dell'utente</span></label>
                    <div class="fm-input-group">
                        <textarea id="dichiarazioni" name="dichiarazioni" rows="2" placeholder="Nessuna"></textarea>
                    </div>
                </div>

                <div class="fm-field fm-full">
                    <label for="mancanti"><span>Oggetti mancanti</span></label>
                    <div class="fm-input-group">
                        <textarea id="mancanti" name="mancanti" rows="2" placeholder="Nessuno"></textarea>
                        <span class="fm-help">Indica gli oggetti che il ritirante dichiara mancanti rispetto all'elenco. Lascia vuoto se non manca nulla.</span>
                    </div>
                </div>

                <div class="fm-field fm-full">
                    <label for="noteritiro"><span>Note</span></label>
                    <div class="fm-input-group">
                        <textarea id="noteritiro" name="noteritiro" rows="2" placeholder="Nessuna"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div id="fm-errori" class="fm-msg-err" style="display:none"></div>

        <div class="rv-actionbar">
            <div class="rv-actions fm-actions">
                <span class="fm-help fm-nota">Confermando, gli oggetti passeranno allo stato «riconsegnato» e verrà generato il verbale.</span>
                <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">Annulla</a>
                <button type="button" id="avanti">Genera verbale di restituzione</button>
            </div>
        </div>
    </form>
</div>

<div id="dialog-form" title="Controlla gli oggetti mancanti" style="display:none">
    <div class="fm-dialog">
        <p>Non hai indicato oggetti mancanti.</p>
        <p>Hai chiesto al ritirante se manca qualcosa rispetto all'elenco degli oggetti in carico?</p>
    </div>
</div>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>