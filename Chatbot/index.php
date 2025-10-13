<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Min Chatbot</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 30px;
        }
        #chatBox {
            width: 400px;
            height: 400px;
            border: 1px solid #ccc;
            padding: 10px;
            overflow-y: auto;
            margin-bottom: 10px;
        }
        .msg {
            margin: 6px 0;
            padding: 8px 12px;
            border-radius: 10px;
            max-width: 80%;
        }
        .user-msg {
            background-color: #d1f1ff;
            text-align: right;
        }
        .bot-msg {
            background-color: #f1f1f1;
            text-align: left;
        }
    </style>
</head>
<body>
<h2>Velkommen til vår astronomi-chatbot!</h2>

<div id="chatBox"></div>
    <label for="input">Velg tema:</label>
    <input type="text" id="input" placeholder="skriv melding...">
<button id="sendBtn">Send</button>

<script src="script.js"></script>
</body>
</html>