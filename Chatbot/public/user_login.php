<?php
require_once __DIR__ . '/../src/db.php'; // samme db-tilkobling som register.php
session_start();

$melding = [];
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $melding[] = "Vennligst fyll inn e-post og passord.";
    } else {
        try {
            $pdo = get_db_connection();
            // Hent bruker fra DB
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $bruker = $stmt->fetch();

            if ($bruker && password_verify($password, $bruker['password_hash'])) {
                // Sett innlogget bruker i session
                $_SESSION['innlogget'] = [
                    'id' => $bruker['id'],
                    'email' => $bruker['email'],
                    'first_name' => $bruker['first_name'],
                    'last_name' => $bruker['last_name']
                ];

                // Popup-melding på index
                $_SESSION['popup'] = [
                    'type' => 'success',
                    'message' => "Du er nå logget inn som {$bruker['first_name']}."
                ];

                header("Location: index.php");
                exit;
            } else {
                $melding[] = "Feil e-post eller passord.";
            }
        } catch (PDOException $e) {
            $melding[] = "Noe gikk galt. Prøv igjen senere.";
        }
    }
}

// Håndterer visning: inkluder HTML
include 'user_login_form.php';

?>