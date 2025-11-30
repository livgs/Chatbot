<?php
require_once __DIR__ . '/db.php';

function getOrCreateChatSession(?int $userId = null): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['chat_session_id'])) {
        return $_SESSION['chat_session_id'];
    }

    $db = get_db_connection();

    // Generer en UUID i databasen (Postgres)
    $stmt = $db->query("SELECT gen_random_uuid() AS id");
    $row = $stmt->fetch();
    $sessionId = $row['id'];

    $insert = $db->prepare("
        INSERT INTO chat_sessions (session_id, user_id)
        VALUES (:session_id, :user_id)
    ");
    $insert->execute([
        ':session_id' => $sessionId,
        ':user_id'    => $userId,
    ]);

    $_SESSION['chat_session_id'] = $sessionId;

    return $sessionId;
}

function saveChatMessage(string $sessionId, string $role, string $text, ?string $originFactId = null): void
{
    $db = get_db_connection();

    $insert = $db->prepare("
        INSERT INTO chat_messages (session_id, role, text, origin_fact_id)
        VALUES (:session_id, :role, :text, :origin_fact_id)
    ");
    $insert->execute([
        ':session_id'    => $sessionId,
        ':role'          => $role,          // 'user' eller 'bot'
        ':text'          => $text,
        ':origin_fact_id'=> $originFactId,  // kan være null
    ]);

    $db->prepare("
        UPDATE chat_sessions
        SET last_active_utc = now()
        WHERE session_id = :session_id
    ")->execute([':session_id' => $sessionId]);
}
