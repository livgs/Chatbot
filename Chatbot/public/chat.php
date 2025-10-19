<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // for nginx og proxyer

// Deaktiver all output-buffering
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

require_once __DIR__ . '/../src/ollama.php';

$msg = $_GET['message'] ?? '';
if (!$msg) {
    echo "data: Du må skrive en melding først.\n\n";
    flush();
    exit;
}

// Kall Ollama-stream-funksjonen
askOllamaStream($msg);
?>