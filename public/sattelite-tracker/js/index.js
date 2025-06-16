var map, markers = [];

function createMap() {
  map = new ol.Map({
    target: 'map',
    layers: [
      new ol.layer.Tile({
        source: new ol.source.OSM()
      })
    ],
    view: new ol.View({
      center: ol.proj.fromLonLat([37.41, 8.82]),
      zoom: 4
    })
  });
  
  let featureLayer = new ol.layer.Vector({
    source: new ol.source.Vector()
  });
  
  featureLayer.set("name", "featurelayer");
  map.addLayer(featureLayer);
}

// Returns the feature layer
function getFeatureLayer() {
  let layers = map.getLayers().array_;
  for(let layer of layers) if(layer.get("name") == "featurelayer") return layer;
}

// Returns true if the given marker exists
function markerExists(name) {
  for(let marker of markers) if(marker.values_.name == name) return true;
  return false;
}

// Returns the given marker
function getMarker(name) {
  let featureLayer = getFeatureLayer();
  for(let marker of featureLayer.getSource().getFeatures()) if(marker.values_.name == name) return marker;
  return false;
}

// Draw a satellite on the map
function drawSatellite(name) {
  if(markerExists(name)) {
    updateSatellite(name);
    return;
  }

  try {
    // Get satellite data
    let pos = getCurrentLatLong(name);
    
    // Check if position data is valid
    if (!pos || pos.long === undefined || pos.lat === undefined) {
      console.warn(`Invalid position data for satellite: ${name}`);
      return;
    }

    let featureLayer = getFeatureLayer();
    let marker = new ol.Feature({
      geometry: new ol.geom.Point(ol.proj.fromLonLat([pos.long, pos.lat])),
      name: name
    });
  
  marker.style = {
    label : name,
    pointRadius: 10,
    fillColor: "#ffcc66",
    fillOpacity: 0.8,
    strokeColor: "#cc6633",
    strokeWidth: 2,
    strokeOpacity: 0.8
  };
  
  markers.push(marker);
  featureLayer.getSource().addFeature(marker);
  } catch (error) {
    console.error(`Error creating marker for satellite ${name}: ${error.message}`);
  }
}

// Draw all satellites
function drawAllSatellites() {
  tleArr.forEach(tle => {
    try{
      drawSatellite(tle.name);
    } catch (e) {
      console.error(`Error drawing satellite ${tle.name}: ${e.message}`);
    }
  });
}

// Update the position of an existing satellite marker
function updateSatellite(name) {
  try {
    let pos = getCurrentLatLong(name);
    
    // Check if position data is valid
    if (!pos || pos.long === undefined || pos.lat === undefined) {
      console.warn(`Invalid position data for satellite: ${name}`);
      return;
    }
    
    let featureLayer = getFeatureLayer();
    let marker = getMarker(name);
    
    if (!marker) return;
    
    featureLayer.getSource().removeFeature(marker);
    marker.setGeometry(new ol.geom.Point(ol.proj.fromLonLat([pos.long, pos.lat])));
    marker.style = {
      label : name,
      pointRadius: 10,
      fillColor: "#ffcc66",
      fillOpacity: 0.8,
      strokeColor: "#cc6633",
      strokeWidth: 2,
      strokeOpacity: 0.8
    };
    featureLayer.getSource().addFeature(marker);
  } catch (error) {
    console.error(`Error updating satellite ${name}: ${error.message}`);
  }
}

// Initialize the map
createMap();

// Start tracking
drawAllSatellites();

// Update positions regularly
setInterval(() => {
  try {
    drawAllSatellites();
  } catch (e) {
    console.error("Error updating satellites:", e);
  }
}, 1000);