document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById("chatBox");
    const input   = document.getElementById("input");
    const sendBtn = document.getElementById("sendBtn");

    let historyLoaded = false;

    function addMessage(text, sender) {
        const div = document.createElement("div");
        div.className = sender === "user" ? "msg user-msg" : "msg bot-msg";

        // Bot kan ha HTML, bruker skal vises som ren tekst
        if (sender === "bot") {
            div.innerHTML = text;
        } else {
            div.textContent = text;
        }

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // 1) Last tidligere meldinger fra serveren
    fetch("load_history.php")
        .then((res) => res.json())
        .then((history) => {
            if (Array.isArray(history) && history.length > 0) {
                historyLoaded = true;

                history.forEach((msg) => {
                    // msg.role er 'user' eller 'bot' fra databasen
                    addMessage(msg.text, msg.role);
                });
            }

            // Hvis ingen historikk: vis velkomstmelding
            if (!historyLoaded) {
                addMessage(
                    "Hei! Dette er en chatbot med astronomiske tema. <br><b>Spør i vei!</b>",
                    "bot"
                );
            }
        })
        .catch(() => {
            // Hvis noe feiler: bare vis velkomstmelding
            addMessage(
                "Hei! Dette er en chatbot med astronomiske tema. <br><b>Spør i vei!</b>",
                "bot"
            );
        });

    // 2) Vanlig chat-funksjonalitet
    input.addEventListener("keypress", async (e) => {
        if (e.key === "Enter") await sendMessage();
    });

    sendBtn.addEventListener("click", async () => await sendMessage());

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        // Vis brukerens melding
        addMessage(text, "user");
        input.value = "";

        // Bot-boble med ... mens chatboten tenker
        const div = document.createElement("div");
        div.className = "msg bot-msg typing";
        div.innerHTML = `<span class="typing-dots">
                    <span>.</span><span>.</span><span>.</span>
                 </span>`;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;

        let receivedFirstChunk = false;

        // Stream via EventSource
        const evtSource = new EventSource(
            `chat.php?message=${encodeURIComponent(text)}`
        );

        evtSource.onmessage = function (event) {
            const chunk = event.data;

            // Første chunk: fjern "typing" og start tekst
            if (!receivedFirstChunk) {
                div.classList.remove("typing");
                div.textContent = "";
                receivedFirstChunk = true;
            } else {
                // Sett mellomrom mellom chunks ved behov
                if (
                    div.textContent &&
                    !div.textContent.endsWith(" ") &&
                    !chunk.startsWith(" ")
                ) {
                    div.textContent += " ";
                }
            }

            div.textContent += chunk;
            chatBox.scrollTop = chatBox.scrollHeight;
        };

        // Håndter EventSource-feil, og lukk forbindelsen
        evtSource.onerror = function () {
            evtSource.close();
        };
    }
});
