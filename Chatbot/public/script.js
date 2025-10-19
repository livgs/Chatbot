document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById("chatBox");
    const input = document.getElementById("input");
    const sendBtn = document.getElementById("sendBtn");

    // Vis velkomstmelding
    addMessage(
        "Hei! Dette er en chatbot med astronomiske tema. <br>Velg mellom: <b>planeter</b>, <b>stjernetegn</b>, <b>galakser</b> eller <b>sorte hull</b>",
        "bot"
    );

    input.addEventListener("keypress", async (e) => {
        if (e.key === "Enter") await sendMessage();
    });

    sendBtn.addEventListener("click", async () => await sendMessage());

    function addMessage(text, sender) {
        const div = document.createElement("div");
        div.className = sender === "user" ? "msg user-msg" : "msg bot-msg";

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

        addMessage(text, "user");
        input.value = "";

        const div = document.createElement("div");
        div.className = "msg bot-msg";
        div.textContent = "";
        chatBox.appendChild(div);

        // Stream via EventSource
        const evtSource = new EventSource(`chat.php?message=${encodeURIComponent(text)}`);

        evtSource.onmessage = function(event) {
            let text = event.data.trim();

            // Legg til mellomrom hvis nødvendig
            if (!/[.?!]\s*$/.test(div.textContent.slice(-1)) && !/^[,.:!?]/.test(text)) {
                div.textContent += " ";
            }

            div.textContent += text;
            chatBox.scrollTop = chatBox.scrollHeight;
        };


        evtSource.onerror = function() {
            evtSource.close();
        };
    }
});
