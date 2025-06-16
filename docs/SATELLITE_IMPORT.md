# Satellite Import Documentation

## Overview

The VBIS satellite import system supports multiple file formats for importing satellite data into the tracking application:

1. **TLE Format**: Standard Two-Line Element Set format
2. **3LE Format**: Three-Line Element Set format (with a "0" prefix for names)
3. **XML Format**: Multiple XML formats supported, including Space-Track.org exports

## File Formats

### TLE Format (.txt)

Standard TLE format consists of three lines per satellite:

```
ISS (ZARYA)
1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995
2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009
```

- First line: Satellite name
- Second line: Line 1 of TLE data (starts with "1")
- Third line: Line 2 of TLE data (starts with "2")

### 3LE Format (.txt)

Modified TLE format with "0" prefix for name line:

```
0 VANGUARD 1
1 00005U 58002B   25157.90932835  .00000050  00000-0  37874-4 0  9997
2 00005  34.2615  32.6027 1841749 246.4822  93.2088 10.85926385402485
```

- First line: Satellite name with "0 " prefix
- Second line: Line 1 of TLE data (starts with "1")
- Third line: Line 2 of TLE data (starts with "2")

### XML Format (.xml)

Multiple XML formats are supported:

1. **Space-Track.org XML**:
   ```xml
   <tle>
     <OBJECT_NAME>ISS (ZARYA)</OBJECT_NAME>
     <LINE1>1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995</LINE1>
     <LINE2>2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009</LINE2>
     <OBJECT_TYPE>PAYLOAD</OBJECT_TYPE>
   </tle>
   ```

2. **OMM (Orbit Mean-Elements Message) XML**:
   ```xml
   <segment>
     <metadata>
       <OBJECT_NAME>ISS (ZARYA)</OBJECT_NAME>
       <OBJECT_TYPE>PAYLOAD</OBJECT_TYPE>
     </metadata>
     <data>
       <meanElements>
         <!-- Orbital elements here -->
       </meanElements>
     </data>
   </segment>
   ```

3. **Generic Satellite XML**:
   ```xml
   <satellite>
     <name>ISS (ZARYA)</name>
     <line1>1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995</line1>
     <line2>2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009</line2>
     <category>Space Station</category>
   </satellite>
   ```

## Auto-Categorization

The system supports automatic categorization of satellites based on their names. When enabled, satellites will be categorized into the following groups:

- Weather (NOAA, METOP, GOES, etc.)
- Navigation (GPS, GLONASS, GALILEO, etc.)
- Communication (INTELSAT, IRIDIUM, STARLINK, etc.)
- Earth Observation (LANDSAT, SENTINEL, etc.)
- Space Station (ISS, TIANGONG, etc.)
- Science (HUBBLE, JWST, etc.)
- Military (USA, NROL, etc.)
- Amateur (AMSAT, OSCAR, etc.)

If a satellite name doesn't match any category rules, it will be placed in the "Uncategorized" category.

## Import Process

1. Upload your file (TLE, 3LE, or XML) through the satellite import form
2. The system automatically detects the file format
3. Satellite data is parsed according to the detected format
4. If auto-categorization is enabled, satellites are categorized based on their names
5. Satellites are imported into the database
6. A success message with the number of imported satellites is displayed

## Known Issues and Troubleshooting

### Database Connection Issues

If you encounter database connection errors:
- Make sure your MySQL server is running
- Check the database credentials in `core/DbConnection.php`
- Run the database test at `/db-test.php` to diagnose connection issues

### File Format Problems

If your import fails or no satellites are imported:
- Check that your file follows one of the supported formats
- Ensure the file has the correct extension (.txt for TLE/3LE, .xml for XML)
- Try the test import tool at `/test-import.php` to validate your file format

### Duplicate Entries

The system will skip duplicate satellite entries (same name and TLE data).

## Integration with Satellite Tracker

The imported satellite data can be viewed in the satellite tracker at `/sattelite-tracker/`.

## API Access

Satellite data can be exported via API endpoints:
- JSON format: `/exportJson` 
- XML format: `/exportXml` 