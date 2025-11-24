import { GoogleGenerativeAI } from "@google/generative-ai";


const ciaBtn = document.getElementById("ciaBtn");
ciaBtn.style.background = usedColor;

const marqueeoutput = document.querySelector(".prompt-text");
const subtitleText = document.getElementById("subtitle-text");
const soundWaveImage = document.querySelector("#ciawaves");
subtitleText.innerHTML = "";
let currentText = "";
let textIndex = 0;
function updateSubtitle(fullText) {
    const words = fullText.split(" ");
    const visibleWords = words.slice(textIndex, textIndex + 4).join(" ");
    subtitleText.textContent = visibleWords;
    textIndex += 4;
    if (textIndex >= words.length) {
        textIndex = 0;
    }
}
const recognition = new (window.SpeechRecognition ||
    window.webkitSpeechRecognition ||
    window.mozSpeechRecognition ||
    window.msSpeechRecognition)();
let isListeningForCommand = false;
recognition.continuous = true;
recognition.interimResults = true;
let silenceTimeout;
ciaBtn.addEventListener("click", function () {
    recognition.start();
});
recognition.onstart = () => {
    // startButton.textContent = 'Listening...';
    soundWaveImage.classList.remove("dis-no");
    resetSilenceTimeout();
};

recognition.onresult = (event) => {
    let transcript = Array.from(event.results)
        .map((result) => result[0].transcript)
        .join(" ");
    const fullText = transcript;
    clearInterval(window.subtitleInterval);
    window.subtitleInterval = setInterval(() => updateSubtitle(fullText), 500);
    isListeningForCommand = true;
    resetSilenceTimeout(transcript);
};


recognition.onend = () => {
    // startButton.textContent = 'Start Voice Input';
    soundWaveImage.classList.add("dis-no");
    subtitleText.classList.add("dis-no");
    clearTimeout(silenceTimeout);
};

function resetSilenceTimeout(transcript) {
    clearTimeout(silenceTimeout);
    silenceTimeout = setTimeout(() => {
        recognition.stop();
        if (isListeningForCommand) {
            // generateSQLFromCommand(transcript);
            executeSQLQuery(transcript);
        }
        isListeningForCommand = false;
    }, 5000);
}

// function executeSQLQuery(query) {
//     const csrfToken = document
//         .querySelector('meta[name="csrf-token"]')
//         .getAttribute("content");
//     const aiAssistantUrl = document.querySelector(
//         'input[name="ciaassistanturl"]'
//     ).value;

//     fetch(aiAssistantUrl, {
//         method: "POST",
//         headers: {
//             "Content-Type": "application/json",
//             "X-CSRF-TOKEN": csrfToken,
//         },
//         body: JSON.stringify({
//             prompt: query,
//         }),
//     })
//         .then((response) => {
//             if (!response.ok) {
//                 throw new Error("Query not executed. Try again.");
//             }
//             return response.json();
//         })
//         .then((data) => {
//             console.log("Query Execution Result:", data);

//             if (data.status == "success" && data.redirectUrl) {
//                 window.location.href = data.redirectUrl;
//             } else {
//                 showPopup("No redirection URL provided.", "error");
//             }
//         })
//         .catch((error) => {
//             console.error("Error executing SQL query:", error);
//             showPopup(
//                 error.message || "Action Not Performed. Please try again",
//                 "error"
//             );
//         });
// }
let isProcessing = false;
let processingTimeout = null;
function executeSQLQuery(query) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    const aiAssistantUrl = document.querySelector(
        'input[name="ciaassistanturl"]'
    ).value;
    getShortenedFeedback(query).then((shortFeedback) => {
        speakText(shortFeedback);
    });
    fetch(aiAssistantUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            prompt: query,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Query not executed. Try again.");
            }
            if (!isProcessing) {
                isProcessing = true;
                speakText("Processing, please wait...");
                startProcessingTimer();
            }
            return response.json();
        })
        .then((data) => {
            if (data.status === "success" && data.redirectUrl) {
                window.location.href = data.redirectUrl;
            } else {
                showPopup("No redirection URL provided.", "error");
            }
        })
        .catch((error) => {
            console.error("Error executing SQL query:", error);
            speakText("An error occurred while processing your request.");
            showPopup(
                error.message || "Action Not Performed. Please try again",
                "error"
            );
        })
        .finally(() => {
            clearTimeout(processingTimeout);
            isProcessing = false;
        });
}
function startProcessingTimer() {
    processingTimeout = setTimeout(() => {
        if (isProcessing) {
            speakText("Processing, please wait...");
            startProcessingTimer();  
        }
    }, 2000); 
}
function getShortenedFeedback(prompt) {
    const GEMINI_API_KEY = 'AIzaSyDDNLeZnLDUAW8cHF4kGE4yT9RLHkJpF_4';
    const geminiApiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`;
    const requestData = {
        contents: [
            {
                parts: [
                    {
                        text: `Summarize this into short sentence for speech like Siri respond "opening YouTube" : "${prompt}"`,
                    },
                ],
            },
        ],
    };

    return fetch(geminiApiUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(requestData),
    })
        .then((response) => response.json())
        .then((data) => {
            const extractedText = data.candidates[0].content.parts[0].text.trim();
            console.log(extractedText);
            return extractedText;
        })
        .catch((error) => {
            console.error("Error generating shortened feedback:", error);
            return "Processing your request."; 
        });
}

function speakText(text) {
    const speech = new SpeechSynthesisUtterance();
    speech.text = text;
    speech.lang = "en-US";
    window.speechSynthesis.speak(speech);
}

function showPopup(message, type) {
    // Create a popup element
    const popup = document.createElement("div");
    popup.className = `popup ${type}`; // Add a class based on the type (success or error)

    // Style the popup
    Object.assign(popup.style, {
        position: "fixed",
        top: "15%",
        right: "30%",
        padding: "20px",
        borderRadius: "8px",
        color: "#333",
        backgroundColor: "#fff",
        fontSize: "16px",
        zIndex: 1000,
        boxShadow: "0 4px 8px rgba(0,0,0,0.1)",
        display: "flex",
        alignItems: "center",
        gap: "10px",
        minWidth: "250px",
    });

    // Create an icon container
    const iconContainer = document.createElement("div");
    Object.assign(iconContainer.style, {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        width: "40px",
        height: "40px",
        borderRadius: "50%",
        backgroundColor: type === "success" ? "green" : "red",
    });

    // Add the icon
    const icon = document.createElement("i");
    icon.className = type === "success" ? "fa fa-check" : "fa fa-times"; // Font Awesome icons
    icon.style.color = "#fff";
    icon.style.fontSize = "20px";
    iconContainer.appendChild(icon);

    // Create the message container
    const messageContainer = document.createElement("div");
    messageContainer.textContent = message;

    // Append the icon and message to the popup
    popup.appendChild(iconContainer);
    popup.appendChild(messageContainer);

    // Append the popup to the body
    document.body.appendChild(popup);

    setTimeout(() => {
        popup.remove();
    }, 5000);
}

// Include Font Awesome in your HTML
// Add this in your `<head>`:
// <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

// function executeSQLQuery(query) {
//     const csrfToken = document
//         .querySelector('meta[name="csrf-token"]')
//         .getAttribute("content");
//     const aiAssistantUrl = document.querySelector(
//         'input[name="ciaassistanturl"]'
//     ).value;
//     console.log(aiAssistantUrl);

//     fetch(aiAssistantUrl, {
//         method: "POST",
//         headers: {
//             "Content-Type": "application/json",
//             "X-CSRF-TOKEN": csrfToken,
//         },
//         body: JSON.stringify({
//             prompt: query,
//         }),
//     })
//         .then((response) => response.json())
//         .then((data) => {
//             console.log("Query Execution Result:", data);

//             // Create a table container
//             const tableContainer = document.getElementById("resultsTable");
//             tableContainer.innerHTML = ""; // Clear any previous content

//             // Check if data contains a prediction response
//             if (data.prediction) {
//                 // Display the prediction result as text
//                 const predictionContainer = document.createElement("div");
//                 predictionContainer.classList.add("prediction-result");
//                 predictionContainer.innerHTML = `<p><strong>Prediction:</strong> ${data.prediction}</p>`;
//                 tableContainer.appendChild(predictionContainer);
//             }
//             // Check if there are SQL results
//             else if (data.success && data.results.length > 0) {
//                 data.results.forEach((result) => {
//                     if (result.data && result.data.length > 0) {
//                         // Create table element
//                         const table = document.createElement("table");
//                         table.classList.add("table", "table-bordered");

//                         // Add table header
//                         const headerRow = document.createElement("tr");
//                         const headers = Object.keys(result.data[0]);
//                         headers.forEach((header) => {
//                             const th = document.createElement("th");
//                             th.innerText = header;
//                             headerRow.appendChild(th);
//                         });
//                         table.appendChild(headerRow);

//                         // Add table rows for each data entry
//                         result.data.forEach((row) => {
//                             const tableRow = document.createElement("tr");
//                             headers.forEach((header) => {
//                                 const td = document.createElement("td");
//                                 td.innerText = row[header];
//                                 tableRow.appendChild(td);
//                             });
//                             table.appendChild(tableRow);
//                         });

//                         // Append the table to the container
//                         tableContainer.appendChild(table);
//                     } else {
//                         tableContainer.innerHTML = "<p>No data found.</p>";
//                     }
//                 });
//             } else {
//                 tableContainer.innerHTML = "<p>No results to display.</p>";
//             }
//         })
//         .catch((error) => {
//             console.error("Error executing SQL query:", error);
//         });
// }

function readOut(message) {
    const speech = new SpeechSynthesisUtterance();
    // different voices
    const allVoices = speechSynthesis.getVoices();
    speech.text = message;
    speech.voice = allVoices[36];

    speech.volume = 1;
    window.speechSynthesis.speak(speech);
}
