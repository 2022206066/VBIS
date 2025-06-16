<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Satellite Tracker</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <!-- Use iframe to embed the satellite tracker with correct path resolution -->
                <iframe id="satellite-tracker-frame" src="<?= \app\core\Application::url('/sattelite-tracker/single.html') ?>" style="width: 100%; height: 600px; border: none;"></iframe>
                
                <!-- Add a fallback message in case iframe fails to load -->
                <div id="iframe-error" style="display:none; padding: 20px; text-align: center;">
                    <p>The satellite tracker failed to load. Please check your browser console for errors.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load satellite.js for parent page -->
<script src="<?= \app\core\Application::url('/sattelite-tracker/res/tles.js') ?>"></script>
<script src="<?= \app\core\Application::url('/sattelite-tracker/node_modules/satellite.js/dist/satellite.min.js') ?>" onerror="loadSatelliteFallback()"></script>
<script src="<?= \app\core\Application::url('/sattelite-tracker/js/parse.js') ?>"></script>
<script>
// Fallback mechanism for satellite.js
function loadSatelliteFallback() {
  console.log("Local satellite.js failed to load, using CDN fallback");
  var fallbackScript = document.createElement('script');
  fallbackScript.src = 'https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js';
  document.head.appendChild(fallbackScript);
}
</script>

<script>
<?= $model['satellitesJs'] ?>

// Check if JS resources are correctly loaded
if (typeof satellite === 'undefined') {
  console.error("satellite.js library not loaded!");
}

// Parse TLEs and create satellite objects
function parseTles(tles) {
  let tleArr = [];
  linesArr = tles.split("\n");
  let tle = "placeholder";
  linesArr.forEach(line => {
    if(line.split(" ")[0] === "1") {
      tle.line1 = line;
    }
    else if(line.split(" ")[0] === "2") {
      tle.line2 = line;
    }
    else {
      if(tle !== "placeholder") tleArr.push(tle);
      tle = {};
      tle.name = line;
    }
  });
  return tleArr;
}

// Wait for iframe to load, then send ISS TLE data to it
document.getElementById('satellite-tracker-frame').onload = function() {
  console.log("Satellite tracker iframe loaded successfully");
  const tleArr = parseTles(tles);
  if (tleArr.length > 0) {
    // Find ISS in TLE data
    let issSatellite = null;
    for (let sat of tleArr) {
      if (sat.name.includes("ISS")) {
        issSatellite = sat;
        break;
      }
    }
    
    // If ISS not found, use first satellite
    if (!issSatellite && tleArr.length > 0) {
      issSatellite = tleArr[0];
    }
    
    if (issSatellite) {
      console.log("Sending satellite data to iframe:", issSatellite);
      try {
        this.contentWindow.postMessage({
          name: issSatellite.name,
          line1: issSatellite.line1,
          line2: issSatellite.line2
        }, '*');
      } catch (e) {
        console.error("Error sending data to iframe:", e);
      }
    }
    
    // Listen for position updates from iframe
    window.addEventListener('message', function(event) {
      if (event.data && event.data.type === 'position') {
        console.log("Received position from iframe:", event.data);
      }
    });
  }
};

// Handle iframe load error
document.getElementById('satellite-tracker-frame').onerror = function() {
  console.error("Failed to load satellite tracker iframe");
  document.getElementById('iframe-error').style.display = 'block';
  this.style.display = 'none';
};
</script> 