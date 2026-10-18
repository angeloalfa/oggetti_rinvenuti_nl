<?php
require_once 'config/header.php';
require 'config/connect.php';    // connessione al database
require_once 'functions/function.php';
require_once 'functions/utility.php';
require_once 'config/autorizza.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$idrinvenimento = (int)(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$azione         = isset($_REQUEST['azione']) ? $_REQUEST['azione'] : '';
$trasm          = (int)(isset($_REQUEST['trasmissione']) ? $_REQUEST['trasmissione'] : 0);
$idorganigramma = (int)$_SESSION['idorganigramma'];
$livello        = (int)$_SESSION['livello'];

// Cambio di stato richiesto dai pulsanti
if ($azione == 'annullato') {
    change_state($idrinvenimento, 40, "annullato");
} elseif ($azione == 'carico') {
    change_state($idrinvenimento, 10, "in carico");
} elseif ($azione == 'riconsegnato') {
    change_state($idrinvenimento, 20, "riconsegnato");
}

// Valori di default (evitano notice se un campo è assente)
$proprietario = $consegnante = $ritirante = '';
$ufficio_trasm = $tipo_uff_trasm = $citta_trasm = $indirizzo_trasm = '';
$idorganigramma_in = $idorganigramma_out = 0;
$note_rinv = '';
$visto = 0;

// Recupero il rinvenimento; ogni campo del DB diventa una variabile con lo stesso nome
$result = mysqli_query($conn, "SELECT * FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$r = $result ? mysqli_fetch_assoc($result) : null;
if (!$r) {
    echo "<tr><td class='rv-vuoto'>Rinvenimento non trovato.</td></tr>";
    require_once 'config/footer.php';
    exit;
}
foreach ($r as $k => $v) {
    $$k = $v;
}

if (trim((string)$note_rinv) === '') {
    $note_rinv = 'Nessuna';
}

// Stato del rinvenimento -> classe del badge
$classi_stato = array(
    'in carico'      => 'b-carico',
    'in trattazione' => 'b-trattazione',
    'riconsegnato'   => 'b-riconsegnato',
    'trasmesso'      => 'b-trasmesso',
    'annullato'      => 'b-annullato',
);
$classe_stato = isset($classi_stato[$statorinvenimento]) ? $classi_stato[$statorinvenimento] : 'b-altro';

// Indicatore "visto" (stesso significato dei pallini della lista)
$testi_visto = array(
    0 => 'Da trattare',
    1 => 'In attesa di restituzione',
    2 => 'Accertamenti in corso',
    3 => 'Da trasmettere',
);
$visto = isset($testi_visto[(int)$visto]) ? (int)$visto : 0;

// Persone collegate
$ids = array();
foreach (array($idconsegnante, $idproprietario, $idritirante) as $p) {
    if ($p !== '' && $p !== null) {
        $ids[] = "'" . mysqli_real_escape_string($conn, $p) . "'";
    }
}
if (count($ids) > 0) {
    $result_persone = mysqli_query($conn, "SELECT * FROM oggetti2.persone WHERE idpersona IN (" . implode(',', $ids) . ")");
    while ($rows_persone = mysqli_fetch_assoc($result_persone)) {
        if ($rows_persone['tipopersona'] == 1) {
            $consegnante = get_persona($rows_persone, 'Consegnante');
        } elseif ($rows_persone['tipopersona'] == 2) {
            $proprietario = get_persona($rows_persone, 'Proprietario');
        } elseif ($rows_persone['tipopersona'] == 3) {
            $ritirante = get_persona($rows_persone, 'Ritirante');
        }
    }
}

// Dati di trasmissione
if (!empty($idtrasmissione)) {
    $result_trasm = mysqli_query($conn, "SELECT * FROM oggetti2.trasmissioni WHERE idtrasmissioni=" . (int)$idtrasmissione);
    while ($rows_trasm = mysqli_fetch_assoc($result_trasm)) {
        foreach ($rows_trasm as $k => $v) {
            $$k = $v;
        }
    }
}

// Rinvenimento precedente e successivo nella stessa posizione
$luogo_esc = mysqli_real_escape_string($conn, $luogoverbale);
$row_next = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT MIN(idrinvenimento) AS n FROM oggetti2.rinvenimenti
     WHERE idrinvenimento > $idrinvenimento AND luogoverbale='$luogo_esc' AND statorinvenimento='in carico'"));
$row_prev = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT MAX(idrinvenimento) AS p FROM oggetti2.rinvenimenti
     WHERE idrinvenimento < $idrinvenimento AND luogoverbale='$luogo_esc' AND statorinvenimento='in carico'"));

// Configurazione passata al JavaScript (json_encode gestisce gli apici in sicurezza)
$cfg = array(
    'id'           => $idrinvenimento,
    'trasm'        => $trasm,
    'livello'      => $livello,
    'idOrgSession' => $idorganigramma,
    'idOrgRinv'    => (int)$idorganigramma_in,
    'idOrgRest'    => (int)$idorganigramma_out,
    'uffTrasm'     => (string)$ufficio_trasm,
    'tipoUffTrasm' => (string)$tipo_uff_trasm,
    'prev'         => $row_prev['p'] ? (int)$row_prev['p'] : 0,
    'next'         => $row_next['n'] ? (int)$row_next['n'] : 0,
    'azione'       => $azione,
);
?>
<script src="js/input_format.js"></script>
<script type="text/javascript">
var cfg = <?php echo json_encode($cfg); ?>;

// Salva il nuovo stato "visto" e torna alla lista solo a salvataggio riuscito
function cambiaVisto(v) {
    $.post('change_visto.php', {idrinvenimento: cfg.id, visto: v})
        .done(function() { location.href = 'oggetti_rinvenuti_list.php'; })
        .fail(function(x) {
            console.log(x.responseText);
            alert('Errore nel salvataggio dello stato.');
        });
}

// Usata dai pulsanti "Verbale Restituzione" (vedi sotto)
function confermaElimina() {
    return confirm("Vuoi davvero eliminare (annullare) questo rinvenimento?");
}

$(document).ready(function() {

    // Se la pagina è stata aperta con un'azione, ripulisco l'URL: un refresh non la rieseguirà
    if (cfg.azione) {
        history.replaceState(null, '', 'rinvenimento_view.php?idrinvenimento=' + cfg.id);
    }

    // Visibilità degli elementi in base al livello di autorizzazione
    var l = cfg.livello;
    if (l === 3 || l === 0) $('.livello3').show();
    if (l === 2 || l === 1 || l === 0) $('.livello2').show();
    if (l === 1 || l === 0) $('.livello1').show();
    if (l === 0) $('.livello0').show();

    // Modifica: chi ha fatto il rinvenimento, chi sta restituendo, e il livello 0
    if (cfg.idOrgSession === cfg.idOrgRinv || cfg.idOrgSession === cfg.idOrgRest || l === 0) {
        $('#modifica').show();
    }

    $('#citta').upperCase();
    $('#tipo_uff_trasm').upperCase();

    // Dialog "Trattato"
    $('#dialog-form').dialog({
        autoOpen: false,
        width: 500,
        modal: true,
        buttons: {
            "In Riconsegna":  function() { cambiaVisto(1); },
            "Accertamenti":   function() { cambiaVisto(2); },
            "Trasmettere":    function() { cambiaVisto(3); },
            "Annulla":        function() { $(this).dialog('close'); }
        }
    });
    $('#trattato').click(function() { $('#dialog-form').dialog('open'); });

    // Sezione trasmissione
    $('#trasmetti').click(function() {
        $('.view').hide();
        $('.trasm').show();
        $('#ufficio_t').val(cfg.uffTrasm);
    });
    if (cfg.trasm === 0) { $('.trasm').hide(); } else { $('.view').hide(); }
    if (cfg.uffTrasm !== '') { $('.trasm').show(); }
    $('.tipo_uff_trasm').toggle(cfg.tipoUffTrasm !== '');
    $('#ufficio_t').change(function() {
        var val = $(this).val();
        $('.tipo_uff_trasm').toggle(val == 3 || val == 4);
    });
    $('#ufficio_t').val(cfg.uffTrasm);

    // Precedente / successivo
    $('#prev').click(function() {
        if (!cfg.prev) { alert("Non ci sono altri rinvenimenti prima di questo"); return; }
        location.href = 'rinvenimento_view.php?idrinvenimento=' + cfg.prev;
    });
    $('#next').click(function() {
        if (!cfg.next) { alert("Non ci sono altri rinvenimenti oltre questo"); return; }
        location.href = 'rinvenimento_view.php?idrinvenimento=' + cfg.next;
    });

    // Frecce da tastiera (solo se non si sta scrivendo in un campo)
    $(document).on('keydown', function(e) {
        if ($(e.target).is('input, textarea, select')) return;
        if (e.which === 37) $('#prev').click();
        if (e.which === 39) $('#next').click();
    });
});
</script>

<tr>
<td>
<div class="rv">

    <!-- Testata -->
    <div class="rv-head">
        <a class="btn btn-ghost" href="oggetti_rinvenuti_list.php">&larr; Lista</a>
        <h2>Dettagli rinvenimento</h2>
    </div>

    <div class="rv-summary">
        <div class="rv-nav">
            <button type="button" id="prev" title="Rinvenimento precedente (←)">&lsaquo;</button>
            <div class="rv-item"><span class="rv-label">Protocollo</span><b><?php echo $idrinvenimento; ?></b></div>
            <button type="button" id="next" title="Rinvenimento successivo (→)">&rsaquo;</button>
        </div>
        <div class="rv-item"><span class="rv-label">Data rinvenimento</span><b><?php echo h(convertiDataAE($datarinvenimento)); ?></b></div>
        <div class="rv-item rv-grow"><span class="rv-label">Luogo di rinvenimento</span><b><?php echo h($luogorinvenimento); ?></b></div>
        <div class="rv-item"><span class="rv-label">Stato</span><span class="badge <?php echo $classe_stato; ?>"><?php echo h($statorinvenimento); ?></span></div>
        <div class="rv-item"><span class="rv-label">Trattazione</span><span><i class="stato stato-<?php echo $visto; ?>"></i> <?php echo h($testi_visto[$visto]); ?></span></div>
    </div>

    <!-- Corpo -->
    <div id="corpo">

        <div class="rv-card">
            <h3>Persone</h3>
            <div class="rv-persone">
                <div><?php echo $proprietario === '' ? 'Proprietario: Sconosciuto' : $proprietario; ?></div>
                <div><?php echo $consegnante === '' ? 'Consegnante: Sconosciuto' : $consegnante; ?></div>
                <?php if ($ritirante !== '') { echo "<div>$ritirante</div>"; } ?>
            </div>
        </div>

        <div class="rv-card">
            <h3>Note</h3>
            <p><?php echo nl2br(h($note_rinv)); ?></p>
        </div>

        <div class="rv-card">
            <h3>Oggetti rinvenuti: <span class="rv-sub"><?php echo h($brevedescrizioneoggetto); ?></span></h3>
            <iframe src="listaoggetti_frame.php?idrinvenimento=<?php echo $idrinvenimento; ?>" title="Lista oggetti"></iframe>
        </div>

        <div class="rv-card">
            <h3>Movimentazioni</h3>
            <iframe src="listamovimentazione_frame.php?idrinvenimento=<?php echo $idrinvenimento; ?>" title="Lista movimentazioni"></iframe>
        </div>

        <!-- Trasmissione -->
        <div class="trasm rv-card">
            <h3>Dati di trasmissione</h3>
            <form id="form" name="trasmission" method="post"
                  action="trasmissione_submit.php?idrinvenimento=<?php echo $idrinvenimento; ?>">
                <div class="rv-form livello2 livello3" style="display:none">
                    <label>Ufficio a cui trasmettere
                        <select name="ufficio_trasm" id="ufficio_t">
                            <option></option>
                            <?php foreach ($ufficio_trasmissione as $i => $value): ?>
                                <option value="<?php echo h($i); ?>"><?php echo h($value); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="tipo_uff_trasm">Del
                        <input type="text" name="tipo_uff_trasm" id="tipo_uff_trasm" value="<?php echo h($tipo_uff_trasm); ?>">
                    </label>
                    <label>Città
                        <input type="text" name="citta_trasm" id="citta" value="<?php echo h($citta_trasm); ?>">
                    </label>
                    <label class="rv-full">Indirizzo
                        <textarea rows="4" name="indirizzo_trasm"><?php echo h($indirizzo_trasm); ?></textarea>
                    </label>
                    <div class="rv-full"><input type="submit" value="Salva"></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Barra azioni (resta visibile in fondo) -->
    <div class="view rv-actionbar">
        <div class="rv-actions">
            <a class="btn" href="identificazione_restituzione.php?idrinvenimento=<?php echo $idrinvenimento; ?>">Verbale Restituzione</a>
            <a class="btn" href="verbale_rinvenimento.php?idrinvenimento=<?php echo $idrinvenimento; ?>">Verbale Rinvenimento</a>
            <a class="btn" href="nota_add.php?idrinvenimento=<?php echo $idrinvenimento; ?>">Aggiungi Nota</a>
            <a class="btn" id="modifica" style="display:none"
               href="identificazione.php?tipopersona=1&amp;idrinvenimento=<?php echo $idrinvenimento; ?>">Modifica</a>
            <button type="button" id="trasmetti">Trasmetti</button>
        </div>

        <div class="rv-actions livello2 livello3" style="display:none">
            <button type="button" id="trattato">Trattato</button>
            <a class="btn" href="listamovimentazione.php?idrinvenimento=<?php echo $idrinvenimento; ?>">Movimentazioni oggetto</a>
            <a class="btn" href="identificazione.php?tipopersona=2&amp;idrinvenimento=<?php echo $idrinvenimento; ?>&amp;edit_own=TRUE">Aggiungi/Modifica Proprietario</a>
            <a class="btn" href="listaoggetti_edit.php?idrinvenimento=<?php echo $idrinvenimento; ?>">Gestisci Oggetti</a>
            <a class="btn" href="rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento; ?>&amp;azione=carico">In Carico</a>
            <a class="btn" href="rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento; ?>&amp;azione=riconsegnato">Riconsegnato</a>
            <a class="btn btn-danger" onclick="return confermaElimina();"
               href="rinvenimento_view.php?idrinvenimento=<?php echo $idrinvenimento; ?>&amp;azione=annullato">Elimina</a>
        </div>
    </div>

</div>

<div id="dialog-form" title="Rinvenimento trattato" style="display:none">
    <p>Nella lista, accanto ai rinvenimenti apparirà:</p>
    <p><i class="stato stato-1"></i> Se si è in attesa del proprietario<br>
       <i class="stato stato-2"></i> Se ci sono accertamenti in corso<br>
       <i class="stato stato-3"></i> Se è da trasmettere</p>
    <p>Usa i pulsanti qui sotto per cambiarne lo stato.</p>
</div>
</td>
</tr>

<?php require_once 'config/footer.php'; ?>