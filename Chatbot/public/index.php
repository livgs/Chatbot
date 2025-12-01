<?php session_start(); ?>
<?php if (isset($_SESSION['popup'])): ?>
    <div id="popup" style="
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: <?= $_SESSION['popup']['type'] === 'success' ? '#4CAF50' : '#f44336' ?>;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 16px;
            ">
        <?= $_SESSION['popup']['message'] ?>
    </div>
    <script>
        setTimeout(() => {
            const popup = document.getElementById('popup');
            if (popup) popup.style.display = 'none';
        }, 5000); // vises i 5 sekunder
    </script>
    <?php unset($_SESSION['popup']); ?>
<?php endif; ?>


<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Min Chatbot</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="page-container">

<h2 class="title-left">Velkommen til vår astronomi-chatbot!</h2>

<div id="chatBox"></div>
<div class="input-row">
    <input type="text" id="input" placeholder="Skriv melding...">
<button class="btn btn-submit" id="sendBtn">Send</button>
</div>

<div class="auth-btn">
    <?php if (!isset($_SESSION['innlogget'])): ?>
        <button class="btn auth-login-btn" onclick="window.location.href='user_login_form.php'">Logg inn<br>
        <button class="btn auth-register-btn" onclick="window.location.href='register_form.php'">Registrer deg<br>
    <?php else: ?>
        <button class="btn auth-logout-btn" onclick="window.location.href='logout.php'">
            Logg ut (<?= htmlspecialchars($_SESSION['innlogget']['first_name']) ?>)
        </button>
    <?php endif; ?>
</div>
</div>

<script src="script.js"></script>
</body>
</html>