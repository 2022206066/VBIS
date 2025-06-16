/* description: Parses a string of TLEs into an array of tle objects, which follow the format of {name, line1, line2}
 * parameters: tles{string}: A string of TLEs
 * return: tleArr{array}: An array of TLE objects
 */
function parseTles(tles) {
  let tleArr = []
  linesArr = tles.split("\n")
  let tle = "placeholder"
  linesArr.forEach(line => {
    if(line.split(" ")[0] === "1") {
      tle.line1 = line
    }
    else if(line.split(" ")[0] === "2") {
      tle.line2 = line
    }
    else {
      if(tle !== "placeholder") tleArr.push(tle)
      tle = {}
      tle.name = line
    }
  })
  return tleArr
}

/* description: Calculates the lat/long/height for the given TLE name
 * parameters: name{string}: The name of the TLE
 * return: latlon{object}: An object containing the sattelites lat/long/height
 */
function getCurrentLatLong(name) {
  let tle = {}
  for(let item of tleArr) {
    if(item.name == name) {
      tle = item
      break
    }
  }
  try {
    let satrec = satellite.twoline2satrec(tle.line1, tle.line2)

    //  Or you can use a JavaScript Date
    var positionAndVelocity = satellite.propagate(satrec, new Date())
    
    // The position_velocity result is a key-value pair of ECI coordinates.
    // These are the base results from which all other coordinates are derived.
    var positionEci = positionAndVelocity.position,
        velocityEci = positionAndVelocity.velocity
    
    // You will need GMST for some of the coordinate transforms.
    // http://en.wikipedia.org/wiki/Sidereal_time#Definition
    var gmst = satellite.gstime(new Date());
    
    var positionEcf   = satellite.eciToEcf(positionEci, gmst),
        positionGd    = satellite.eciToGeodetic(positionEci, gmst)
    
    // Geodetic coords are accessed via `longitude`, `latitude`, `height`.
    var longitude = positionGd.longitude,
        latitude  = positionGd.latitude,
        height    = positionGd.height
    
    //  Convert the RADIANS to DEGREES for pretty printing (appends "N", "S", "E", "W", etc).
    var longitudeStr = satellite.degreesLong(longitude),
        latitudeStr  = satellite.degreesLat(latitude)

    //console.log(longitudeStr)
    
    return {lat: latitudeStr, long: longitudeStr, height: height}
  } catch(e) {
    throw(e)
  }
}

/* description: Converts tleArr to be an array of current sattelite positions
 * return: posArr{arr}: An array of pos objects with the format {name, lat, long, height}
 */
function tleArrToPosArr() {
  let posArr = []
  tleArr.forEach(tle => {
    try{
      let pos = getCurrentLatLong(tle.name)
      posArr.push({
        name: tle.name,
        lat: pos.lat,
        long: pos.long,
        height: pos.height
      })
    } catch {
      console.log(`Error parsing ${tle.name}`)
    }
  })
  return posArr
}

/* description: Gets an object from the poArr with the specified name
 * parameters: name{string}: The name of the satellite that you would like to retrieve data for
 * return: pos{object}: An object of the format {name, lat. long, height}
 */
function getPos(name) {
  let pos = {}
  for(let item of posArr) {
    if(item.name == name) {
      pos = item
      break
    }
  }
  return pos
}

var tleArr = parseTles(tles)
//var posArr = tleArrToPosArr()