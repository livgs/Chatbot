<?php
// Headere for SSE (Server-Sent Events)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Deaktiver output-buffering og komprimering for sanntidsstrøm
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

// Starter PHP-session (for innlogging og chat_session_id)
session_start();

// Laster inn filer
require_once __DIR__ . '/../src/ollama.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/chat_storage.php';

// Hent og trim brukerens melding
$userMessage = $_GET['message'] ?? '';
$userMessage = trim($userMessage);

if ($userMessage === '') {
    echo "data: Du må skrive en melding først.\n\n";
    flush();
    exit;
}

// Hent eller opprett chat-session
// Login-koden setter $_SESSION['innlogget']['id'] til users.id_user
$userId        = $_SESSION['innlogget']['id'] ?? null;
$chatSessionId = getOrCreateChatSession($userId);

// Lagre brukermeldingen i databasen
saveChatMessage($chatSessionId, 'user', $userMessage, null);

// Sjekk om brukeren spør etter kilde
$asksForSource = (bool) preg_match('/kilde/i', $userMessage);

// Henter fakta fra databasen (fulltekstsøk) + siste meldinger (for kontekst)
$facts        = [];
$historyBlock = '';

try {
    $dbConnection = get_db_connection();

    // Hent relevante fakta fra databasen basert på brukerens melding
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

    // Hent siste meldinger i denne chat-sesjonen (for kontekst)
    $historyStmt = $dbConnection->prepare("
        SELECT role, text
        FROM chat_messages
        WHERE session_id = :session_id
        ORDER BY created_at_utc DESC
        LIMIT 6
    ");
    $historyStmt->execute([':session_id' => $chatSessionId]);
    $historyRows = array_reverse($historyStmt->fetchAll(PDO::FETCH_ASSOC)); // eldste først

    // Bygg tekstblokk med historikk, og sett rollen "bot" eller "bruker"
    if (!empty($historyRows)) {
        $lines = [];
        foreach ($historyRows as $row) {
            $roleLabel = $row['role'] === 'user' ? 'Bruker' : 'Bot';
            $lines[]   = $roleLabel . ': ' . $row['text'];
        }

        $historyBlock =
            "Her er de siste meldingene i samtalen (bruk dette for å forstå oppfølgingsspørsmål):\n" .
            implode("\n", $lines) . "\n";
    }

} catch (Throwable $exception) {
    // Ved databasefeil: logg teknisk info og send en vennlig melding til klienten
    error_log('[chat.php] DB-feil: ' . $exception->getMessage());
    echo "data: Det oppstod en feil med databasen. Prøv igjen senere.\n\n";
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

// 2) Ellers: bygg prompt til modellen (RAG + LLM + kort historikk)
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

    // RAG-prompt: bruk fakta, ta hensyn til kort historikk
    $modelPrompt =
        "Du får litt samtalekontekst, noen fakta og et spørsmål.\n" .
        "Bruk konteksten til å forstå hva brukeren mener (oppfølgingsspørsmål osv.).\n" .
        "Du skal svare basert på fakta-listen under og den korte samtalekonteksten.\n" .
        "Ikke finn opp nye detaljer som motsier fakta.\n" .
        "Svar på norsk bokmål, kort og presist (2–4 setninger).\n\n" .
        ($historyBlock !== '' ? $historyBlock . "\n" : "") .
        "Fakta:\n" . $factsTextBlock . "\n\n" .
        "Spørsmål: " . $userMessage . "\n\n" .
        "Svar:";

    $answer = askOllamaStream($modelPrompt, 0.0);
} else {
    // Ingen fakta funnet -> la modellen svare fritt, men med kort historikk
    $modelPrompt =
        ($historyBlock !== '' ? $historyBlock . "\n" : "") .
        "Spørsmål: " . $userMessage;

    $answer = askOllamaStream($modelPrompt, 0.3);
}

// Felles håndtering: hvis vi fikk et svar, lagre og send til klient
if ($answer === null || $answer === '') {
    error_log('[chat.php] Tomt eller null-svar fra språkmodellen.');
    echo "data: Det oppstod en feil mot språkmodellen. Prøv igjen senere.\n\n";
    flush();
    exit;
}

saveChatMessage($chatSessionId, 'bot', $answer, null);
echo "data: " . $answer . "\n\n";
flush();
exit;
