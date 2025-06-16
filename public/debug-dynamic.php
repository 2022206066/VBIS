<?php
require_once __DIR__ . "/../vendor/autoload.php";
use app\core\Application;

$app = new Application();
$baseUrl = Application::$BASE_URL;

// Helper function to check if a resource exists
function resourceExists($path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    return file_exists($fullPath) ? "✅ Exists" : "❌ Not found";
}

function makeAbsolutePath($path) {
    global $baseUrl;
    // Remove trailing slash from baseUrl if it exists
    $base = rtrim($baseUrl, '/');
    // Add leading slash to path if it doesn't exist
    if (substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .test-box { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Path Debug Tool</h1>
    
    <h2>Server Variables</h2>
    <pre>
REQUEST_URI: <?= $_SERVER['REQUEST_URI'] ?? 'not set' ?>

SCRIPT_NAME: <?= $_SERVER['SCRIPT_NAME'] ?? 'not set' ?>

PHP_SELF: <?= $_SERVER['PHP_SELF'] ?? 'not set' ?>

DOCUMENT_ROOT: <?= $_SERVER['DOCUMENT_ROOT'] ?? 'not set' ?>

HTTP_HOST: <?= $_SERVER['HTTP_HOST'] ?? 'not set' ?>
    </pre>

    <h2>Application Base URL</h2>
    <pre><?= $baseUrl ?></pre>
    
    <h2>Path Resolution Tests</h2>
    <table>
        <tr>
            <th>Resource</th>
            <th>Generated URL</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>OpenLayers CSS</td>
            <td><?= $cssPath = makeAbsolutePath('/assets/libs/openlayers/ol.css') ?></td>
            <td><?= resourceExists($cssPath) ?></td>
        </tr>
        <tr>
            <td>OpenLayers JS</td>
            <td><?= $jsPath = makeAbsolutePath('/assets/libs/openlayers/ol.js') ?></td>
            <td><?= resourceExists($jsPath) ?></td>
        </tr>
        <tr>
            <td>Satellite.js</td>
            <td><?= $satPath = makeAbsolutePath('/sattelite-tracker/node_modules/satellite.js/dist/satellite.min.js') ?></td>
            <td><?= resourceExists($satPath) ?></td>
        </tr>
        <tr>
            <td>Satellite Tracker HTML</td>
            <td><?= $htmlPath = makeAbsolutePath('/sattelite-tracker/single.html') ?></td>
            <td><?= resourceExists($htmlPath) ?></td>
        </tr>
    </table>
    
    <div class="test-box">
        <h2>Test Links</h2>
        <p>Click these links to verify they work correctly:</p>
        <ul>
            <li><a href="<?= Application::url('/') ?>">Home</a></li>
            <li><a href="<?= Application::url('/satellites') ?>">Satellites</a></li>
            <li><a href="<?= Application::url('/login') ?>">Login</a></li>
        </ul>
    </div>
    
    <div class="test-box">
        <h2>Resource Loading Test</h2>
        <p>This box will load OpenLayers and display a simple map if paths are correct:</p>
        <div id="map" style="width: 400px; height: 300px;"></div>
        <script>
            // Try different paths to load OpenLayers
            function loadScript(src, callback) {
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = src;
                script.onload = callback;
                script.onerror = function() {
                    console.error('Failed to load: ' + src);
                    document.getElementById('status').innerHTML += '<div class="error">❌ Failed to load: ' + src + '</div>';
                    
                    // Try next path if this one fails
                    if (currentPathIndex < paths.length - 1) {
                        currentPathIndex++;
                        tryLoadOpenLayers();
                    }
                };
                document.head.appendChild(script);
            }
            
            var currentPathIndex = 0;
            var paths = [
                '<?= Application::url("/assets/libs/openlayers/ol.js") ?>',
                '/VBIS-main/public/assets/libs/openlayers/ol.js',
                '../assets/libs/openlayers/ol.js'
            ];
            
            function tryLoadOpenLayers() {
                document.getElementById('status').innerHTML += '<div>Trying path: ' + paths[currentPathIndex] + '</div>';
                loadScript(paths[currentPathIndex], function() {
                    if (typeof ol !== 'undefined') {
                        document.getElementById('status').innerHTML += '<div class="success">✅ OpenLayers loaded successfully!</div>';
                        initMap();
                    } else {
                        document.getElementById('status').innerHTML += '<div class="error">❌ OpenLayers object not available despite script loading</div>';
                    }
                });
            }
            
            function initMap() {
                try {
                    new ol.Map({
                        target: 'map',
                        layers: [
                            new ol.layer.Tile({
                                source: new ol.source.OSM()
                            })
                        ],
                        view: new ol.View({
                            center: ol.proj.fromLonLat([0, 0]),
                            zoom: 2
                        })
                    });
                    document.getElementById('status').innerHTML += '<div class="success">✅ Map initialized successfully!</div>';
                } catch (e) {
                    document.getElementById('status').innerHTML += '<div class="error">❌ Error initializing map: ' + e.message + '</div>';
                }
            }
            
            // Add status div
            document.write('<div id="status" style="margin-top: 10px;"></div>');
            
            // Start loading process
            tryLoadOpenLayers();
        </script>
    </div>
</body>
</html> 