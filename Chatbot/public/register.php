<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

// 1) Henter data fra skjema
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

// 2) Validering

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ugyldig e-postadresse.';
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Passord må være minst 8 tegn.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Passordene er ikke like.';
    }

    if (!empty($errors)) {
        include 'register_form.php';
        exit;
    }

    try {
        $pdo = get_db_connection();

        // 3) Sjekker om e-posten allerede fins
        $checkUser = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $checkUser->execute([':email' => $email]);
        if ($checkUser->fetch()) {
            $errors[] = "En bruker med denne e-posten finnes allerede.";
            include "register_form.php";
            exit;
        }

        // 4) Lager hash av passordet
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 5) Setter det inn i databasen
        $insertUser = $pdo->prepare('
        INSERT INTO users (email, first_name, last_name, password_hash)
        VALUES (:email, :first_name, :last_name, :password_hash)
    ');

        $insertUser->execute([
            ':email' => $email,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':password_hash' => $hash,
        ]);

        $_SESSION['innlogget'] = [
            'id' => $pdo->lastInsertId(),
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ];

        $_SESSION['popup'] = [
            'type' => 'success',
            'message' => "Registrering vellykket! Du er nå registrert som $first_name."
        ];
        header('Location: index.php');
        exit;

    } catch (PDOException $e) {
        $errors[] = "Noe gikk galt. Prøv igjen senere.";
        include 'register_form.php';
        exit;
    }
} else {
    // GET-request viser skjema
    include 'register_form.php';
}