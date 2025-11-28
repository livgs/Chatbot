<?php
require_once __DIR__ . '/../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

// 1) Henter data fra skjema
$email           = trim($_POST['email'] ?? '');
$firstName       = trim($_POST['first_name'] ?? '');
$lastName        = trim($_POST['last_name'] ?? '');
$password        = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

// 2) Validering
$errors = [];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ugyldig e-postadresse.';
}

if ($password === '' || strlen($password) < 8) {
    $errors[] = 'Passord må være minst 8 tegn.';
}

if ($password !== $passwordConfirm) {
    $errors[] = 'Passordene er ikke like.';
}

if (!empty($errors)) {
    echo "<h1>Feil ved registrering</h1><ul>";
    foreach ($errors as $e) {
        echo "<li>" . htmlspecialchars($e) . "</li>";
    }
    echo "</ul><a href=\"register.html\">Tilbake</a>";
    exit;
}

try {
    $pdo = get_db_connection();

    // 3) Sjekker om e-posten allerede fins
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo "<p>En bruker med denne e-posten finnes allerede.</p>";
        echo '<a href="register.html">Tilbake</a>';
        exit;
    }

    // 4) Lager passord hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 5) Setter det inn i databasen
    $insert = $pdo->prepare('
        INSERT INTO users (email, first_name, last_name, password_hash)
        VALUES (:email, :first_name, :last_name, :password_hash)
    ');

    $insert->execute([
        ':email'         => $email,
        ':first_name'    => $firstName,
        ':last_name'     => $lastName,
        ':password_hash' => $hash,
    ]);

    echo "<h1>Registrering vellykket!</h1>";
    echo "<p>Du kan nå logge inn.</p>";

} catch (PDOException $e) {
    // Feilhåndtering
    echo "<h1>Noe gikk galt</h1>";
    echo "<p>Kunne ikke lagre brukeren. Prøv igjen senere.</p>";
}

