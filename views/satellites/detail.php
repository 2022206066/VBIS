<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Satellite Details</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name" class="form-control-label">Name</label>
                            <input class="form-control" type="text" value="<?= $model->name ?>" id="name" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category" class="form-control-label">Category</label>
                            <input class="form-control" type="text" value="<?= $model->category ?>" id="category" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="line1" class="form-control-label">TLE Line 1</label>
                            <input class="form-control" type="text" value="<?= $model->line1 ?>" id="line1" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="line2" class="form-control-label">TLE Line 2</label>
                            <input class="form-control" type="text" value="<?= $model->line2 ?>" id="line2" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_updated" class="form-control-label">Last Updated At</label>
                            <input class="form-control" type="text" value="<?= date('Y-m-d H:i:s') ?>" id="last_updated" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Current Position</h6>
            </div>
            <div class="card-body">
                <!-- Pass the satellite TLE data to the original tracker -->
                <iframe id="satellite-detail-frame" src="<?= \app\core\Application::url('/sattelite-tracker/simple-tracker.html') ?>" style="width: 100%; height: 500px; border: none;"></iframe>
                
                <script>
                // Store the satellite data to pass to the iframe
                var satelliteData = {
                    name: "<?= htmlspecialchars(addslashes($model->name)) ?>",
                    line1: "<?= htmlspecialchars(addslashes($model->line1)) ?>",
                    line2: "<?= htmlspecialchars(addslashes($model->line2)) ?>"
                };
                
                console.log("Parent page satellite data:", satelliteData);
                
                // Function to update position from iframe data
                function updatePositionDisplay(position) {
                    if (position) {
                        document.getElementById('latitude').value = position.latitude.toFixed(6) + "°";
                        document.getElementById('longitude').value = position.longitude.toFixed(6) + "°";
                        document.getElementById('height').value = position.height.toFixed(2);
                        console.log("Position updated in UI:", position);
                    }
                }
                
                // Function to poll tracker position (backup method)
                function pollTrackerPosition() {
                    try {
                        var iframe = document.getElementById('satellite-detail-frame').contentWindow;
                        if (iframe.trackerAPI && typeof iframe.trackerAPI.getPosition === 'function') {
                            var position = iframe.trackerAPI.getPosition();
                            if (position) {
                                updatePositionDisplay(position);
                            }
                        }
                    } catch(e) {
                        console.log("Error polling position:", e);
                    }
                }
                
                // Function to send data to the iframe
                function sendSatelliteData() {
                    console.log("Sending satellite data to tracker");
                    
                    try {
                        // Get iframe window
                        var iframe = document.getElementById('satellite-detail-frame').contentWindow;
                        
                        // Try the API method first
                        if (iframe.trackerAPI && typeof iframe.trackerAPI.loadSatellite === 'function') {
                            iframe.trackerAPI.loadSatellite(satelliteData);
                            console.log("Data sent using trackerAPI");
                        } else {
                            // Fall back to postMessage
                            iframe.postMessage(satelliteData, '*');
                            console.log("Data sent using postMessage");
                        }
                        
                        // As a last resort, try direct assignment
                        if (iframe.satelliteData === null && typeof iframe.startTracking === 'function') {
                            console.log("Trying direct assignment");
                            iframe.satelliteData = satelliteData;
                            iframe.startTracking();
                        }
                        
                        // Set up polling for position updates
                        setInterval(pollTrackerPosition, 1000);
                        
                    } catch(e) {
                        console.error("Error sending data to iframe:", e);
                    }
                }
                
                // Wait for iframe to load before passing data
                document.getElementById('satellite-detail-frame').onload = function() {
                    console.log("Iframe loaded, waiting for libraries to initialize...");
                    
                    // Give the iframe some time to fully initialize its JS
                    setTimeout(sendSatelliteData, 2000);
                };
                </script>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="latitude" class="form-control-label">Latitude</label>
                            <input class="form-control" type="text" id="latitude" value="Loading..." readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="longitude" class="form-control-label">Longitude</label>
                            <input class="form-control" type="text" id="longitude" value="Loading..." readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="height" class="form-control-label">Height (km)</label>
                            <input class="form-control" type="text" id="height" value="Loading..." readonly>
                        </div>
                    </div>
                </div>
                
                <script>
                // Listen for position updates from the iframe
                window.addEventListener('message', function(event) {
                    console.log("Received message from iframe:", event.data);
                    
                    if (event.data && event.data.type === 'position') {
                        updatePositionDisplay(event.data);
                    }
                });
                </script>
            </div>
        </div>
    </div>
</div> 