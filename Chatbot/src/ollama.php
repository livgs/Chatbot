<?php
// Hjelpefunksjon: rydder opp i whitespace
function cleanWhitespace(string $text): string {
    // Erstatter all whitespace (linjeskift, tab osv.) med ett enkelt mellomrom
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function askOllamaStream(string $userMessage = '', float $temperature = 0.15): void
{
    if ($userMessage === '') {
        return;
    }

    // Sørg for at output kan streames til EventSource
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);

    // Stilguide + strengere faktakrav og domenebegrensning
    $messages = [
        [
            "role" => "system",
            "content" =>
                "Du skriver KUN på korrekt norsk bokmål. Ikke bland inn engelsk, dansk eller andre språk.\n" .
                "Hold språket nøytralt, presist og kort (helst 2–4 setninger).\n" .
                "Bruk norske fagtermer. Eksempler: 'nettleser' (ikke 'browser'), 'ytelse' (ikke 'performance'), " .
                "'funksjon' (ikke 'feature'), 'forespørsel' (ikke 'request').\n" .
                "Skriv 'NASAs', ikke 'NASA's'. Skriv 'oppdrag' eller 'ferd', ikke 'mission'.\n" .
                "Sett mellomrom etter punktum, spørsmålstegn og utropstegn.\n" .
                "Hvis du ikke er rimelig sikker på et faktasvar, skal du si at du ikke er sikker og unngå å gjette."
        ],
        [
            "role" => "system",
            "content" =>
                "Du er en STRENG astronomi-assistent.\n" .
                "- Du svarer bare på spørsmål som handler om astronomi, astrofysikk, solsystemet, stjerner, galakser, " .
                "kosmologi eller romfart (for eksempel NASA, ESA, romsonder, raketter).\n" .
                "- Hvis spørsmålet ikke gjelder astronomi eller romfart, skal du svare nøyaktig: " .
                "\"Det veit jeg ikke, jeg er bare en liten astronomibot\".\n" .
                "- Hvis spørsmålet delvis handler om astronomi og delvis noe annet, svarer du KUN på den astronomiske delen " .
                "og ignorerer resten.\n" .
                "- Hvis du er usikker på detaljer (for eksempel tallverdier, årstall eller eksakte avstander), " .
                "skal du si at du ikke er helt sikker i stedet for å finne på noe."
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

    // Payload til Ollama – mer konservative innstillinger
    $payload = json_encode([
        "model"    => "llama3",
        "stream"   => false,
        "messages" => $messages,
        "options"  => [
            // lav temperatur = mindre kreativitet → mindre risiko for å finne på ting
            "temperature"    => $temperature,
            "top_p"          => 0.7,
            "repeat_penalty" => 1.05
        ]
    ]);

    $ch = curl_init("http://127.0.0.1:11434/api/chat");

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,   // <- vi vil ha hele svaret som string
        CURLOPT_TIMEOUT        => 0,
    ]);

    $rawResponse = curl_exec($ch);

    if ($rawResponse === false) {
        $errorMsg = curl_error($ch);
        curl_close($ch);

        echo "data: Det oppstod en feil mot Ollama: $errorMsg\n\n";
        @ob_flush(); flush();
        return;
    }

    curl_close($ch);

    $json = json_decode($rawResponse, true);

    if (!is_array($json) || empty($json['message']['content'])) {
        echo "data: Jeg klarte ikke å tolke svaret fra modellen.\n\n";
        @ob_flush(); flush();
        return;
    }

    $answer = $json['message']['content'];

    // Enkel språk/whitespace-rydding
    $answer = cleanWhitespace($answer);

    echo "data: " . $answer . "\n\n";
    @ob_flush(); flush();
}
?>
