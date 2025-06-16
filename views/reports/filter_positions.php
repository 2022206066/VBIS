<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="background-color: #f5365c; border-color: #f5365c; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
            <div class="card-body">
                <h5 style="color: white; font-weight: 600; letter-spacing: -0.025rem; margin-bottom: 0;">⚠️ THIS FUNCTION IS SUSPENDED DUE TO LOCAL HOSTING LIMITING ABILITIES TO PERFORM REAL-TIME CALCULATIONS ⚠️</h5>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Satellites Near Me</h6>
            </div>
            <div class="card-body">
                <form action="/filterPositions" method="get">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="start_date" class="form-control-label">Start Date</label>
                                <input class="form-control" type="date" name="start_date" id="start_date" value="<?= $model['startDate'] ?>">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="end_date" class="form-control-label">End Date</label>
                                <input class="form-control" type="date" name="end_date" id="end_date" value="<?= $model['endDate'] ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mb-3">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Filtered Positions</h6>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportToCSV()">Export CSV</button>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0" id="positionsTable">
                        <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Satellite</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Category</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Latitude</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Longitude</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Height (km)</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Timestamp</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($model['positions'] as $position): ?>
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm"><?= $position['name'] ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0"><?= $position['category'] ?></p>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold"><?= $position['latitude'] ?></span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold"><?= $position['longitude'] ?></span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold"><?= $position['height'] ?></span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold"><?= $position['timestamp'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Position Distribution Map</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div id="map" style="height: 500px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to export table data to CSV
    function exportToCSV() {
        var table = document.getElementById("positionsTable");
        var rows = table.querySelectorAll("tr");
        var csv = [];
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (var j = 0; j < cols.length; j++) {
                // Get text content and clean it
                var text = cols[j].textContent.trim().replace(/\s+/g, ' ');
                // Escape double quotes
                text = text.replace(/"/g, '""');
                // Add quotes around the field
                row.push('"' + text + '"');
            }
            
            csv.push(row.join(","));
        }
        
        // Download CSV file
        downloadCSV(csv.join("\n"), 'satellite_positions.csv');
    }
    
    function downloadCSV(csv, filename) {
        var csvFile;
        var downloadLink;
        
        // Create CSV file
        csvFile = new Blob([csv], {type: "text/csv"});
        
        // Create download link
        downloadLink = document.createElement("a");
        
        // File name
        downloadLink.download = filename;
        
        // Create a link to the file
        downloadLink.href = window.URL.createObjectURL(csvFile);
        
        // Hide download link
        downloadLink.style.display = "none";
        
        // Add the link to DOM
        document.body.appendChild(downloadLink);
        
        // Click download link
        downloadLink.click();
    }
    
    // Initialize the map
    var map = new ol.Map({
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
    
    // Create a vector layer for the positions
    var vectorSource = new ol.source.Vector();
    var vectorLayer = new ol.layer.Vector({
        source: vectorSource,
        style: function(feature) {
            return new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 5,
                    fill: new ol.style.Fill({
                        color: feature.get('color')
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#ffffff',
                        width: 1
                    })
                }),
                text: new ol.style.Text({
                    text: feature.get('name'),
                    font: '12px Calibri,sans-serif',
                    fill: new ol.style.Fill({
                        color: '#000'
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#fff',
                        width: 2
                    }),
                    offsetY: -15
                })
            });
        }
    });
    map.addLayer(vectorLayer);
    
    // Add position markers to the map
    <?php foreach ($model['positions'] as $position): ?>
    var feature = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([<?= $position['longitude'] ?>, <?= $position['latitude'] ?>])),
        name: '<?= $position['name'] ?>',
        color: getCategoryColor('<?= $position['category'] ?>')
    });
    vectorSource.addFeature(feature);
    <?php endforeach; ?>
    
    function getCategoryColor(category) {
        switch(category) {
            case 'Weather':
                return '#66ccff';
            case 'Space Station':
                return '#ff6666';
            default:
                return '#ffcc66';
        }
    }
</script> 