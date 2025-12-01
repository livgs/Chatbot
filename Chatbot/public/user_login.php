<?php
require_once __DIR__ . '/../src/db.php'; // samme db-tilkobling som register.php
session_start();

$melding = [];
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    // Hent e-post og passord fra POST
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Enkel validering som sjekker om felt er tomme
    if (!$email || !$password) {
        $melding[] = "Vennligst fyll inn e-post og passord.";
    } else {
        try {
            $pdo = get_db_connection();

            // Hent bruker fra DB
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $bruker = $stmt->fetch();

            // Sjekker om bruker finnes og om passordet matcher hash i DB
            if ($bruker && password_verify($password, $bruker['password_hash'])) {

                // Sett innlogget bruker i session
                $_SESSION['innlogget'] = [
                    'id'         => $bruker['id_user'],
                    'email'      => $bruker['email'],
                    'first_name' => $bruker['first_name'],
                    'last_name'  => $bruker['last_name']
                ];

                // Popup-melding på index
                $_SESSION['popup'] = [
                    'type'    => 'success',
                    'message' => "Du er nå logget inn som {$bruker['first_name']}."
                ];

                // Sender brukeren til startsiden
                header("Location: index.php");
                exit;

            } else {
                $melding[] = "Feil e-post eller passord.";
            }

        } catch (PDOException $e) {
            // Logg teknisk feil, vis vennlig beskjed
            error_log('[user_login] DB-feil: ' . $e->getMessage());
            $melding[] = "Noe gikk galt. Prøv igjen senere.";
        }
    }
}

// Viser login-skjemaet: inkluder HTML
include 'user_login_form.php';
?>
