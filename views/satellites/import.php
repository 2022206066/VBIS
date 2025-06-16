<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Import Satellites</h6>
            </div>
            <div class="card-body">
                <form action="/VBIS-main/public/processImport" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tleFile" class="form-control-label">Satellite Data File</label>
                                <input class="form-control" type="file" name="tleFile" id="tleFile" accept=".txt,.xml" required>
                                <small class="text-muted">Upload a .txt file containing TLE or 3LE data, or .xml file with satellite data</small>
                                <div class="alert alert-warning mt-2">
                                    <small><strong>Note:</strong> XML import support is a work in progress. For reliable imports, please use TXT format.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category" class="form-control-label">Default Category (Optional)</label>
                                <input class="form-control" type="text" name="category" id="category" placeholder="e.g., Weather, Navigation, Communication">
                                <small class="text-muted">Optional: Used when category isn't available in the file</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_categorize" id="auto_categorize" value="1" checked>
                                <label class="form-check-label" for="auto_categorize">
                                    Auto-categorize from data when possible
                                </label>
                                <div><small class="text-muted">Automatically categorizes satellites based on their names</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Import Satellites</button>
                            <a href="<?= \app\core\Application::url('/satellites') ?>" class="btn btn-secondary">Cancel</a>
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
            <div class="card-header pb-0">
                <h6>Supported File Formats</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5>TLE Format (.txt)</h5>
                        <p>TLE (Two-Line Element Set) is a data format used to convey orbital elements for Earth-orbiting objects.</p>
                        <p>Each satellite is represented by three lines:</p>
                        <pre>
ISS (ZARYA)
1 25544U 98067A   20130.40187346  .00000892  00000-0  24043-4 0  9995
2 25544  51.6445 180.4320 0001102 260.4037 190.9963 15.49359311226009
                        </pre>
                        <p>The first line is the name of the satellite, and the next two lines contain the orbital elements.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>3LE Format (.txt)</h5>
                        <p>3LE (Three-Line Element Set) is a variant of TLE used by some services like Space-Track.org.</p>
                        <p>Each satellite is represented by three lines with a "0" prefix for the name:</p>
                        <pre>
0 VANGUARD 1
1 00005U 58002B   25157.90932835  .00000050  00000-0  37874-4 0  9997
2 00005  34.2615  32.6027 1841749 246.4822  93.2088 10.85926385402485
                        </pre>
                        <p>The system automatically detects and handles this format.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>XML Format (.xml)</h5>
                        <p>XML files containing satellite data in the OMM (Orbit Mean-Elements Message) format are supported.</p>
                        <p>The system will extract the following essential data:</p>
                        <ul>
                            <li>Object name</li>
                            <li>Two-line element data (converted from XML elements)</li>
                            <li>Additional metadata when available</li>
                        </ul>
                        <p>XML files from sources like Space-Track.org are compatible with this system.</p>
                        <p>For mixed files containing satellites of different types, the system will attempt to categorize them automatically.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 