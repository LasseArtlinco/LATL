<?php
// api_response_debug.php - Diagnosticeringsværktøj for API-kald
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        button { background: #042940; color: white; border: none; padding: 8px 15px; border-radius: 3px; cursor: pointer; }
        input[type="text"] { padding: 8px; width: 60%; }
    </style>
</head>
<body>
    <h1>API Response Debug</h1>
    
    <div class="card">
        <h2>Test Band Save (POST)</h2>
        <button id="testPostFormData">Test POST with FormData</button>
        <div id="postResult"></div>
    </div>
    
    <div class="card">
        <h2>Custom API Call</h2>
        <div style="display: flex; margin-bottom: 10px;">
            <select id="methodSelect" style="padding: 8px; margin-right: 10px;">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>
            <input type="text" id="endpointInput" placeholder="api/endpoint" value="api/bands/forside">
            <button id="sendRequest" style="margin-left: 10px;">Send Request</button>
        </div>
        <div>
            <p>Request Body (for POST/PUT):</p>
            <textarea id="requestBody" style="width: 100%; height: 100px; padding: 8px;" placeholder='{"key": "value"}'></textarea>
        </div>
        <div id="customResult"></div>
    </div>
    
    <script>
        // API-adresse
        const API_URL = '/api';
        
        // Test POST med FormData
        document.getElementById('testPostFormData').addEventListener('click', async () => {
            const resultElement = document.getElementById('postResult');
            resultElement.innerHTML = '<p>Sender POST med FormData...</p>';
            
            // Opret test data
            const formData = new FormData();
            
            // Tilføj band data
            const bandData = {
                band_type: 'html',
                band_height: '1',
                band_order: '1',
                band_content: {
                    html: '<h2>Test Band</h2><p>Dette er et test bånd oprettet via API-debug værktøjet.</p>'
                }
            };
            
            formData.append('band_data', JSON.stringify(bandData));
            
            try {
                const response = await fetch(`${API_URL}/bands/forside`, {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    resultElement.innerHTML = `
                        <p>Status: ${response.status} ${response.statusText}</p>
                        <p>Response:</p>
                        <pre>${JSON.stringify(result, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultElement.innerHTML = `
                        <p>Status: ${response.status} ${response.statusText}</p>
                        <p>Raw Response (invalid JSON):</p>
                        <pre>${responseText}</pre>
                        <p>JSON Parse Error: ${e.message}</p>
                    `;
                }
            } catch (error) {
                resultElement.innerHTML = `
                    <p>Fetch Error: ${error.message}</p>
                `;
            }
        });
        
        // Custom API Call
        document.getElementById('sendRequest').addEventListener('click', async () => {
            const method = document.getElementById('methodSelect').value;
            const endpoint = document.getElementById('endpointInput').value.trim();
            const body = document.getElementById('requestBody').value.trim();
            const resultElement = document.getElementById('customResult');
            
            resultElement.innerHTML = `<p>Sender ${method} til ${endpoint}...</p>`;
            
            const options = {
                method: method
            };
            
            if (method === 'POST' || method === 'PUT') {
                if (body) {
                    options.headers = {
                        'Content-Type': 'application/json'
                    };
                    options.body = body;
                }
            }
            
            try {
                const response = await fetch(endpoint, options);
                const responseText = await response.text();
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    resultElement.innerHTML = `
                        <p>Status: ${response.status} ${response.statusText}</p>
                        <p>Response:</p>
                        <pre>${JSON.stringify(result, null, 2)}</pre>
                    `;
                } catch (e) {
                    resultElement.innerHTML = `
                        <p>Status: ${response.status} ${response.statusText}</p>
                        <p>Raw Response (invalid JSON):</p>
                        <pre>${responseText}</pre>
                        <p>JSON Parse Error: ${e.message}</p>
                    `;
                }
            } catch (error) {
                resultElement.innerHTML = `
                    <p>Fetch Error: ${error.message}</p>
                `;
            }
        });
    </script>
</body>
</html>
