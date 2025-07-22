<?php
// api_tester.php - Display the actual structure of the API response
header('Content-Type: text/html; charset=utf-8');

// Load configuration
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LATL API Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2, h3 {
            color: #333;
        }
        pre {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 500px;
            margin: 15px 0;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .api-test {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LATL API Structure Tester</h1>
        
        <div class="api-test">
            <h2>Test 1: Fetch layout/forside</h2>
            <p>This test will fetch the data for the front page and show the complete response structure.</p>
            <button id="testLayoutForside">Test layout/forside</button>
            <div id="layoutForsideResult"></div>
        </div>
        
        <div class="api-test">
            <h2>Test 2: Fetch layout/global/styles</h2>
            <p>This test will fetch the global styles configuration.</p>
            <button id="testGlobalStyles">Test global styles</button>
            <div id="globalStylesResult"></div>
        </div>
        
        <div class="api-test">
            <h2>Test 3: Test forside with Mock Data</h2>
            <p>This test will try to render the front page using mock data.</p>
            <button id="testMockData">Test with Mock Data</button>
            <div id="mockDataResult" style="margin-top: 15px; min-height: 100px;"></div>
        </div>
        
        <div class="api-test">
            <h2>Manual API URL Test</h2>
            <p>Enter an API endpoint to test (e.g. "layout/forside", "products"):</p>
            <input type="text" id="customEndpoint" style="width: 300px; padding: 8px;">
            <button id="testCustomEndpoint">Test Endpoint</button>
            <div id="customEndpointResult"></div>
        </div>
    </div>
    
    <script>
        // API-adresse
        const API_URL = '<?php echo BASE_URL; ?>/api';
        
        // Hjælpefunktion til at hente data fra API
        async function fetchApi(endpoint) {
            try {
                console.log(`Fetching from: ${API_URL}/${endpoint}`);
                
                const response = await fetch(`${API_URL}/${endpoint}`);
                const result = await response.json();
                
                console.log('API response:', result);
                
                if (!response.ok) {
                    throw new Error(result.message || 'Der skete en fejl');
                }
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                return { status: 'error', message: error.toString() };
            }
        }
        
        // Hjælpefunktion til at formattere JSON
        function formatJson(json) {
            return JSON.stringify(json, null, 4);
        }
        
        // Test 1: Fetch layout/forside
        document.getElementById('testLayoutForside').addEventListener('click', async () => {
            const resultElement = document.getElementById('layoutForsideResult');
            resultElement.innerHTML = '<p>Henter data...</p>';
            
            const result = await fetchApi('layout/forside');
            
            if (result.status === 'success') {
                resultElement.innerHTML = `
                    <p class="success">Success! Layout data received.</p>
                    <h3>Data Structure:</h3>
                    <pre>${formatJson(result)}</pre>
                    
                    <h3>Structure Analysis:</h3>
                    <div>
                        <p>Looking at your data structure to help debug frontend issues:</p>
                        <ul>
                            <li>Status: ${result.status}</li>
                            <li>Has 'data' property: ${result.data !== undefined ? 'Yes' : 'No'}</li>
                            <li>Data has 'page_id': ${result.data && result.data.page_id ? 'Yes: ' + result.data.page_id : 'No'}</li>
                            <li>Data has 'layout_data': ${result.data && result.data.layout_data ? 'Yes' : 'No'}</li>
                            <li>Data has 'bands': ${result.data && result.data.bands ? 'Yes' : 'No'}</li>
                            <li>Data has property directly named 'bands': ${result.data && Array.isArray(result.data.bands) ? 'Yes, array with ' + result.data.bands.length + ' items' : 'No'}</li>
                        </ul>
                    </div>
                `;
            } else {
                resultElement.innerHTML = `
                    <p class="error">Error: ${result.message}</p>
                `;
            }
        });
        
        // Test 2: Fetch global styles
        document.getElementById('testGlobalStyles').addEventListener('click', async () => {
            const resultElement = document.getElementById('globalStylesResult');
            resultElement.innerHTML = '<p>Henter globale stilarter...</p>';
            
            const result = await fetchApi('layout/global/styles');
            
            if (result.status === 'success') {
                resultElement.innerHTML = `
                    <p class="success">Success! Global styles received.</p>
                    <pre>${formatJson(result)}</pre>
                `;
            } else {
                resultElement.innerHTML = `
                    <p class="error">Error: ${result.message}</p>
                    <p>This could indicate your API doesn't have the required endpoint for global styles.</p>
                `;
            }
        });
        
        // Test 3: Mock Data Test
        document.getElementById('testMockData').addEventListener('click', async () => {
            const resultElement = document.getElementById('mockDataResult');
            
            // Mock layout data
            const mockLayout = {
                page_id: "forside",
                layout_data: {
                    title: "LATL.dk - Test",
                    meta_description: "Test description"
                },
                bands: [
                    {
                        id: 1,
                        band_type: "html",
                        band_height: 1,
                        band_order: 1,
                        band_content: {
                            html: "<h2>Test Band</h2><p>Dette er et test bånd for at verificere rendering.</p>"
                        }
                    }
                ]
            };
            
            resultElement.innerHTML = `
                <div style="background-color: #e3f7e3; padding: 15px; border-radius: 5px;">
                    <h2>Test Band</h2>
                    <p>Dette er et test bånd for at verificere rendering.</p>
                </div>
                <p style="margin-top: 15px;">Mock data was rendered successfully. If this works but the real site doesn't, the issue is likely with the data structure from your API.</p>
                <p>Mock data structure:</p>
                <pre>${formatJson(mockLayout)}</pre>
            `;
        });
        
        // Custom endpoint test
        document.getElementById('testCustomEndpoint').addEventListener('click', async () => {
            const endpoint = document.getElementById('customEndpoint').value.trim();
            const resultElement = document.getElementById('customEndpointResult');
            
            if (!endpoint) {
                resultElement.innerHTML = '<p class="error">Please enter an endpoint to test</p>';
                return;
            }
            
            resultElement.innerHTML = '<p>Testing endpoint...</p>';
            
            const result = await fetchApi(endpoint);
            resultElement.innerHTML = `
                <h3>Response:</h3>
                <pre>${formatJson(result)}</pre>
            `;
        });
    </script>
</body>
</html>
