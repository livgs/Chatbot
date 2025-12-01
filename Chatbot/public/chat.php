<?php
// Headere for SSE (Server-Sent Events)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // for nginx / proxy

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

// Starter PHP-session (for innlogging + chat_session_id)
session_start();

// Laster inn filer
require_once __DIR__ . '/../src/ollama.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/chat_storage.php';

// Henter brukermelding
$userMessage = $_GET['message'] ?? '';
$userMessage = trim($userMessage);

if ($userMessage === '') {
    echo "data: Du må skrive en melding først.\n\n";
    flush();
    exit;
}

// Finner (eller oppretter) chat-session i databasen
// Login-koden setter $_SESSION['innlogget']['id'] til users.id_user
$userId        = $_SESSION['innlogget']['id'] ?? null;
$chatSessionId = getOrCreateChatSession($userId);

// Lagre brukermeldingen i databasen
saveChatMessage($chatSessionId, 'user', $userMessage, null);

// Sjekk om brukeren spør etter kilde
$asksForSource = (bool) preg_match('/kilde/i', $userMessage);

// Henter fakta fra databasen (fulltekstsøk)
$facts = [];
try {
    $dbConnection = get_db_connection();

    $sql = "
        WITH q AS (
            SELECT websearch_to_tsquery('norwegian', :q) AS tsq
        )
        SELECT
            f.fact_id,
            f.text,
            f.source
        FROM facts f, q
        WHERE f.language = 'no'
          AND f.search_vector @@ q.tsq
        ORDER BY ts_rank(f.search_vector, q.tsq) DESC
        LIMIT 3
    ";

    $findFactsStmt = $dbConnection->prepare($sql);
    $findFactsStmt->execute([':q' => $userMessage]);
    $facts = $findFactsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {
    echo "data: Det oppstod en feil med databasen.\n\n";
    flush();
    exit;
}

// 1) Hvis vi har fakta og brukeren spør om kilde -> svar direkte fra databasen
if (!empty($facts) && $asksForSource) {

    // Bruk den beste (første) faktaraden
    $bestFactText   = trim($facts[0]['text']   ?? '');
    $bestFactSource = trim($facts[0]['source'] ?? '');
    $bestFactId     = $facts[0]['fact_id']     ?? null;

    // Start med selve faktasetningen
    $answer = $bestFactText;

    // Sett punktum hvis nødvendig
    if ($answer !== '' && !preg_match('/[.!?]$/u', $answer)) {
        $answer .= '.';
    }

    // Legg til kilde hvis vi har den
    if ($bestFactSource !== '') {
        $answer .= ' Kilde: ' . $bestFactSource . '.';
    }

    // Lagre botsvar i databasen (knyttet til faktum)
    saveChatMessage($chatSessionId, 'bot', $answer, $bestFactId);

    // Send svaret til frontend
    echo "data: " . $answer . "\n\n";
    flush();
    exit;
}

// 2) Ellers: bygg prompt til modellen (RAG + LLM)
if (!empty($facts)) {

    // Formater fakta som punktliste
    $factLines = [];
    foreach ($facts as $fact) {
        $line = "- " . $fact['text'];
        if (!empty($fact['source'])) {
            $line .= " (Kilde: " . $fact['source'] . ")";
        }
        $factLines[] = $line;
    }

    $factsTextBlock = implode("\n", $factLines);

    // RAG-prompt: bruk fakta, men ikke motsi dem
    $modelPrompt =
        "Du får noen fakta og et spørsmål.\n" .
        "Du skal svare basert på fakta-listen under. Ikke finn opp nye detaljer som motsier fakta.\n" .
        "Svar på norsk bokmål, kort og presist (2–4 setninger).\n\n" .
        "Fakta:\n" . $factsTextBlock . "\n\n" .
        "Spørsmål: " . $userMessage . "\n\n" .
        "Svar:";

    askOllamaStream($modelPrompt, 0.0);

} else {

    // Ingen fakta funnet -> la modellen svare fritt (styrt av systemprompten i ollama.php)
    askOllamaStream($userMessage, 0.3);
}

?>
