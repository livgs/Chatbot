<?php
require_once __DIR__ . '/db.php';

function getOrCreateChatSession(?int $userId = null): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $db = get_db_connection();

    // Hvis vi allerede har en session_id lagret i PHP-sessionen
    if (!empty($_SESSION['chat_session_id'])) {
        $sessionId = $_SESSION['chat_session_id'];

        // Hvis bruker nå er logget inn, men raden i DB mangler id_user → sett den
        if ($userId !== null) {
            $update = $db->prepare("
                UPDATE chat_sessions
                SET id_user = :id_user
                WHERE session_id = :session_id
                  AND id_user IS NULL
            ");
            $update->execute([
                ':id_user'    => $userId,
                ':session_id' => $sessionId,
            ]);
        }

        return $sessionId;
    }

    // Ingen eksisterende chat_session -> opprett ny
    $stmt = $db->query("SELECT gen_random_uuid() AS id");
    $row = $stmt->fetch();
    $sessionId = $row['id'];

    $insert = $db->prepare("
        INSERT INTO chat_sessions (session_id, id_user)
        VALUES (:session_id, :id_user)
    ");
    $insert->execute([
        ':session_id' => $sessionId,
        ':id_user'    => $userId,
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
