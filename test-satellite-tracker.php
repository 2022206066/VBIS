<?php

// This script tests the satellite tracker to verify it loads correctly

?>
<!DOCTYPE html>
<html>
<head>
    <title>Satellite Tracker Test</title>
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
        <h1>Satellite Tracker Test</h1>
        <p>This page tests if the satellite tracker loads and functions correctly.</p>
        
        <div class="tracker-container">
            <iframe id="satellite-test-frame" src="sattelite-tracker/single.html"></iframe>
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
        </div>
    </div>
    
    <script>
        // Sample satellite data (ISS)
        var satelliteData = {
            name: "ISS (ZARYA)",
            line1: "1 25544U 98067A   21336.70242088  .00005321  00000+0  10299-3 0  9991",
            line2: "2 25544  51.6423 126.9754 0004283  46.9255  83.3408 15.49512648314116"
        };
        
        // Wait for iframe to load before passing data
        document.getElementById('satellite-test-frame').onload = function() {
            console.log("Iframe loaded, sending data");
            document.getElementById('debug-output').innerHTML += "<br>Iframe loaded, sending satellite data...";
            
            // Give the iframe some time to fully initialize its JS
            setTimeout(function() {
                try {
                    document.getElementById('satellite-test-frame').contentWindow.postMessage(satelliteData, '*');
                    document.getElementById('debug-output').innerHTML += "<br>Data sent to iframe";
                } catch(e) {
                    document.getElementById('debug-output').innerHTML += "<br>Error sending data to iframe: " + e.toString();
                    console.error("Error sending data to iframe:", e);
                }
            }, 2000); // Longer delay to ensure iframe is fully ready
        };
        
        // Listen for position updates from the iframe
        window.addEventListener('message', function(event) {
            console.log("Received message from iframe:", event.data);
            document.getElementById('debug-output').innerHTML += "<br>Received message from iframe: " + JSON.stringify(event.data);
            
            if (event.data && event.data.type === 'position') {
                document.getElementById('latitude').textContent = event.data.latitude.toFixed(6) + "°";
                document.getElementById('longitude').textContent = event.data.longitude.toFixed(6) + "°";
                document.getElementById('height').textContent = event.data.height.toFixed(2);
                console.log("Position updated in UI");
            }
        });
    </script>
</body>
</html> 