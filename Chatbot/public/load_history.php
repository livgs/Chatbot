<?php
session_start();
require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json; charset=utf-8');

// Vi bruker samme chat_session_id som chat.php lagrer i $_SESSION
$sessionId = $_SESSION['chat_session_id'] ?? null;

// Hvis ingen sessions, returner tom liste
if (!$sessionId) {
    echo json_encode([]);
    exit;
}

try {
    $db = get_db_connection();

    // Hent siste 20 meldinger (både bruker og bot) for denne sesjonen
    $stmt = $db->prepare("
        SELECT role, text
        FROM chat_messages
        WHERE session_id = :session_id
        ORDER BY created_at_utc DESC
        LIMIT 20
    ");
    $stmt->execute([':session_id' => $sessionId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Snu så eldste chat kommer først i UI
    $rows = array_reverse($rows);

    echo json_encode($rows);
    exit;

} catch (Throwable $e) {
    // Ved feil: returner tom liste (så siden fortsatt funker)
    echo json_encode([]);
    exit;
}

