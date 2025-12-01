<?php
// Hjelpefunksjon: rydder opp i whitespace
function cleanWhitespace(string $text): string {
    // Erstatter all whitespace (linjeskift, tab osv.) med ett enkelt mellomrom
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function askOllamaStream(string $userMessage = '', float $temperature = 0.3): void
{
    if ($userMessage === '') {
        return;
    }

    // Sørg for at output kan streames til EventSource
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);

    // Stilguide + few-shot
    $messages = [
        [
            "role" => "system",
            "content" =>
                "Du skriver KUN på korrekt norsk bokmål. Ikke bland inn engelsk eller dansk eller andre språk.\n" .
                "Hold språket nøytralt, presist og kort (2–4 setninger).\n" .
                "Bruk norske fagtermer. Eksempler: 'nettleser' (ikke 'browser'), 'ytelse' (ikke 'performance'), 'funksjon' (ikke 'feature'), 'forespørsel' (ikke 'request').\n" .
                "Skriv 'NASAs', ikke 'NASA's'. Skriv 'oppdrag' eller 'ferd', ikke 'mission'.\n" .
                "Sett mellomrom etter punktum, spørsmålstegn og utropstegn."
        ],
        [
            "role" => "system",
            "content" =>
                "Du er en vennlig astronomi-assistent. Svar bare på astronomi. " .
                "Hvis spørsmålet ikke gjelder astronomi: Svar nøyaktig 'Det veit jeg ikke, jeg er bare en liten astronomibot'."
        ],
        [
            "role" => "user",
            "content" => "Hvem er Buzz Aldrin?"
        ],
        [
            "role" => "assistant",
            "content" =>
                "Buzz Aldrin er en amerikansk astronaut og ingeniør. " .
                "Han var den andre personen til å gå på månen under månelandingen i 1969, etter Neil Armstrong. " .
                "Aldrin spilte en viktig rolle i NASAs månelandingsprogram og har skrevet flere bøker om sine erfaringer som astronaut."
        ],
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ];

    // Payload til Ollama (NB: stream = false nå, vi henter alt i én respons)
    $payload = json_encode([
        "model"    => "llama3",
        "stream"   => false,
        "messages" => $messages,
        "options"  => [
            "temperature"    => $temperature,
            "top_p"          => 0.9,
            "repeat_penalty" => 1.05
        ]
    ]);

    $ch = curl_init("http://127.0.0.1:11434/api/chat");

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,   // <- viktig: vi vil ha hele svaret som string
        CURLOPT_TIMEOUT        => 0,
    ]);

    $rawResponse = curl_exec($ch);

    if ($rawResponse === false) {
        $errorMsg = curl_error($ch);
        curl_close($ch);

        // Send feilmelding til klienten
        echo "data: Det oppstod en feil mot Ollama: $errorMsg\n\n";
        @ob_flush(); flush();
        return;
    }

    curl_close($ch);

    // Ollama svarer med ett JSON-objekt når stream = false
    $json = json_decode($rawResponse, true);

    if (!is_array($json) || empty($json['message']['content'])) {
        echo "data: Jeg klarte ikke å tolke svaret fra modellen.\n\n";
        @ob_flush(); flush();
        return;
    }

    $answer = $json['message']['content'];

    // Litt enkel språk/whitespace-rydding (kan utvides senere)
    $answer = cleanWhitespace($answer);

    // Send som ett SSE-event
    echo "data: " . $answer . "\n\n";
    @ob_flush(); flush();
}
?>
