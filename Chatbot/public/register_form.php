<?php
// Setter standardverdier hvis variabelen ikke allerede finnes
$email = $email ?? "";
$first_name = $first_name  ?? "";
$last_name = $last_name ?? "";
$errors = $errors ?? [];
?>

<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Registrer bruker</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="header-row">
    <button class="btn btn-return" onclick="window.location.href='index.php'"> &larr; Tilbake til chatbot</button>
    <h2>Registrer ny bruker</h2>
</div>

<div class="register">

// Feilmeldinger
<?php if (!empty($errors)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $m): ?>
                <li><?= htmlspecialchars($m) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="register.php" method="post">
    <div class="form-row">
    <label>E-post:</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>">
    </div>

    <div class="form-row">
    <label>Fornavn:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>">
    </div>

    <div class="form-row">
    <label>Etternavn:</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>">
    </div>

    <div class="form-row">
    <label>Passord:</label>
        <input type="password" name="password" required>
    </div>

    <div class="form-row">
    <label>Gjenta passord:</label>
        <input type="password" name="password_confirm" required>
    </div>

    <button class="register-btn" type="submit">Registrer</button>
</form>
</div>
</body>
</html>