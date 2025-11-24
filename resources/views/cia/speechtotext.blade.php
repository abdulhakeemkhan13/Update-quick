@extends('layouts.admin')

@section('page-title')
    {{ __('Dynamic Reporting') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Workflow') }}</li>
@endsection
@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // const usedColor = getComputedStyle(document.body).getPropertyValue('--used-color').trim();
        // const usedColorLight = getComputedStyle(document.body).getPropertyValue('--used-color-light').trim();
        // const usedColorMedium = getComputedStyle(document.body).getPropertyValue('--used-color-medium').trim();
        // const usedColorDark = getComputedStyle(document.body).getPropertyValue('--used-color-dark').trim();
        // const usedColorDarker = getComputedStyle(document.body).getPropertyValue('--used-color-darker').trim();
        // const usedColorContrast = getComputedStyle(document.body).getPropertyValue('--used-color-contrast').trim();
    </script>

    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            .container {
                max-width: 100vw;
                margin: 0 auto;
                background: #fff;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            }

            h3 {
                font-size: 1.5rem;
                color: #555;
                text-align: center;
            }

            #promptInput {
                width: 100%;
                height: 80px;
                border-radius: 8px;
                padding: 10px;
                font-size: 1rem;
                margin-bottom: 15px;
            }

            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #6fd943, #469524);
                color: #fff;
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn:hover {
                transform: translateY(-5px);
            }

            #resultsTable {
                margin-top: 30px;
                text-align: center;
            }

            #output {
                margin-top: 20px;
                font-size: 1.2rem;
                font-weight: bold;
                color: #333;
                text-align: center;
            }

            .icon-box {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 20px;
                margin: 30px 0;
            }

            .icon-box i {
                font-size: 2rem;
            }

            .icon-box span {
                font-size: 1rem;
                font-weight: bold;
                color: #444;
            }

            .icon-box::before {
                display: none;
            }

            .custom-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                font-size: 16px;
                text-align: left;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .custom-table th,
            .custom-table td {
                border: 1px solid #ddd;
                padding: 10px;
            }

            .custom-table th {
                color: #fff;
            }

            .custom-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            .custom-table tr:hover {
                background-color: #f1f1f1;
            }

            .d-none {
                display: none !important;
            }
        </style>
    </head>
    <div class="container">
        <input type="text" id="promptInput" placeholder="Enter your prompt here..." />
        <br>
        <button class="btn" id="submitPromptButton"><i class="fas fa-paper-plane"></i> {{ __('Submit Prompt') }}</button>
        {{-- <br><br>
        <span style="font-size: 1.2rem; color: #555;">or</span>
        <br><br>
        <button class="btn" id="startButton"><i class="fas fa-microphone"></i> {{ __('Start Voice Input') }}</button> --}}
        <div id="output"></div>

        <div class="icon-box">
            <i class="fas fa-chart-bar"></i>
            <i class="fas fa-database"></i>
            <i class="fas fa-cogs"></i>
            <span>{{ __('Generate, Analyze, and Optimize your reports dynamically!') }}</span>
        </div>
        <div style="text-align: center; font-size:2rem; font-weight:800;"> Result </div>
        <button class="btn d-none" id="exportButton" style="float: right;"><i class="fas fa-file-excel"></i>
            {{ __('Export to Excel') }}</button><br>
        <div id="resultsTable" class="table-responsive"></div>

        {{-- <div id="result"></div> --}}
        <h3><i class="fas fa-exclamation-circle"></i>
            {{ __('This Assistant may make mistakes. Please verify important information.') }}</h3>

    </div>
    <div>
    </div>
    <script>
        document.getElementById('exportButton').addEventListener('click', function() {
            const table = document.querySelector('.custom-table');
            const workbook = XLSX.utils.table_to_book(table, {
                sheet: "Sheet1"
            });
            XLSX.writeFile(workbook, 'Dynamic_Report.xlsx');
        });
    </script>

    <script type="importmap">
        {
          "imports": {
            "@google/generative-ai": "https://esm.run/@google/generative-ai"
          }
        }
      </script>
    <script type="module">
        import {
            GoogleGenerativeAI
        } from "@google/generative-ai";
        document.querySelector('#promptInput').style.border = `2px solid ${usedColor}`;
        document.querySelectorAll('.icon-box i').forEach(function(icon) {
            icon.style.color = usedColor;
        });
        const startButton = document.getElementById('startButton');
        const outputDiv = document.getElementById('output');
        const promptInput = document.getElementById('promptInput');
        const submitPromptButton = document.getElementById('submitPromptButton');
        submitPromptButton.style.background = `linear-gradient(135deg, ${usedColorDarker}, ${usedColor})`;
        submitPromptButton.style.color = '#fff';
        const exportExcelbtn = document.getElementById('exportButton');
        exportExcelbtn.style.background = `linear-gradient(135deg, ${usedColorDarker}, ${usedColor})`;
        submitPromptButton.addEventListener('click', () => {
            const prompt = promptInput.value;
            if (prompt != '') {
                executeSQLQuery(prompt);
            } else {
                alert('Please enter a prompt.');
            }
        });
        const recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition ||
            window.mozSpeechRecognition || window.msSpeechRecognition)();
        let isListeningForCommand = false;
        recognition.continuous = true;
        recognition.interimResults = true;
        let silenceTimeout;

        recognition.onstart = () => {
            startButton.textContent = 'Listening...';
            resetSilenceTimeout();
        };

        recognition.onresult = (event) => {
            let transcript = Array.from(event.results)
                .map(result => result[0].transcript)
                .join('');

            outputDiv.textContent = `${transcript}`;
            isListeningForCommand = true;
            resetSilenceTimeout(transcript);
        };

        recognition.onend = () => {
            startButton.textContent = 'Start Voice Input';
            clearTimeout(silenceTimeout);
        };

        startButton.addEventListener('click', () => {
            recognition.start();
        });

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

        // const prompt = `Generate a MySQL query based on the following command: "${command}" for an application users table with fields: name, email, password, and role. 
    // The role column should use values 1 or 2, and the password should be hashed (e.g., $2y$10$mO2cgHHuAv7Ul2ptdI1L7uZNB.fOkyMKkbWjeWz57tHUSAjuoNV3q).
    // If the command is to create a new user, use placeholder values for any unspecified fields. For UPDATE or DELETE commands, ensure the query first checks if the specified user exists by matching available fields such as name, email, or role. 
    //  Use "SELECT * FROM users" to check for user existence. 
    // If no matching record is found, return a 'User not found' message and do not create a new user or proceed with any default action. 
    // Do not provide extra explanations—just return the SQL query.`;
        // function generateSQLFromCommand(command) {
        //     const API_KEY = "AIzaSyDDNLeZnLDUAW8cHF4kGE4yT9RLHkJpF_4";
        //     const genAI = new GoogleGenerativeAI(API_KEY);

        //     const prompt = `Generate a MySQL query based on the following command: "${command}" for an application'
    //     Do not provide extra explanations—just return the SQL query.`;

        //     const model = genAI.getGenerativeModel({
        //         model: "gemini-1.5-flash"
        //     });

        //     model.generateContent(prompt)
        //         .then(result => {
        //             const sqlQuery = result.response.candidates[0].content.parts[0].text;
        //             console.log('Generated SQL Query:', sqlQuery);
        //             executeSQLQuery(sqlQuery);
        //         })
        //         .catch(error => {
        //             console.error('Error:', error);
        //             alert('No data found !');
        //         });
        // }

        // function generateSQLFromCommand(command) {
        //     const API_KEY = "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"; 
        //     const prompt =
        //         `Generate a MySQL query based on the following command: "${command}". Do not provide extra explanations—just return the SQL query.`;

        //     fetch('https://api.openai.com/v1/chat/completions', {
        //             method: 'POST',
        //             headers: {
        //                 'Authorization': `Bearer ${API_KEY}`,
        //                 'Content-Type': 'application/json'
        //             },
        //             body: JSON.stringify({
        //                 model: "gpt-4o-mini",
        //                 messages: [{
        //                         role: "system",
        //                         content: "You are an assistant that generates SQL queries based on user instructions."
        //                     },
        //                     {
        //                         role: "user",
        //                         content: prompt
        //                     }
        //                 ],
        //                 max_tokens: 100,
        //                 temperature: 0.3
        //             })
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             const sqlQuery = data.choices[0].message.content.trim();
        //             console.log('Generated SQL Query:', sqlQuery);
        //             executeSQLQuery(sqlQuery);
        //         })
        //         .catch(error => {
        //             console.error('Error:', error);
        //             alert('No data found!');
        //         });
        // }

        function executeSQLQuery(query) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const dynamicreportingurl = '{{ route('dynamic_reporting') }}';
            const submitButton = document.getElementById('submitPromptButton');
            submitButton.disabled = true;
            submitButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing, please wait...`;
            fetch(dynamicreportingurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        prompt: query
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Query Execution Result:', data);
                    const tableContainer = document.getElementById('resultsTable');
                    tableContainer.innerHTML = '';
                    if (data.success && data.results.length > 0) {
                        data.results.forEach(result => {
                            if (result.data && result.data.length > 0) {
                                const table = document.createElement('table');
                                table.classList.add('custom-table');

                                const headerRow = document.createElement('tr');
                                const headers = Object.keys(result.data[0]);

                                headers.forEach(header => {
                                    const th = document.createElement('th');
                                    th.innerText = formatHeader(header);
                                    headerRow.appendChild(th);
                                });
                                table.appendChild(headerRow);
                                result.data.forEach(row => {
                                    const tableRow = document.createElement('tr');
                                    headers.forEach(header => {
                                        const td = document.createElement('td');
                                        td.innerText = row[header] ||
                                            '-';
                                        tableRow.appendChild(td);
                                    });
                                    table.appendChild(tableRow);
                                });
                                tableContainer.appendChild(table);
                                exportExcelbtn.classList.remove('d-none');
                                document.querySelectorAll('.custom-table th').forEach(function(th) {
                                    th.style.backgroundColor = usedColor; 
                                });
                                submitButton.disabled = false;
                                submitButton.innerHTML =
                                    `<i class="fas fa-paper-plane"></i> {{ __('Submit Prompt') }}`;
                            } else {
                                tableContainer.innerHTML = '<p>No data found.</p>';
                                submitButton.disabled = false;
                                submitButton.innerHTML =
                                    `<i class="fas fa-paper-plane"></i> {{ __('Submit Prompt') }}`;
                            }
                        });
                    } else {
                        tableContainer.innerHTML = '<p>No results to display.</p>';
                        submitButton.disabled = false;
                        submitButton.innerHTML = `<i class="fas fa-paper-plane"></i> {{ __('Submit Prompt') }}`;
                    }
                })
                .catch(error => {
                    console.error('Error executing SQL query:', error);
                    submitButton.disabled = false;
                    submitButton.innerHTML = `<i class="fas fa-paper-plane"></i> {{ __('Submit Prompt') }}`;
                });
        }

        function formatHeader(header) {
            return header
                .replace(/_/g, ' ')
                .toLowerCase()
                .replace(/\b\w/g, char => char.toUpperCase());
        }




        function readOut(message) {
            const speech = new SpeechSynthesisUtterance();
            // different voices
            const allVoices = speechSynthesis.getVoices();
            speech.text = message;
            speech.voice = allVoices[36];

            speech.volume = 1;
            window.speechSynthesis.speak(speech);
        }


        // function extractUserData(sqlResponse) {
        //     const regex =
        //         /INSERT INTO users \(\s*username,\s*email,\s*password\s*\)\s*VALUES\s*\(\s*'([^']*)',\s*'([^']*)',\s*'([^']*)'\s*\);/;
        //     const match = sqlResponse.match(regex);

        //     if (match && match.length === 4) {
        //         return {
        //             username: match[1],
        //             email: match[2],
        //             password: match[3],
        //         };
        //     } else {
        //         console.error('Could not extract user data from the response');
        //         return null;
        //     }
        // }
        // function extractUserData(sqlResponse) {
        //     // Define an object to hold the extracted user data
        //     const userData = {
        //         username: null,
        //         password: null,
        //         email: null,
        //     };

        //     // Convert the SQL response to lowercase for case-insensitive matching
        //     const lowerCaseResponse = sqlResponse.toLowerCase();

        //     // Use regular expressions to find the values
        //     const usernameMatch = lowerCaseResponse.match(/username\s*=\s*'([^']+)'/);
        //     const passwordMatch = lowerCaseResponse.match(/password\s*=\s*'([^']+)'/);
        //     const emailMatch = lowerCaseResponse.match(/email\s*=\s*'([^']+)'/);

        //     // Check for matches and assign values to the userData object
        //     if (usernameMatch) {
        //         userData.username = usernameMatch[1];
        //     }
        //     if (passwordMatch) {
        //         userData.password = passwordMatch[1];
        //     }
        //     if (emailMatch) {
        //         userData.email = emailMatch[1];
        //     }

        //     // Check if all required data is present
        //     if (userData.username && userData.password && userData.email) {
        //         return userData;
        //     } else {
        //         console.error('Could not extract all user data from the response');
        //         return null;
        //     }
        // }

        // function addUserToDatabase(userData) {
        //     const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        //     fetch('/ai/add-user', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //                 'X-CSRF-TOKEN': csrfToken
        //             },
        //             body: JSON.stringify(userData),
        //         })
        //         .then(response => {
        //             if (!response.ok) {
        //                 throw new Error('Network response was not ok');
        //             }
        //             return response.json();
        //         })
        //         .then(data => console.log('User added:', data))
        //         .catch(error => console.error('Error adding user:', error));
        // }
    </script>
@endsection
