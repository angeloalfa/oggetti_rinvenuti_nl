<?php
// nota_add.php
require_once 'config/header.php';
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

$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$url_dettagli   = 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento;

$res  = mysqli_query($conn, "SELECT brevedescrizioneoggetto FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
$rinv = $res ? mysqli_fetch_assoc($res) : null;
if (!$rinv) {
    vr_errore('Rinvenimento non trovato.');
}

$max = 1000;   // lunghezza massima della nota
?>

<div class="vb">

    <div class="vb-toolbar">
        <div class="vb-title">
            <h2>Nuova nota - rinvenimento nr. <?php echo $idrinvenimento; ?></h2>
            <span class="vb-sub"><?php echo h($rinv['brevedescrizioneoggetto']); ?></span>
        </div>
        <div class="vb-actions">
            <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">&larr; Dettagli rinvenimento</a>
        </div>
    </div>

    <form id="form" name="datapost" method="post" novalidate
          action="nota_add_submit.php?idrinvenimento=<?php echo $idrinvenimento; ?>">

        <div class="fm-avviso">
            <b>Attenzione:</b> questa nota non verrà riportata né nel verbale di rinvenimento né in quello di restituzione.
        </div>

        <div class="rv-card">
            <h3>Nota</h3>
            <p class="fm-help">
                Inserisci tutte le informazioni utili ai fini dell'individuazione del proprietario dell'oggetto rinvenuto
                o comunque utili alla gestione e alla restituzione dell'oggetto.
            </p>
            <label class="fm-field" style="margin-top:10px">
                <textarea id="nota" name="notemovimentazioni" maxlength="<?php echo $max; ?>" rows="8"
                          placeholder="Scrivi qui la nota…"></textarea>
            </label>
            <div id="nota-conta" class="nt-conta">0 / <?php echo $max; ?> caratteri</div>
        </div>

        <div id="fm-errori" class="fm-msg-err" style="display:none"></div>

        <div class="rv-actionbar">
            <div class="rv-actions fm-actions">
                <span class="fm-help fm-nota">Ctrl+Invio per salvare.</span>
                <a class="btn btn-ghost" href="<?php echo h($url_dettagli); ?>">Annulla</a>
                <button type="submit" id="salva">Salva nota</button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    var max = <?php echo $max; ?>;
    var $nota  = $('#nota');
    var $conta = $('#nota-conta');
    var $err   = $('#fm-errori');
    var inviato = false;

    // Contatore dei caratteri, evidenziato quando ci si avvicina al limite
    function aggiorna() {
        var n = $nota.val().length;
        $conta.text(n + ' / ' + max + ' caratteri')
              .toggleClass('vicino', n >= max * 0.9 && n < max)
              .toggleClass('pieno', n >= max);
    }
    $nota.on('input keyup', function() {
        $nota.removeClass('fm-err');
        $err.hide();
        aggiorna();
    });
    aggiorna();

    // Avvisa se si lascia la pagina con una nota scritta e non salvata
    $(window).on('beforeunload', function() {
        if (!inviato && $.trim($nota.val()) !== '') {
            return 'Hai scritto una nota che non è stata salvata.';
        }
    });

    // Ctrl+Invio salva
    $nota.on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#form').submit();
        }
    });

    // Controllo prima dell'invio (e protezione dal doppio clic)
    $('#form').on('submit', function(e) {
        if (inviato) { e.preventDefault(); return; }
        if ($.trim($nota.val()) === '') {
            e.preventDefault();
            $nota.addClass('fm-err').focus();
            $err.text('Scrivi il testo della nota prima di salvare.').show();
            return;
        }
        inviato = true;
        $('#salva').prop('disabled', true).text('Salvataggio…');
    });

    $nota.focus();
});
</script>

<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>