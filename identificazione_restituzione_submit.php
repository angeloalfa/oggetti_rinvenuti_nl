<?php
// identificazione_restituzione_submit.php
// Salva il proprietario (tipopersona=2) o il ritirante (tipopersona=3) e risponde sempre con un JSON:
// {"success": bool, "message": "...", "error": "..."}
ob_start();      // trattiene eventuali notice/warning: il JSON deve restare valido
header('Content-Type: application/json; charset=utf-8');

require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/oggetti_rinvenuti/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');   // controllo di accesso
require_once 'config/connect.php';

if (session_id() === '') {
    session_start();
}

// Errore con un messaggio adatto all'utente (gli altri errori vengono solo registrati nel log)
class ErroreUtente extends Exception {}

function ip_post($k) {
    return (isset($_POST[$k]) && !is_array($_POST[$k])) ? trim((string)$_POST[$k]) : '';
}

// Campo con nome alternativo (compatibilità con altri moduli)
function ip_alias($principale, $alternativa) {
    $v = ip_post($principale);
    return ($v !== '') ? $v : ip_post($alternativa);
}

// gg/mm/aaaa -> aaaa-mm-gg ; vuoto -> null ; formato o data non validi -> false
function ip_data($s) {
    if ($s === '') {
        return null;
    }
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m) || !checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
        return false;
    }
    return $m[3] . '-' . $m[2] . '-' . $m[1];
}

function ip_q($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    if ($r === false) {
        throw new Exception(mysqli_error($conn));
    }
    return $r;
}

function ip_e($conn, $s) {
    return mysqli_real_escape_string($conn, $s);
}

$response = array('success' => false, 'message' => '', 'error' => '');
$in_transazione = false;

try {
    if (!isset($_SESSION['idorganigramma'])) {
        throw new ErroreUtente("Sessione scaduta: effettua di nuovo l'accesso.");
    }

    // --- Controlli sui dati ---------------------------------------------------
    $idrinvenimento = intval(ip_post('idrinvenimento'));
    $tipopersona    = intval(ip_post('tipopersona'));
    if ($idrinvenimento <= 0 || ($tipopersona !== 2 && $tipopersona !== 3)) {
        throw new ErroreUtente('Richiesta non valida: rinvenimento o tipo di persona mancante.');
    }

    $cognome = ip_post('cognome');
    $nome    = ip_post('nome');
    if ($cognome === '' || $nome === '') {
        throw new ErroreUtente('Cognome e nome sono obbligatori.');
    }

    $datanascita  = ip_data(ip_post('datanascita'));
    $rilasciatoil = ip_data(ip_alias('rilasciodocumentoil', 'rilasciatoil'));
    if ($datanascita === false) {
        throw new ErroreUtente('Data di nascita non valida (formato gg/mm/aaaa).');
    }
    if ($rilasciatoil === false) {
        throw new ErroreUtente('Data di rilascio del documento non valida (formato gg/mm/aaaa).');
    }
    $sql_nascita   = ($datanascita === null)  ? 'NULL' : "'" . $datanascita . "'";   // solo cifre e trattini
    $sql_rilascio  = ($rilasciatoil === null) ? 'NULL' : "'" . $rilasciatoil . "'";

    $e_cognome  = ip_e($conn, $cognome);
    $e_nome     = ip_e($conn, $nome);
    $e_luogo    = ip_e($conn, ip_post('luogonascita'));
    $e_resid    = ip_e($conn, ip_post('residenza'));
    $e_via      = ip_e($conn, ip_post('via'));
    $e_doc      = ip_e($conn, ip_post('documento'));
    $e_docnum   = ip_e($conn, ip_alias('numerodocumento', 'documentonumero'));
    $e_rilda    = ip_e($conn, ip_alias('rilasciodocumentodal', 'rilasciatoda'));
    $e_tel      = ip_e($conn, ip_post('telefono'));

    // Il rinvenimento deve esistere
    $res = ip_q($conn, "SELECT idrinvenimento FROM oggetti2.rinvenimenti WHERE idrinvenimento=$idrinvenimento");
    if (!mysqli_fetch_assoc($res)) {
        throw new ErroreUtente('Rinvenimento non trovato.');
    }

    // --- Salvataggio: tutto o niente ---------------------------------------------
    ip_q($conn, 'START TRANSACTION');
    $in_transazione = true;

    // Esiste già una persona di questo tipo per il rinvenimento? Allora si aggiorna
    $res = ip_q($conn, "SELECT idpersona FROM oggetti2.persone
                        WHERE idrinvenimento=$idrinvenimento AND tipopersona=$tipopersona
                        ORDER BY idpersona LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        $idpersona = intval($row['idpersona']);
        ip_q($conn, "UPDATE oggetti2.persone SET
                        cognome='$e_cognome', nome='$e_nome', datanascita=$sql_nascita,
                        luogonascita='$e_luogo', residenza='$e_resid', via='$e_via',
                        documento='$e_doc', documentonumero='$e_docnum',
                        rilasciatoda='$e_rilda', rilasciatoil=$sql_rilascio, telefono='$e_tel'
                     WHERE idpersona=$idpersona");
    } else {
        ip_q($conn, "INSERT INTO oggetti2.persone
                        (tipopersona, idrinvenimento, cognome, nome, datanascita, luogonascita, residenza, via,
                         documento, documentonumero, rilasciatoda, rilasciatoil, telefono)
                     VALUES
                        ($tipopersona, $idrinvenimento, '$e_cognome', '$e_nome', $sql_nascita, '$e_luogo', '$e_resid', '$e_via',
                         '$e_doc', '$e_docnum', '$e_rilda', $sql_rilascio, '$e_tel')");
        $idpersona = intval(mysqli_insert_id($conn));
        if ($idpersona <= 0) {
            throw new Exception("Recupero dell'ID della persona inserita non riuscito.");
        }
    }

    // Collega la persona al rinvenimento
    $campo = ($tipopersona === 2) ? 'idproprietario' : 'idritirante';
    ip_q($conn, "UPDATE oggetti2.rinvenimenti SET $campo=$idpersona WHERE idrinvenimento=$idrinvenimento");

    ip_q($conn, 'COMMIT');
    $in_transazione = false;

    $response['success'] = true;
    $response['message'] = 'I dati sono stati salvati con successo.';

} catch (ErroreUtente $e) {
    if ($in_transazione) { mysqli_query($conn, 'ROLLBACK'); }
    $response['error'] = $e->getMessage();

} catch (Exception $e) {
    if ($in_transazione && isset($conn)) { mysqli_query($conn, 'ROLLBACK'); }
    error_log('[IDENTIFICAZIONE RESTITUZIONE] ' . $e->getMessage());
    $response['error'] = 'Errore interno durante il salvataggio. Riprova o contatta l\'assistenza.';
}

// Scarta qualsiasi output accidentale e invia solo il JSON
while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response);
exit;