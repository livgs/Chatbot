<?php
// Hjelpefunksjon
function normalize_spaces(string $s): string {
    // Erstatter all whitespace med ett enkelt mellomrom
    return preg_replace('/\s+/u', ' ', $s);
}
function askOllamaStream(string $userMessage = '', float $temperature = 0.3): void
{
    if (empty($userMessage)) return;

    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);

    // Stilguide
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

        // Viser modellen hvilken stil vi vil ha
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

        // Brukerforespørsel
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ];

    // Payload med temperatur
    $payload = json_encode([
        "model" => "llama3",
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
    $pendingSpaceAtNextChunk = false;

    // 1) Fikser mellomrom etter . ! ? (og rydder doble mellomrom)
    $fix_spacing = static function (string $s): string {
        // Legger til mellomrom etter punktum, utropstegn og spørsmålstegn hvis neste tegn ikke er mellomrom
        $s = preg_replace('/([.!?])(?=[^\s])/u', '$1 ', $s);
        // Samler opp dobbelte mellomrom
        $s = preg_replace('/ {2,}/', ' ', $s);
        return $s;
    };

    // 2) Språk-normalisering
    $normalize_nb = static function (string $s): string {
        $replacements = [
            // apostrofgenitiv
            "/\\bNASA's\\b/u"                     => 'NASAs',

            // mission -> oppdrag / spesialtilfeller
            "/\\bApollo\\s*11[- ]mission(en)?\\b/ui" => 'Apollo 11-oppdraget',
            "/\\bmission(en|er)?\\b/ui"           => 'oppdrag',

            // sammensetninger/ordvalg
            "/\\bmånelandingprogram\\b/ui"       => 'månelandingsprogram',
            "/\\bmånelanding(en)?\\b/ui"         => 'månelanding',

            // ‘gå ut på månens overflate’ -> ‘gå på månen’
            "/gå ut på månens overflate/ui"      => 'gå på månen',

            // små engelske låneord som ofte sniker seg inn
            "/\\bfeature(s)?\\b/ui"              => 'funksjon',
            "/\\bperformance\\b/ui"              => 'ytelse',
            "/\\brequest(s)?\\b/ui"              => 'forespørsel',
        ];
        foreach ($replacements as $pat => $rep) {
            $s = preg_replace($pat, $rep, $s);
        }
        return $s;
    };

    $post_process = static function (string $s) use ($fix_spacing, $normalize_nb): string {
        return $fix_spacing($normalize_nb($fix_spacing($s)));
    };

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($fix_spacing, &$bufferedText, &$pendingSpaceAtNextChunk, $post_process) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $line = rtrim($line, "\r\n");
                if ($line === '') continue;

                $json = json_decode($line, true);
                if ($json === null) continue;

                if (isset($json['message']['content'])) {
                    $chunk = $json['message']['content'];

                    // Hvis forrige chunk endte med punktum, legg til mellomrom foran den nye
                    if ($pendingSpaceAtNextChunk && $chunk !== '' && !preg_match('/^\s/u', $chunk)) {
                        $chunk = ' ' . $chunk;
                    }
                    $pendingSpaceAtNextChunk = false;

                    // Rydder opp dobbelte mellomrom
                    $chunk = $fix_spacing($chunk);

                    // Oppdater buffer for intern logikk
                    $bufferedText .= $chunk;

                    // Kjører fix_spacing på hele bufferet før sending
                    $bufferedText = $fix_spacing($bufferedText);

                    if (preg_match('/[.!?]$/u', rtrim($bufferedText))) {
                        echo "data: " . $bufferedText . "\n\n";
                        $bufferedText = '';
                        @ob_flush(); flush();
                    }

                }

                if (!empty($json['done'])) {
                    if ($bufferedText !== '') {
                        $bufferedText = normalize_spaces($bufferedText);
                        echo "data: " . $bufferedText . "\n\n";
                        @ob_flush();
                        flush();
                        $bufferedText = '';
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
