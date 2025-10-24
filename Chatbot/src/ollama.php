<?php
function askOllamaStream(string $userMessage = '', float $temperature = 0.3): void
{
    if (empty($userMessage)) return;

    if (!headers_sent()) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
    }
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);

    // ——— Stilguide + few-shot eksempel (styrer tone og ordvalg) ———
    $messages = [
        [
            "role" => "system",
            "content" =>
                "Du skriver KUN på korrekt norsk bokmål. Ikke bland inn engelsk eller dansk.\n" .
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

        // Few-shot: viser modellen nøyaktig stilen vi vil ha
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

        // Faktisk brukerforespørsel
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ];

    // ——— Payload med temperatur ———
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
    $pendingSpaceAtNextChunk = false;

    // 1) Fiks mellomrom etter . ! ? (og rydd doble mellomrom)
    $fix_spacing = static function (string $s): string {
        // Legg til mellomrom etter punktum, utropstegn og spørsmålstegn hvis neste tegn ikke er mellomrom
        $s = preg_replace('/([.!?])(?=[^\s])/u', '$1 ', $s);
        // Samle opp dobbelte mellomrom
        $s = preg_replace('/ {2,}/', ' ', $s);
        return $s;
    };

    // 2) Lett språk-normalisering (vanlige norwenglish/dansismer)
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

    // Kjede etterbehandling: spacing -> normalisering -> spacing igjen (i tilfelle endringer lager nye grenser)
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
                $line = trim($line);
                if ($line === '') continue;

                $json = json_decode($line, true);
                if ($json === null) continue;

                if (isset($json['message']['content'])) {
                    $chunk = $json['message']['content'];

                    // Hvis forrige chunk endte med punktum, legg til mellomrom foran ny
                    if ($pendingSpaceAtNextChunk && $chunk !== '' && !preg_match('/^\s/u', $chunk)) {
                        $chunk = ' ' . $chunk;
                    }
                    $pendingSpaceAtNextChunk = false;

                    $bufferedText .= $chunk;
                    $bufferedText = $fix_spacing($bufferedText);

                    // Hvis chunken ender på punktum/utrop/spørsmål, husk at neste må starte med mellomrom
                    if (preg_match('/[.!?]$/u', rtrim($chunk))) {
                        $pendingSpaceAtNextChunk = true;
                    }

                    // Flush når passende
                    if (preg_match('/[\s.,!?]$/u', $chunk)) {
                        echo "data: " . $bufferedText . "\n\n";
                        @ob_flush(); flush();
                        $bufferedText = '';
                    }
                }

                if (!empty($json['done'])) {
                    if ($bufferedText !== '') {
                        echo "data: " . $post_process($bufferedText) . "\n\n";
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
