document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById("chatBox");
    const input = document.getElementById("input");
    const sendBtn = document.getElementById("sendBtn");

    // Vis velkomstmelding
    addMessage(
        "Hei! Dette er en chatbot med astronomiske tema. <br>Spør i vei!</b>",
        "bot"
    );

    input.addEventListener("keypress", async (e) => {
        if (e.key === "Enter") await sendMessage();
    });

    sendBtn.addEventListener("click", async () => await sendMessage());

    function addMessage(text, sender) {
        const div = document.createElement("div");
        div.className = sender === "user" ? "msg user-msg" : "msg bot-msg";

        // Bruk innerHTML for boten (for evt. HTML i meldingen), textContent for brukeren
        if (sender === "bot") {
            div.innerHTML = text;
        } else {
            div.textContent = text;
        }

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        // Vis brukerens melding
        addMessage(text, "user");
        input.value = "";

        // Bot-boble med ... mens chatbot-en tenker
        const div = document.createElement("div");
        div.className = "msg bot-msg typing";
        div.innerHTML = `<span class="typing-dots">
                    <span>.</span><span>.</span><span>.</span>
                 </span>`;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;

        let receivedFirstChunk = false;

        // Stream via EventSource
        const evtSource = new EventSource(`chat.php?message=${encodeURIComponent(text)}`);

        evtSource.onmessage = function(event) {
            const text = event.data;

            // Når første del av meldingen mottas, fjernes "typing" og starter å vise tekst
            if (!receivedFirstChunk) {
                div.classList.remove("typing");
                div.textContent = "";
                receivedFirstChunk = true;
            } else {
                // Mellomrom mellom chunks
                if (div.textContent && !div.textContent.endsWith(' ') && !text.startsWith(' ')) {
                    div.textContent += ' ';
                }
            }

            div.textContent += text;
            chatBox.scrollTop = chatBox.scrollHeight;
        };

        // Håndter EventSource-feil, og lukk forbindelsen
        evtSource.onerror = function() {
            evtSource.close();
        };
    }
});
