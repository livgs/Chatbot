<?php
session_start();

require_once __DIR__ . '/../src/db.php';

// Hent bruker-ID fra session-strukturen som login faktisk bruker
$userId = $_SESSION['innlogget']['id'] ?? null;

// Sjekk om brukeren er logget inn
if ($userId === null) {
    echo "<!DOCTYPE html>
<html lang=\"no\">
<head>
    <meta charset=\"UTF-8\">
    <title>Mine chatter</title>
    <link rel=\"stylesheet\" href=\"style.css\">
</head>
<body>
    <h1>Mine tidligere chatter</h1>
    <div class=\"empty\">
        <p>Du må være logget inn for å se lagrede chatter.</p>
        <p><a class=\"back-link\" href=\"index.php\">Til forsiden</a></p>
    </div>
</body>
</html>";
    exit;
}

$userId = (int) $userId;

try {
    $db = get_db_connection();

    // Hent alle chat-sessions for denne brukeren
    $stmt = $db->prepare("
        SELECT session_id,
               started_at_utc,
               last_active_utc,
               client_label
        FROM chat_sessions
        WHERE user_id = :user_id
        ORDER BY last_active_utc DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {
    echo "<!DOCTYPE html>
<html lang=\"no\">
<head>
    <meta charset=\"UTF-8\">
    <title>Mine chatter</title>
</head>
<body>
    <h1>Mine tidligere chatter</h1>
    <p>Det oppstod en feil med databasen. Prøv igjen senere.</p>
</body>
</html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Mine chatter</title>
</head>
<body>
<h1>Mine tidligere chatter</h1>

<?php if (empty($sessions)): ?>
    <div class="empty">
        <p>Du har ingen lagrede chatter ennå.</p>
        <p>Skriv en melding til chatboten for å starte en ny samtale.</p>
    </div>
<?php else: ?>

    <?php
    // Hent noen få meldinger per sesjon (for eksempel de siste 5)
    foreach ($sessions as $session):
        $sessionId   = $session['session_id'];
        $startedAt   = $session['started_at_utc'];
        $lastActive  = $session['last_active_utc'];
        $clientLabel = $session['client_label'] ?? '';

        // For enkelhet: hent de siste 5 meldingene for denne session
        $msgStmt = $db->prepare("
            SELECT role, text, created_at_utc
            FROM chat_messages
            WHERE session_id = :session_id
            ORDER BY created_at_utc DESC
            LIMIT 5
        ");
        $msgStmt->execute([':session_id' => $sessionId]);
        $messages = array_reverse($msgStmt->fetchAll(PDO::FETCH_ASSOC)); // snu for kronologisk visning
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
                            <span class="<?= $roleClass; ?>">
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

<p><a class="back-link" href="index.php">&larr; Tilbake til chatbot</a></p>

</body>
</html>

