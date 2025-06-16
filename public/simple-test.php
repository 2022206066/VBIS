<?php
// Simple test page for satellite tracker
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Satellite Tracker Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .tracker-container {
            border: 1px solid #ccc;
            margin: 20px 0;
            height: 500px;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .position-display {
            display: flex;
            justify-content: space-between;
        }
        .position-box {
            width: 30%;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        h1, h2 {
            color: #333;
        }
        .controls {
            margin: 20px 0;
        }
        button {
            padding: 8px 15px;
            margin-right: 10px;
            cursor: pointer;
        }
        #log {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            height: 150px;
            overflow-y: auto;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Satellite Tracker Test</h1>
        <p>This page tests the simplified satellite tracker.</p>
        
        <div class="controls">
            <button id="send-data">Send Test Data</button>
            <button id="reset">Reset</button>
            <button id="check-tracker">Check Tracker</button>
        </div>
        
        <div class="tracker-container">
            <iframe id="tracker-frame" src="sattelite-tracker/simple-tracker.html"></iframe>
        </div>
        
        <div class="position-display">
            <div class="position-box">
                <h2>Latitude</h2>
                <div id="latitude">Waiting...</div>
            </div>
            <div class="position-box">
                <h2>Longitude</h2>
                <div id="longitude">Waiting...</div>
            </div>
            <div class="position-box">
                <h2>Height</h2>
                <div id="height">Waiting...</div>
            </div>
        </div>
        
        <div id="log">Log messages will appear here...</div>
    </div>
    
    <script>
        // Log function
        function log(message) {
            const logElement = document.getElementById('log');
            const entry = document.createElement('div');
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logElement.appendChild(entry);
            logElement.scrollTop = logElement.scrollHeight;
            console.log(message);
        }
        
        // Sample satellite data (ISS)
        const testData = {
            name: "ISS (ZARYA)",
            line1: "1 25544U 98067A   21336.70242088  .00005321  00000+0  10299-3 0  9991",
            line2: "2 25544  51.6423 126.9754 0004283  46.9255  83.3408 15.49512648314116"
        };
        
        // Send data button
        document.getElementById('send-data').addEventListener('click', function() {
            log('Sending test data to tracker...');
            try {
                const iframe = document.getElementById('tracker-frame');
                
                // Print the data being sent for debugging
                log('Sending data: ' + JSON.stringify(testData));
                
                // Try direct method first
                if (iframe.contentWindow.trackerAPI && iframe.contentWindow.trackerAPI.loadSatellite) {
                    iframe.contentWindow.trackerAPI.loadSatellite(testData);
                    log('Data sent via trackerAPI.loadSatellite()');
                } else {
                    // Fall back to postMessage
                    iframe.contentWindow.postMessage(testData, '*');
                    log('Data sent via postMessage()');
                }
                
                // Check if data was received properly
                setTimeout(function() {
                    try {
                        if (iframe.contentWindow.trackerAPI) {
                            const status = iframe.contentWindow.trackerAPI.getStatus();
                            log('Data received by tracker: ' + status.hasData);
                        }
                    } catch (e) {
                        log('Error checking data receipt: ' + e.message);
                    }
                }, 500);
                
            } catch (error) {
                log(`Error sending data: ${error.message}`);
                console.error('Error details:', error);
            }
        });
        
        // Reset button
        document.getElementById('reset').addEventListener('click', function() {
            log('Resetting tracker...');
            const iframe = document.getElementById('tracker-frame');
            iframe.src = iframe.src;
            
            document.getElementById('latitude').textContent = 'Waiting...';
            document.getElementById('longitude').textContent = 'Waiting...';
            document.getElementById('height').textContent = 'Waiting...';
        });
        
        // Check tracker button
        document.getElementById('check-tracker').addEventListener('click', function() {
            log('Checking tracker status...');
            try {
                const iframe = document.getElementById('tracker-frame');
                const trackerAPI = iframe.contentWindow.trackerAPI;
                
                if (trackerAPI && trackerAPI.getStatus) {
                    const status = trackerAPI.getStatus();
                    log(`OpenLayers loaded: ${status.olLoaded}`);
                    log(`satellite.js loaded: ${status.satelliteJsLoaded}`);
                    log(`Map initialized: ${status.mapInitialized ? 'Yes' : 'No'}`);
                    log(`Has satellite data: ${status.hasData ? 'Yes' : 'No'}`);
                    
                    if (status.mapInitialized) {
                        log(`Map layers: ${status.layers}`);
                    }
                } else {
                    log('Tracker API not available - iframe may not be fully loaded');
                }
            } catch (error) {
                log(`Error checking tracker: ${error.message}`);
            }
        });
        
        // Listen for position updates
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'position') {
                const position = event.data;
                
                document.getElementById('latitude').textContent = `${position.latitude.toFixed(6)}°`;
                document.getElementById('longitude').textContent = `${position.longitude.toFixed(6)}°`;
                document.getElementById('height').textContent = `${position.height.toFixed(2)} km`;
                
                log(`Position updated: ${position.latitude.toFixed(2)}°, ${position.longitude.toFixed(2)}°, ${position.height.toFixed(2)} km`);
            }
        });
        
        // Log page load
        window.onload = function() {
            log('Page loaded');
        };
    </script>
</body>
</html> 