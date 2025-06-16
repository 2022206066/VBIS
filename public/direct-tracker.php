<?php
// This is a direct access file for the satellite tracker, bypassing the router

// Set content type to HTML
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Satellite Tracker</title>
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
        .nav-links {
            margin-top: 20px;
        }
        .nav-links a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct Satellite Tracker</h1>
        <p>This page loads the satellite tracker directly, bypassing the router.</p>
        
        <div class="nav-links">
            <a href="/VBIS-main/public/">Home</a>
            <a href="/VBIS-main/public/satellites">Satellites</a>
        </div>
        
        <div class="tracker-container">
            <iframe id="satellite-test-frame" src="/VBIS-main/public/sattelite-tracker/single.html"></iframe>
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
            <button id="check-libraries">Check Libraries</button>
            <button id="reload-tracker">Reload Tracker</button>
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
        
        // Check libraries button
        document.getElementById('check-libraries').addEventListener('click', function() {
            try {
                var iframe = document.getElementById('satellite-test-frame');
                var iframeDoc = iframe.contentWindow || iframe.contentDocument;
                
                document.getElementById('debug-output').innerHTML += "<br>Checking libraries...";
                
                if (iframeDoc.satellite) {
                    document.getElementById('debug-output').innerHTML += "<br>satellite.js is loaded in iframe";
                } else {
                    document.getElementById('debug-output').innerHTML += "<br>satellite.js is NOT loaded in iframe";
                }
                
                if (iframeDoc.ol) {
                    document.getElementById('debug-output').innerHTML += "<br>OpenLayers is loaded in iframe";
                } else {
                    document.getElementById('debug-output').innerHTML += "<br>OpenLayers is NOT loaded in iframe";
                }
            } catch(e) {
                document.getElementById('debug-output').innerHTML += "<br>Error checking libraries: " + e.toString();
            }
        });
        
        // Reload tracker button
        document.getElementById('reload-tracker').addEventListener('click', function() {
            document.getElementById('debug-output').innerHTML += "<br>Reloading tracker...";
            var iframe = document.getElementById('satellite-test-frame');
            iframe.src = iframe.src;
        });
    </script>
</body>
</html> 