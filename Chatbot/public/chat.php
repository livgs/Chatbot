<?php
// === STREAMING HEADERS ===
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // for nginx

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

// === LAST INN FILER ===
require_once __DIR__ . '/../src/ollama.php';
require_once __DIR__ . '/../src/db.php';

// === HENT BRUKERMELDING ===
$msg = $_GET['message'] ?? '';
$msg = trim($msg);

if ($msg === '') {
    echo "data: Du må skrive en melding først.\n\n";
    flush();
    exit;
}

// === HENT FAKTA FRA DATABASEN (FULLTEKSTSØK) ===
$facts = [];
try {
    $db = get_db_connection();

    $sql = "
        WITH q AS (
            SELECT websearch_to_tsquery('norwegian', :q) AS tsq
        )
        SELECT f.text, f.source
        FROM facts f, q
        WHERE f.language = 'no'
          AND f.search_vector @@ q.tsq
        ORDER BY ts_rank(f.search_vector, q.tsq) DESC
        LIMIT 3
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => $msg]);
    $facts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    // Kontrollert fallback hvis databasen feiler
    echo "data: Det oppstod en feil med databasen.\n\n";
    flush();
    exit;
}

// === BYGG PROMPT ===
if (!empty($facts)) {

    // Formatér fakta som punktliste
    $factLines = [];
    foreach ($facts as $f) {
        $line = "- " . $f['text'];
        if (!empty($f['source'])) {
            $line .= " (Kilde: " . $f['source'] . ")";
        }
        $factLines[] = $line;
    }

    $factsBlock = implode("\n", $factLines);

    // Myk RAG: bruk fakta som hjelp, men tillat modellens egen kunnskap
    $prompt =
        "Her er noen relevante astronomifakta som kan være nyttige når du svarer på spørsmålet under.\n" .
        "Svar på norsk bokmål, kort og presist (2–4 setninger).\n" .
        "Hvis faktaene passer til spørsmålet, bør du bruke dem i forklaringen din, men du kan også bruke annen astronomikunnskap så lenge du ikke motsier fakta.\n\n" .
        "Fakta:\n" . $factsBlock . "\n\n" .
        "Spørsmål: " . $msg;

} else {
    // Ingen fakta funnet → la modellen svare fritt basert på systemprompten i ollama.php
    $prompt = $msg;
}

// === SEND PROMPT TIL OLLAMA ===
askOllamaStream($prompt);
?>
