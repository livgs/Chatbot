<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Hent siste samtale (maks 20 meldinger) for innlogget bruker
$initialMessages = [];
$userId = $_SESSION['innlogget']['id'] ?? null;

if ($userId) {
    $userId = (int)$userId;

    try {
        $db = get_db_connection();

        // Finn siste chat-session for denne brukeren
        $stmt = $db->prepare("
            SELECT session_id
            FROM chat_sessions
            WHERE id_user = :id_user
            ORDER BY last_active_utc DESC
            LIMIT 1
        ");
        $stmt->execute([':id_user' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $latestSessionId = $row['session_id'];

            // Hent de siste meldingene (maks 20)
            $msgStmt = $db->prepare("
                SELECT role, text
                FROM chat_messages
                WHERE session_id = :session_id
                ORDER BY created_at_utc ASC
                LIMIT 20
            ");
            $msgStmt->execute([':session_id' => $latestSessionId]);
            $initialMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

            // Sett samme session_id til bruk i chat.php
            $_SESSION['chat_session_id'] = $latestSessionId;
        }

    } catch (Throwable $e) {
        // Logg teknisk info – vis ikke til bruker
        error_log('[index] Feil ved henting av historikk: ' . $e->getMessage());
        $initialMessages = [];
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Min Chatbot</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="page-container">

    <h2 class="title-left">Velkommen til vår astronomi-chatbot!</h2>

    <div id="chatBox"></div>

    <div class="input-row">
        <input type="text" id="input" placeholder="Skriv melding...">
        <button class="btn btn-submit" id="sendBtn">Send</button>
    </div>

    <div class="auth-btn">
        <?php if (!isset($_SESSION['innlogget'])): ?>
            <button class="btn auth-login-btn" onclick="window.location.href='user_login_form.php'">
                Logg inn
            </button>
            <button class="btn auth-register-btn" onclick="window.location.href='register_form.php'">
                Registrer deg
            </button>
        <?php else: ?>
            <button class="btn auth-logout-btn" onclick="window.location.href='logout.php'">
                Logg ut (<?= htmlspecialchars($_SESSION['innlogget']['first_name']) ?>)
            </button>
            <button class="btn mychats-btn" onclick="window.location.href='mychats.php'">
                Mine chatter
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Gjør historikken tilgjengelig for script.js -->
<script>
    window.initialMessages = <?= json_encode(
            $initialMessages,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ); ?>;
</script>

<script src="script.js"></script>
</body>
</html>
