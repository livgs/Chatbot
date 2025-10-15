<?php
function askOllama($prompt) {
    $payload = json_encode([
        'model' => 'llama3',
        'messages' => [['role' => 'user', 'content' => $prompt]]
    ]);

    $ch = curl_init('http://localhost:11434/api/chat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['message']['content'] ?? 'Ingen respons fra Ollama.';
}
?>