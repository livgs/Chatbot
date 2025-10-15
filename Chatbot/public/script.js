document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById("chatBox");
    const input = document.getElementById("input");
    const sendBtn = document.getElementById("sendBtn");

    // Vis introduksjon når siden lastes
    addMessage("Hei! Dette er en chatbot med astronomiske tema. <br>Velg mellom: <b>planeter</b>, <b>stjernetegn</b>, <b>galakser</b> eler <b>sorte hull</b>", "bot");

    //Send melding når man trykker enter
    input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    sendBtn.addEventListener("click", sendMessage);

    // Legger til en ny melding i chatten
    function addMessage(text, sender) {
        const div = document.createElement("div");
        div.className = sender === "user" ? "msg user-msg" : "msg bot-msg";

        if (sender === "bot") {
            div.innerHTML = text; // tolker <br>, <b> osv
        } else {
            div.textContent = text; // Brukerens tekst vises som ren tekst
        }

        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight; // scroller ned til den siste meldingen
    }

    // Sender melding til serveren (PHP-backend)
    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return; //stopper om feltet er tomt

        //viser brukerens melding i chatten
        addMessage(text, "user");
        input.value = "";

        // Midlertidig "..." mens man venter på svar
        addMessage("...", "bot");
        const loader = chatBox.lastChild;

        try {
            // Sender forespørsel til PHP-backend (chat.php)
            const response = await fetch("src/chat.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({message: text}) // sender meldingen som JSON
            });

            const data = await response.json(); // leser svaret fra serveren i JSON format
            loader.remove(); // fjerner "..." boblen

            // Vis botens svar, legg til linjeskift
            addMessage(data.reply.replace(/\n/g, "<br>"), "bot"); // viser svaret fra boten
        } catch (error) {
            loader.remove(); // fjerner boblen
            addMessage("Noe gikk galt med serveren", "bot"); // feilmelding
            console.error(error);
        }
    }
});
