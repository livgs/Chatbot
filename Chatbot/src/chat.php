<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

// Les JSON-data fra POST
$data = json_decode(file_get_contents("php://input"), true);
// Hent meldingen, eller tom streng om det ikke er noen melding
$msg = isset($data["message"]) ? trim($data["message"]) : "";

// Hvis meldingen er tom, send feilmelding
if ($msg === "") {
    echo json_encode([
        "reply" => "Du må skrive en melding først."
        ]);
    exit;
}

// Bestem tema (bruker match for klarhet)
$purpose = "Uvisst";

if (preg_match("/\b(hei|hallo|hei der)\b/u", $msg)) { //preg_match brukes for å sjekke om en tekst matcher et mønster
    $purpose = "hilsen";
} elseif (str_contains($msg, "planeter")) {
    $purpose = "planeter";
} elseif (str_contains($msg, "stjernetegn")) {
    $purpose = "stjernetegn";
} elseif (str_contains($msg, "galakser")) {
    $purpose = "galakser";
} elseif (str_contains($msg, "sorte hull")) {
    $purpose = "sorte hull";
}

// Bruker match for å bestemme svaret
$reply = match($purpose) {
    "hilsen" => "Hei! Velkommen til astronomi-chatboten. Hvilke tema ønsker du mer informasjon om?",
    "planeter" => "Vi har 8 planeter i soslystemet vårt: Merkur, Venus, Jorda, Mars, Saturn, Uranus, Jupiter, Neptun",
    "stjernetegn" => "Det finnes 12 stjernetegn i den vestlige astrologien: Væren, Tyren, Tvillingene, Krepsen, Løven, Jomfruen, Vekten, Skorpionen, Skytten, Steinbukken, Vannmannen og Fiskene",
    "galakser" => "Det finnes sannsynligvis mellom 100 milliarder og 200 milliarder galakser i universet. Solsystemet vårt befinner seg i Galaksen kalt Melkeveien.",
    "sorte hull" => "Et sort hull er et område med en sterk krumning av tidrommet definert ved tilstedeværelsen av, men ikke begrenset til, en hendelseshorisont. Innenfor denne horisonten er gravitasjonen så sterk at ingenting kan unnslippe.",
    default => "Beklager, jeg forstod ikke. Kan du prøve igjen?",
};

// Send tilbake svar
echo json_encode([
    "reply" => $reply
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Passer på at Unicode
