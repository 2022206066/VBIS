<?php
// Diagnostic script for debugging satellite tracker issues

// Set headers for no caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Satellite Tracker Diagnostics</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        button.secondary {
            background-color: #2196F3;
        }
        button.warning {
            background-color: #ff9800;
        }
    </style>
</head>
<body>
    <h1>Satellite Tracker Diagnostics</h1>
    <p>This page provides diagnostics for the satellite tracker component.</p>
    
    <div class="section">
        <h2>1. Environment Information</h2>
        <div class="grid">
            <div class="card">
                <h3>PHP Version</h3>
                <p><?php echo phpversion(); ?></p>
            </div>
            <div class="card">
                <h3>Server Software</h3>
                <p><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            </div>
            <div class="card">
                <h3>Request URI</h3>
                <p><?php echo $_SERVER['REQUEST_URI']; ?></p>
            </div>
            <div class="card">
                <h3>Document Root</h3>
                <p><?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h2>2. Library Tests</h2>
        <div id="library-tests">Running tests...</div>
        <button id="run-tests" class="secondary">Re-run Tests</button>
    </div>
    
    <div class="section">
        <h2>3. Test Satellite Data</h2>
        <p>Below is a test satellite (ISS) that should display if everything is working:</p>
        <div style="height: 400px; border: 1px solid #ccc; margin-bottom: 15px;">
            <iframe id="satellite-frame" src="sattelite-tracker/simple-tracker.html" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
        <button id="send-data">Send Test Satellite Data</button>
        <button id="check-status" class="secondary">Check Status</button>
        <div id="status-output" style="margin-top: 10px;"></div>
    </div>
    
    <div class="section">
        <h2>4. Network Requests</h2>
        <p>Checking if satellite.js and OpenLayers can be accessed:</p>
        <div id="network-tests">Running network tests...</div>
    </div>
    
    <div class="section">
        <h2>5. Browser Information</h2>
        <div id="browser-info"></div>
    </div>
    
    <script>
        // Test Libraries
        function runLibraryTests() {
            const testContainer = document.getElementById('library-tests');
            testContainer.innerHTML = '';
            
            // Test if can load OpenLayers from CDN
            testLibrary('https://cdn.jsdelivr.net/npm/ol@v7.3.0/dist/ol.js', 'OpenLayers (jsDelivr)', testContainer);
            testLibrary('https://cdnjs.cloudflare.com/ajax/libs/openlayers/7.3.0/ol.js', 'OpenLayers (CDNJS)', testContainer);
            testLibrary('https://unpkg.com/ol@7.3.0/dist/ol.js', 'OpenLayers (unpkg)', testContainer);
            
            // Test if can load satellite.js from CDN
            testLibrary('https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js', 'satellite.js (jsDelivr)', testContainer);
            testLibrary('https://unpkg.com/satellite.js@5.0.0/dist/satellite.min.js', 'satellite.js (unpkg)', testContainer);
        }
        
        function testLibrary(url, name, container) {
            const result = document.createElement('div');
            result.className = 'test-result';
            result.innerHTML = `Testing ${name} from ${url}...`;
            container.appendChild(result);
            
            fetch(url)
                .then(response => {
                    if (response.ok) {
                        result.className = 'test-result success';
                        result.innerHTML = `✅ ${name}: Library is accessible`;
                    } else {
                        result.className = 'test-result error';
                        result.innerHTML = `❌ ${name}: HTTP error ${response.status}`;
                    }
                })
                .catch(error => {
                    result.className = 'test-result error';
                    result.innerHTML = `❌ ${name}: ${error.message}`;
                });
        }
        
        // Send test satellite data to iframe
        document.getElementById('send-data').addEventListener('click', function() {
            const iframe = document.getElementById('satellite-frame');
            const testData = {
                name: "ISS (ZARYA)",
                line1: "1 25544U 98067A   21336.70242088  .00005321  00000+0  10299-3 0  9991",
                line2: "2 25544  51.6423 126.9754 0004283  46.9255  83.3408 15.49512648314116"
            };
            
            try {
                // First, ensure the iframe is ready
                if (!iframe || !iframe.contentWindow) {
                    document.getElementById('status-output').innerHTML = 
                        '<div class="test-result error">Iframe not ready or accessible</div>';
                    return;
                }
                
                // Try to send via postMessage
                iframe.contentWindow.postMessage(testData, '*');
                
                // Also try to use the API if available
                if (iframe.contentWindow.trackerAPI) {
                    // Store data and start tracking if map is initialized
                    iframe.contentWindow.satelliteData = testData;
                    if (iframe.contentWindow.trackerAPI.initMap && !iframe.contentWindow.mapObj) {
                        iframe.contentWindow.trackerAPI.initMap();
                    }
                    if (iframe.contentWindow.trackerAPI.startTracking) {
                        iframe.contentWindow.trackerAPI.startTracking();
                    }
                }
                
                document.getElementById('status-output').innerHTML = 
                    '<div class="test-result success">Test data sent to iframe</div>';
            } catch(e) {
                document.getElementById('status-output').innerHTML = 
                    `<div class="test-result error">Error sending data: ${e.message}</div>`;
            }
        });
        
        // Check iframe status
        document.getElementById('check-status').addEventListener('click', function() {
            try {
                const iframe = document.getElementById('satellite-frame');
                const trackerAPI = iframe.contentWindow.trackerAPI;
                
                if (trackerAPI && trackerAPI.getStatus) {
                    const status = trackerAPI.getStatus();
                    let html = '<div class="test-result success">';
                    html += `<p><strong>OpenLayers loaded:</strong> ${status.olLoaded ? '✅ Yes' : '❌ No'}</p>`;
                    html += `<p><strong>satellite.js loaded:</strong> ${status.satelliteJsLoaded ? '✅ Yes' : '❌ No'}</p>`;
                    html += `<p><strong>Map initialized:</strong> ${status.mapInitialized ? '✅ Yes' : '❌ No'}</p>`;
                    html += `<p><strong>Satellite data received:</strong> ${status.hasData ? '✅ Yes' : '❌ No'}</p>`;
                    if (status.mapInitialized) {
                        html += `<p><strong>Map layers:</strong> ${status.layers}</p>`;
                    }
                    html += '</div>';
                    document.getElementById('status-output').innerHTML = html;
                } else {
                    document.getElementById('status-output').innerHTML = 
                        '<div class="test-result warning">Tracker API not available - tracker may not be fully loaded</div>';
                }
            } catch(e) {
                document.getElementById('status-output').innerHTML = 
                    `<div class="test-result error">Error checking status: ${e.message}</div>`;
            }
        });
        
        // Run network tests
        function runNetworkTests() {
            const testContainer = document.getElementById('network-tests');
            testContainer.innerHTML = '';
            
            // Test URLs
            const urls = [
                { url: 'sattelite-tracker/node_modules/satellite.js/dist/satellite.min.js', name: 'Local satellite.js' },
                { url: 'sattelite-tracker/single.html', name: 'Tracker HTML' },
                { url: 'sattelite-tracker/css/main.css', name: 'Tracker CSS' }
            ];
            
            urls.forEach(item => {
                const result = document.createElement('div');
                result.className = 'test-result';
                result.innerHTML = `Testing ${item.name}...`;
                testContainer.appendChild(result);
                
                fetch(item.url)
                    .then(response => {
                        if (response.ok) {
                            result.className = 'test-result success';
                            result.innerHTML = `✅ ${item.name}: File is accessible`;
                        } else {
                            result.className = 'test-result error';
                            result.innerHTML = `❌ ${item.name}: HTTP error ${response.status}`;
                        }
                    })
                    .catch(error => {
                        result.className = 'test-result error';
                        result.innerHTML = `❌ ${item.name}: ${error.message}`;
                    });
            });
        }
        
        // Display browser info
        function showBrowserInfo() {
            const container = document.getElementById('browser-info');
            const info = {
                'User Agent': navigator.userAgent,
                'Platform': navigator.platform,
                'Language': navigator.language,
                'Cookies Enabled': navigator.cookieEnabled,
                'Screen Size': `${window.screen.width}x${window.screen.height}`,
                'Window Size': `${window.innerWidth}x${window.innerHeight}`
            };
            
            let html = '<div class="grid">';
            for (const [key, value] of Object.entries(info)) {
                html += `
                <div class="card">
                    <h3>${key}</h3>
                    <p>${value}</p>
                </div>`;
            }
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Run tests when page loads
        window.addEventListener('DOMContentLoaded', function() {
            runLibraryTests();
            runNetworkTests();
            showBrowserInfo();
            
            // Setup re-run button
            document.getElementById('run-tests').addEventListener('click', function() {
                runLibraryTests();
                runNetworkTests();
            });
        });
    </script>
</body>
</html> 