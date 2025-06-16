<?php

// This script tests the satellite tracker with simple-tracker.html

?>
<!DOCTYPE html>
<html>
<head>
    <title>Satellite Tracker Test (Simple)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .tracker-container {
            border: 1px solid #ccc;
            margin-top: 20px;
            height: 500px;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .debug {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f5f5f5;
        }
        .position-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .position-box {
            width: 30%;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Satellite Tracker Test</h1>
        <p>This page tests if the simple satellite tracker loads and functions correctly.</p>
        
        <div class="tracker-container">
            <iframe id="satellite-test-frame" src="sattelite-tracker/simple-tracker.html"></iframe>
        </div>
        
        <div class="position-info">
            <div class="position-box">
                <h3>Latitude</h3>
                <div id="latitude">Loading...</div>
            </div>
            <div class="position-box">
                <h3>Longitude</h3>
                <div id="longitude">Loading...</div>
            </div>
            <div class="position-box">
                <h3>Height (km)</h3>
                <div id="height">Loading...</div>
            </div>
        </div>
        
        <div class="debug">
            <h3>Debug Information</h3>
            <div id="debug-output">Waiting for satellite tracker to initialize...</div>
            <button id="test-button">Send Test Data</button>
            <button id="check-status">Check Status</button>
            <button id="direct-inject">Direct Inject Data</button>
        </div>
    </div>
    
    <script>
        // Sample satellite data (ISS)
        var satelliteData = {
            name: "ISS (ZARYA)",
            line1: "1 25544U 98067A   21336.70242088  .00005321  00000+0  10299-3 0  9991",
            line2: "2 25544  51.6423 126.9754 0004283  46.9255  83.3408 15.49512648314116"
        };
        
        // Helper to add log message
        function log(message) {
            document.getElementById('debug-output').innerHTML += "<br>" + message;
            console.log(message);
        }
        
        // Wait for iframe to load before passing data
        document.getElementById('satellite-test-frame').onload = function() {
            log("Iframe loaded, waiting for libraries to initialize...");
            
            // Give the iframe some time to fully initialize its JS
            setTimeout(function() {
                sendSatelliteData();
            }, 2000); // Longer delay to ensure iframe is fully ready
        };
        
        // Function to send data to the iframe
        function sendSatelliteData() {
            try {
                var iframe = document.getElementById('satellite-test-frame');
                log("Sending data: " + JSON.stringify(satelliteData));
                
                // Try the API method first
                if (iframe.contentWindow.trackerAPI && typeof iframe.contentWindow.trackerAPI.loadSatellite === 'function') {
                    iframe.contentWindow.trackerAPI.loadSatellite(satelliteData);
                    log("Data sent using trackerAPI");
                } else {
                    // Fall back to postMessage
                    iframe.contentWindow.postMessage(satelliteData, '*');
                    log("Data sent using postMessage");
                }
                
                // Verify the data was received
                setTimeout(function() {
                    checkStatus();
                }, 500);
                
            } catch(e) {
                log("Error sending data: " + e.toString());
                console.error("Error details:", e);
            }
        }
        
        // Function to check tracker status
        function checkStatus() {
            try {
                var iframe = document.getElementById('satellite-test-frame');
                var trackerAPI = iframe.contentWindow.trackerAPI;
                
                if (trackerAPI && trackerAPI.getStatus) {
                    var status = trackerAPI.getStatus();
                    log("Status check: " + JSON.stringify(status));
                    
                    // If data wasn't received, try sending again
                    if (!status.hasData) {
                        log("Data not received by tracker, trying again...");
                        // Try direct approach
                        var win = iframe.contentWindow;
                        if (win.satelliteData === null) {
                            win.satelliteData = satelliteData;
                            if (win.startTracking) {
                                win.startTracking();
                                log("Tried direct satelliteData assignment");
                            }
                        }
                    }
                } else {
                    log("Tracker API not available");
                }
            } catch(e) {
                log("Error checking status: " + e.toString());
            }
        }
        
        // Test button
        document.getElementById('test-button').addEventListener('click', function() {
            sendSatelliteData();
        });
        
        // Check status button
        document.getElementById('check-status').addEventListener('click', function() {
            checkStatus();
        });
        
        // Direct inject button for emergency testing
        document.getElementById('direct-inject').addEventListener('click', function() {
            try {
                var iframe = document.getElementById('satellite-test-frame');
                var win = iframe.contentWindow;
                
                log("Attempting direct injection of satellite data");
                
                // Force the satellite data directly
                win.satelliteData = satelliteData;
                log("Direct data assignment done");
                
                // Try to force tracking to start
                if (typeof win.startTracking === 'function') {
                    win.startTracking();
                    log("Called startTracking() directly");
                } else {
                    log("startTracking() function not available");
                }
                
                // Check if it worked
                setTimeout(function() {
                    if (win.trackerAPI && win.trackerAPI.getStatus) {
                        var status = win.trackerAPI.getStatus();
                        log("Post-injection status: " + JSON.stringify(status));
                    }
                }, 500);
                
            } catch(e) {
                log("Error with direct injection: " + e.toString());
                console.error(e);
            }
        });
        
        // Listen for position updates from the iframe
        window.addEventListener('message', function(event) {
            console.log("Received message from iframe:", event.data);
            
            if (event.data && event.data.type === 'position') {
                document.getElementById('latitude').textContent = event.data.latitude.toFixed(6) + "°";
                document.getElementById('longitude').textContent = event.data.longitude.toFixed(6) + "°";
                document.getElementById('height').textContent = event.data.height.toFixed(2);
                log("Position updated");
            }
        });
    </script>
</body>
</html> 