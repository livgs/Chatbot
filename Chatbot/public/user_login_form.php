<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Logg inn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header-row">
<button class="btn btn-return" onclick="window.location.href='index.php'">Tilbake til chatbot</button>
<h2>Logg inn</h2>
</div>


<?php if (!empty($melding)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($melding as $m): ?>
                <li><?= htmlspecialchars($m) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="login">
    <form method="POST" action="user_login.php">
        <div class="form-row">
            <label for="Epost">Epost:</label>
            <input type="text" name="email" id="Epost">
        </div>

        <div class="form-row">
            <label for="Passord">Passord:</label>
            <input type="password" name="password" id="Passord">
        </div>

        <button type="submit" name="login" class="login-btn">Logg inn</button>
    </form>
</div>
</body>
</html>
