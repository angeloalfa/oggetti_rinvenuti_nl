<?php
// salva_feedback.php

header('Content-Type: application/json; charset=utf-8');

require_once($_SERVER['DOCUMENT_ROOT'] . '/applicazioni/commons/auth/protect.php');

// 2. Inclusione connessione database
require_once __DIR__ . '/config/connect.php';
global $conn;

// Verifica presenza connessione
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore di connessione al database']);
    exit;
}

// 3. Recupero e validazione del punteggio
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valutazione non valida (deve essere tra 1 e 5)']);
    exit;
}

// 4. Dati operatore in sessione
$idutente = $_SESSION['idutente'] ?? ($_SESSION['id_utente'] ?? 0);
$cognome  = $_SESSION['cognome'] ?? '';
$nome     = $_SESSION['nome'] ?? '';
$ufficio  = $_SESSION['ufficio'] ?? '';
$operatore = trim($nome . ' ' . $cognome);

if (empty($operatore)) {
    $operatore = 'Operatore Sconosciuto';
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

// 5. Inserimento nel database tramite Prepared Statement
$sql = "INSERT INTO oggetti2.feedback (idutente, operatore, ufficio, voto, ip_address, data_inserimento) 
        VALUES (?, ?, ?, ?, ?, NOW())";

try {
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Errore nella preparazione della query: " . mysqli_error($conn));
    }

    // Bind dei parametri: i (integer), s (string), s (string), i (integer), s (string)
    mysqli_stmt_bind_param($stmt, "issis", $idutente, $operatore, $ufficio, $rating, $ip_address);
    
    $executed = mysqli_stmt_execute($stmt);

    if ($executed) {
        echo json_encode(['success' => true, 'message' => json_encode($_SESSION)]);
    } else {
        throw new Exception("Errore nell'esecuzione della query");
    }

    mysqli_stmt_close($stmt);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio: ' . $e->getMessage()]);
}
?>