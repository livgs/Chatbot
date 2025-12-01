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

    // Sørg for at output kan streames
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

    // Payload med temperatur
    $payload = json_encode([
        "model"    => "llama3",
        "stream"   => true,
        "messages" => $messages,
        "options"  => [
            "temperature"    => $temperature,
            "top_p"          => 0.9,
            "repeat_penalty" => 1.05
        ]
    ]);

    $ch = curl_init("http://127.0.0.1:11434/api/chat");

    $bufferedText = '';

    // 1) Fikser mellomrom etter . ! ? (og rydder doble mellomrom)
    $fixSpacing = static function (string $s): string {
        // Legger til mellomrom etter punktum, utropstegn og spørsmålstegn hvis neste tegn ikke er mellomrom
        $s = preg_replace('/([.!?])(?=[^\s])/u', '$1 ', $s);
        // Samler opp dobbelte mellomrom
        $s = preg_replace('/ {2,}/', ' ', $s);
        return $s;
    };

    // 2) Språk-normalisering
    $normalizeNorwegian = static function (string $s): string {
        $replacements = [
            "/\\bNASA's\\b/u"                        => 'NASAs',
            "/\\bApollo\\s*11[- ]mission(en)?\\b/ui" => 'Apollo 11-oppdraget',
            "/\\bmission(en|er)?\\b/ui"             => 'oppdrag',
            "/\\bmånelandingprogram\\b/ui"         => 'månelandingsprogram',
            "/\\bmånelanding(en)?\\b/ui"           => 'månelanding',
            "/gå ut på månens overflate/ui"        => 'gå på månen',
            "/\\bfeature(s)?\\b/ui"                => 'funksjon',
            "/\\bperformance\\b/ui"                => 'ytelse',
            "/\\brequest(s)?\\b/ui"                => 'forespørsel',
        ];
        foreach ($replacements as $pattern => $replacement) {
            $s = preg_replace($pattern, $replacement, $s);
        }
        return $s;
    };

    $postProcessText = static function (string $s) use ($fixSpacing, $normalizeNorwegian): string {
        return $fixSpacing($normalizeNorwegian($fixSpacing($s)));
    };

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($fixSpacing, &$bufferedText, $postProcessText) {
            $responseLines = explode("\n", $data);

            foreach ($responseLines as $responseLine) {
                $responseLine = trim($responseLine);
                if ($responseLine === '') {
                    continue;
                }

                $json = json_decode($responseLine, true);
                if (!is_array($json)) {
                    continue;
                }

                if (isset($json['message']['content'])) {
                    $chunk = $json['message']['content'];

                    // veldig enkel variant: send hver chunk rett ut
                    $chunk = $fixSpacing($chunk);
                    $chunk = cleanWhitespace($chunk);

                    if ($chunk !== '') {
                        echo "data: " . $chunk . "\n\n";
                        @ob_flush();
                        flush();
                    }
                }


                if (!empty($json['done'])) {
                    if ($bufferedText !== '') {
                        $out = $postProcessText($bufferedText);
                        $out = cleanWhitespace($out);

                        echo "data: " . $out . "\n\n";
                        $bufferedText = '';
                        @ob_flush();
                        flush();
                    }
                }
            }

            return strlen($data);
        },
    ]);

    curl_exec($ch);
    curl_close($ch);
}
?>
