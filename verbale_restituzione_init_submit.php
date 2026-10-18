<?php
// verbale_restituzione_init_submit.php
require_once 'config/header.php';
require 'config/connect.php';            // connessione al database
require_once 'functions/function.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('debug_log')) {
    function debug_log($msg) { error_log("[RESTITUZIONE] " . $msg); }
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

// Valore di un campo del form, ripulito
function vr_post($k) {
    return isset($_POST[$k]) && !is_array($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

// Esegue una query e lancia un'eccezione se fallisce (serve per il rollback)
function vr_q($conn, $sql) {
    if (!mysqli_query($conn, $sql)) {
        throw new Exception(mysqli_error($conn));
    }
}

echo "<tr><td>";

$idrinvenimento = intval(isset($_REQUEST['idrinvenimento']) ? $_REQUEST['idrinvenimento'] : 0);
$url_dettagli   = 'rinvenimento_view.php?idrinvenimento=' . $idrinvenimento;
$url_form       = 'verbale_restituzione_init.php?idrinvenimento=' . $idrinvenimento;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    vr_errore('Richiesta non valida.', array('Torna al modulo' => $url_form));
}

// Il rinvenimento deve esistere
$res = mysqli_query($conn, "SELECT idrinvenimento FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
if (!$res || !mysqli_fetch_assoc($res)) {
    vr_errore('Rinvenimento non trovato.');
}

// Sessione dell'operatore
if (!isset($_SESSION['idorganigramma'])) {
    vr_errore("Sessione non valida: effettua di nuovo l'accesso.");
}
$idorganigramma = intval($_SESSION['idorganigramma']);

// Codice monouso (verificato ora, consumato solo dopo i controlli sui dati)
$token  = vr_post('token');
$atteso = isset($_SESSION['vr_token'][$idrinvenimento]) ? $_SESSION['vr_token'][$idrinvenimento] : '';
if ($token === '' || $atteso === '' || !hash_equals($atteso, $token)) {
    vr_errore("La restituzione è già stata registrata oppure la pagina è scaduta. Controlla lo stato del rinvenimento prima di ripetere l'operazione.",
        array('Dettagli rinvenimento' => $url_dettagli, 'Ricompila il modulo' => $url_form));
}

// ---------------------------------------------------------------------------
// Lettura e controllo dei dati del form
// ---------------------------------------------------------------------------
$denuncia        = (vr_post('denuncia') === '1');
$tipodenuncia    = vr_post('tipodenuncia');
$datadenuncia_in = vr_post('datadenuncia');
$luogodenuncia   = vr_post('luogodenuncia');
$ufficiodenuncia = vr_post('ufficiodenuncia');

$dichiarazioni = vr_post('dichiarazioni'); if ($dichiarazioni === '') { $dichiarazioni = 'Nessuna'; }
$mancanti      = vr_post('mancanti');      if ($mancanti === '')      { $mancanti      = 'Nessuno'; }
$noteritiro    = vr_post('noteritiro');    if ($noteritiro === '')    { $noteritiro    = 'Nessuna'; }

// Documenti duplicati: considerati solo se "Sì" è stato scelto
$duplicati = (vr_post('duplicati') === '1');
$ids_dup   = array();
if ($duplicati && isset($_POST['duplicati_ids']) && is_array($_POST['duplicati_ids'])) {
    foreach ($_POST['duplicati_ids'] as $v) {
        $v = intval($v);
        if ($v > 0) { $ids_dup[$v] = $v; }
    }
}

$errori = array();
if ($denuncia) {
    if (!in_array($tipodenuncia, array('furto', 'smarrimento'), true)) { $errori[] = 'Tipo di denuncia non valido.'; }
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $datadenuncia_in, $m)
        || !checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
        $errori[] = 'Data della denuncia non valida.';
    }
    if ($luogodenuncia === '')                                           { $errori[] = 'Luogo della denuncia mancante.'; }
    if ($ufficiodenuncia === '')                                         { $errori[] = 'Ufficio della denuncia mancante.'; }
}
if ($duplicati && count($ids_dup) === 0) {
    $errori[] = 'Nessun documento selezionato per il duplicato.';
}
if (count($errori) > 0) {
    vr_errore(implode(' ', $errori), array('Indietro' => 'javascript:history.back()'));
}

// Da qui il codice monouso è consumato: un secondo invio verrà rifiutato
unset($_SESSION['vr_token'][$idrinvenimento]);

// Data, ora e operatore corrente
$time            = time();
$datainserimento = date('Y-m-d', $time);
$orainserimento  = date('H:i', $time);
$data_out        = date('Y-m-d H:i', $time);

$abbrev   = isset($_SESSION['abbrevqualifica']) ? $_SESSION['abbrevqualifica'] : '';
$cognome  = isset($_SESSION['cognome']) ? $_SESSION['cognome'] : '';
$nome     = isset($_SESSION['nome']) ? $_SESSION['nome'] : '';
$operatore = trim($abbrev . ' ' . $cognome . ' ' . $nome);

// Escape per il database
$e_dich   = mysqli_real_escape_string($conn, $dichiarazioni);
$e_manc   = mysqli_real_escape_string($conn, $mancanti);
$e_note   = mysqli_real_escape_string($conn, $noteritiro);
$e_oper   = mysqli_real_escape_string($conn, $operatore);

// ---------------------------------------------------------------------------
// Registrazione della restituzione: tutto o niente
// ---------------------------------------------------------------------------
mysqli_query($conn, "START TRANSACTION");
try {
    // 1) Documenti per cui è stato richiesto il duplicato: segnati come distrutti,
    //    così non compaiono tra gli oggetti restituiti nel verbale
    if (count($ids_dup) > 0) {
        vr_q($conn, "UPDATE oggetti2.listaoggetti
                     SET idstatooggetto='6', duplicato='1'
                     WHERE idrinvenimento=$idrinvenimento
                       AND idlistaoggetti IN (" . implode(',', $ids_dup) . ")");
    }

    // 2) Denuncia
    $iddenuncia_sql = 'NULL';
    if ($denuncia) {
        $e_tipo = mysqli_real_escape_string($conn, $tipodenuncia);
        $e_data = mysqli_real_escape_string($conn, convertiDataEA($datadenuncia_in));
        $e_luog = mysqli_real_escape_string($conn, $luogodenuncia);
        $e_uff  = mysqli_real_escape_string($conn, $ufficiodenuncia);
        vr_q($conn, "INSERT INTO oggetti2.denunce (tipodenuncia, datadenuncia, avvenutoa, ufficiodenuncia)
                     VALUES ('$e_tipo', '$e_data', '$e_luog', '$e_uff')");
        $iddenuncia_sql = intval(mysqli_insert_id($conn));
    }

    // 3) Rinvenimento: denuncia, dichiarazioni, mancanti, note, uscita e nuovo stato
    vr_q($conn, "UPDATE oggetti2.rinvenimenti
                 SET iddenuncia=$iddenuncia_sql,
                     oggettimancanti='$e_manc',
                     dichiarazioneritirante='$e_dich',
                     noteritiro='$e_note',
                     idorganigramma_out='$idorganigramma',
                     data_uscita='$data_out',
                     statorinvenimento='riconsegnato',
                     idstato=20
                 WHERE idrinvenimento=$idrinvenimento");

    // 4) Tutti gli oggetti ancora "in carico" diventano "riconsegnato"
    vr_q($conn, "UPDATE oggetti2.listaoggetti
                 SET idstatooggetto='3'
                 WHERE idrinvenimento=$idrinvenimento AND idstatooggetto<4");

    // 5) Movimentazione
    vr_q($conn, "INSERT INTO oggetti2.movimentazionioggetti
                     (idrinvenimento, operatore, posizione, datamovimentazione, oramovimentazione,
                      statomovimentazione, notemovimentazione, idorganigramma)
                 VALUES ('$idrinvenimento', '$e_oper', 'Proprietario', '$datainserimento', '$orainserimento',
                         'riconsegnato', 'Rinvenimento Riconsegnato', '$idorganigramma')");

    mysqli_query($conn, "COMMIT");

} catch (Exception $e) {
    mysqli_query($conn, "ROLLBACK");
    debug_log("ERRORE registrazione restituzione id=$idrinvenimento: " . $e->getMessage());
    vr_errore("Non è stato possibile registrare la restituzione: nessuna modifica è stata salvata. Riprova o contatta l'assistenza.",
        array('Riprova' => $url_form, 'Dettagli rinvenimento' => $url_dettagli));
}

// A questo punto si genera il verbale di restituzione
$url_verbale = 'verbale_restituzione.php?idrinvenimento=' . $idrinvenimento;
?>
<div class="vb">
    <div class="vb-attesa">
        <div class="vb-spinner"></div>
        <div>Restituzione registrata. Preparazione del verbale…</div>
        <a class="btn" href="<?php echo h($url_verbale); ?>">Se non vieni reindirizzato, clicca qui</a>
    </div>
</div>
<meta http-equiv="refresh" content="0;url=<?php echo h($url_verbale); ?>">
<?php
echo "</td></tr>";
require_once 'config/footer.php';
?>