<?php
function askOllamaStream(string $userMessage = ''): void
{
    if (empty($userMessage)) return;

    $messages = [
        [
            "role" => "system",
            "content" => "Svar som en vennlig astronomi-assistent som alltid svarer på norsk med korte, faktabaserte forklaringer."
        ],
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ];

    $payload = json_encode([
        "model" => "llama3",
        "stream" => true,
        "messages" => $messages
    ]);

    $ch = curl_init("http://127.0.0.1:11434/api/chat");
    $bufferedText = '';

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$bufferedText) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $json = json_decode(trim($line), true);
                if (!$json) continue;

                if (isset($json['message']['content'])) {
                    $chunk = $json['message']['content'];
                    $bufferedText .= $chunk;
                    if (preg_match('/[\s.,!?]$/u', $chunk)) {
                        echo "data: " . $bufferedText . "\n\n";
                        @ob_flush();
                        flush();
                        $bufferedText = '';
                    }
                }

                if (isset($json['done']) && $json['done'] === true) {
                    if (!empty($bufferedText)) {
                        echo "data: " . $bufferedText . "\n\n";
                        @ob_flush();
                        flush();
                        $bufferedText = '';
                    }
                    @ob_flush();
                    flush();
                }
            }
            return strlen($data);
        },
        CURLOPT_TIMEOUT => 0
    ]);

    curl_exec($ch);
    curl_close($ch);
}
?>
