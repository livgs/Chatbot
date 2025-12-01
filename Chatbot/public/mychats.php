<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// --- 1. Finn innlogget bruker ---
$userId = $_SESSION['innlogget']['id'] ?? null;
$userId = $userId !== null ? (int)$userId : null;

// Flags/data til visning
$mustLogin    = false;
$dbError      = false;
$sessions     = [];

if ($userId === null) {
    // Ikke innlogget -> vis info i HTML-delen
    $mustLogin = true;
} else {
    // --- 2. Hent sessions fra databasen ---
    try {
        $db = get_db_connection();

        $stmt = $db->prepare("
            SELECT session_id,
                   started_at_utc,
                   last_active_utc,
                   client_label
            FROM chat_sessions
            WHERE id_user = :id_user
            ORDER BY last_active_utc DESC
        ");
        $stmt->execute([':id_user' => $userId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $exception) {
        $dbError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Mine chatter</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="header-row">
<button class="btn btn-return" onclick="window.location.href='index.php'">
    &larr; Tilbake til chatbot
</button>
<h1>Mine tidligere chatter</h1>
</div>

<?php if ($mustLogin): ?>

    <div class="empty">
        <p>Du må være logget inn for å se lagrede chatter.</p>
    </div>

<?php elseif ($dbError): ?>

    <div class="empty">
        <p>Det oppstod en feil med databasen. Prøv igjen senere.</p>
    </div>

<?php elseif (empty($sessions)): ?>

    <div class="empty">
        <p>Du har ingen lagrede chatter ennå.</p>
        <p>Skriv en melding til chatboten for å starte en ny samtale.</p>
    </div>

<?php else: ?>

    <?php foreach ($sessions as $session): ?>
        <?php
        $sessionId   = $session['session_id'];
        $startedAt   = $session['started_at_utc'];
        $lastActive  = $session['last_active_utc'];
        $clientLabel = $session['client_label'] ?? '';

        // Hent de siste 5 meldingene for hver session
        $msgStmt = $db->prepare("
            SELECT role, text, created_at_utc
            FROM chat_messages
            WHERE session_id = :session_id
            ORDER BY created_at_utc DESC
            LIMIT 5
        ");
        $msgStmt->execute([':session_id' => $sessionId]);
        $messages = array_reverse($msgStmt->fetchAll(PDO::FETCH_ASSOC));
        ?>
        <div class="session-card">
            <div class="session-meta">
                <div class="session-label">
                    Samtale-ID: <code><?= htmlspecialchars($sessionId) ?></code>
                </div>
                <div>Startet: <?= htmlspecialchars($startedAt) ?></div>
                <div>Sist aktiv: <?= htmlspecialchars($lastActive) ?></div>
                <?php if ($clientLabel): ?>
                    <div>Merknad: <?= htmlspecialchars($clientLabel) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($messages)): ?>
                <div class="messages">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $roleLabel = $msg['role'] === 'user' ? 'Du' : 'Bot';
                        $roleClass = $msg['role'] === 'user' ? 'msg-role-user' : 'msg-role-bot';
                        ?>
                        <p class="msg-line">
                            <span class="<?= $roleClass ?>">
                                <?= htmlspecialchars($roleLabel) ?>:
                            </span>
                            <?= htmlspecialchars($msg['text']) ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><em>Ingen meldinger registrert i denne samtalen.</em></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
