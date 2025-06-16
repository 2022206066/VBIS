<?php
// Simple script to test database connection and retrieve import data

// Database connection settings
$dbHost = 'localhost';
$dbName = 'satellite_tracker';  // Updated to match core database name
$dbUser = 'root';  // Change to your actual database user
$dbPass = '';      // Change to your actual database password

// Connect to database
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

echo "<h1>Testing Import Data Retrieval</h1>";
echo "<p>Connected to database successfully.</p>";

// Check if the imported_files table exists
$result = $mysqli->query("SHOW TABLES LIKE 'imported_files'");
if ($result && $result->num_rows > 0) {
    echo "<p>Table 'imported_files' exists.</p>";
} else {
    echo "<p>Table 'imported_files' does not exist!</p>";
    exit;
}

// Count records
$countResult = $mysqli->query("SELECT COUNT(*) as count FROM imported_files");
if ($countResult && $countResult->num_rows > 0) {
    $row = $countResult->fetch_assoc();
    echo "<p>Found " . $row['count'] . " records in the imported_files table.</p>";
} else {
    echo "<p>Error counting records.</p>";
    exit;
}

// Get records
$allRecordsQuery = "SELECT id, filename, uploaded_by, upload_date, satellite_count FROM imported_files ORDER BY upload_date DESC LIMIT 10";
$result = $mysqli->query($allRecordsQuery);

if ($result && $result->num_rows > 0) {
    echo "<h2>Latest 10 Records:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Uploaded By</th><th>Upload Date</th><th>Satellite Count</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['filename'] . "</td>";
        echo "<td>" . $row['uploaded_by'] . "</td>";
        echo "<td>" . $row['upload_date'] . "</td>";
        echo "<td>" . $row['satellite_count'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No records found or error executing query.</p>";
}

// Get aggregated data by date
$dailyQuery = "SELECT 
    DATE(upload_date) as date,
    COUNT(*) as count,
    SUM(satellite_count) as total_satellites
    FROM imported_files 
    GROUP BY DATE(upload_date)
    ORDER BY date DESC
    LIMIT 10";
    
$result = $mysqli->query($dailyQuery);

if ($result && $result->num_rows > 0) {
    echo "<h2>Daily Statistics (Last 10 Days with Data):</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Date</th><th>Import Count</th><th>Total Satellites</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['total_satellites'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Create JSON structure for calendar
    $calendarData = [];
    $satelliteData = [];
    
    // Get all dates for the calendar
    $allDatesQuery = "SELECT 
        DATE(upload_date) as date,
        COUNT(*) as count,
        SUM(satellite_count) as total_satellites
        FROM imported_files 
        GROUP BY DATE(upload_date)
        ORDER BY date ASC";
        
    $allDatesResult = $mysqli->query($allDatesQuery);
    
    if ($allDatesResult && $allDatesResult->num_rows > 0) {
        while ($row = $allDatesResult->fetch_assoc()) {
            $calendarData[$row['date']] = (int)$row['count'];
            $satelliteData[$row['date']] = (int)$row['total_satellites'];
        }
    }
    
    echo "<h2>Calendar Data JSON:</h2>";
    echo "<pre>";
    echo json_encode($calendarData, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    echo "<h2>Satellite Data JSON:</h2>";
    echo "<pre>";
    echo json_encode($satelliteData, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Display a simplified GitHub calendar
    echo "<h2>GitHub-Style Contribution Calendar:</h2>";
    
    // Include CSS and JS for the calendar
    ?>
    <style>
        .contribution-calendar {
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            margin-bottom: 15px;
            min-height: 120px;
            position: relative;
            width: 100%;
        }
        
        .calendar-container {
            position: relative;
            margin-left: 30px; /* Space for day labels */
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .calendar-scroll-container {
            position: relative;
            min-width: 100%;
        }
        
        .month-labels-row {
            display: flex;
            flex-direction: row;
            height: 20px;
            margin-bottom: 5px;
            position: relative;
        }
        
        .month-label {
            position: absolute;
            font-size: 12px;
            color: #586069;
        }
        
        .calendar-grid {
            display: flex;
            flex-direction: row;
            gap: 2px;
        }
        
        .calendar-week {
            display: flex;
            flex-direction: column;
            gap: 2px;
            width: 14px;
            margin-right: 3px;
        }
        
        .calendar-day {
            width: 10px;
            height: 10px;
            margin: 2px;
            border-radius: 2px;
            background-color: #ebedf0;
            transition: background-color 0.1s ease-in-out;
        }
        
        .calendar-day:hover {
            border: 1px solid rgba(27,31,35,0.06);
            cursor: pointer;
        }
        
        .day-labels {
            position: absolute;
            left: 0;
            top: 25px; /* Below month labels */
            display: flex;
            flex-direction: column;
            text-align: right;
            font-size: 10px;
            color: #586069;
            z-index: 2;
        }
        
        /* Position day labels to align exactly with rows */
        .day-label-mon {
            position: relative;
            top: 16px; /* Align with Monday (position 1) */
        }
        
        .day-label-wed {
            position: relative;
            top: 30px; /* Align with Wednesday (position 3) - Fixed */
        }
        
        .day-label-fri {
            position: relative;
            top: 45px; /* Align with Friday (position 5) */
        }
        
        .day-tooltip {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .color-scale {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-top: 15px;
            font-size: 12px;
            color: #586069;
            clear: both;
            width: 100%;
        }
        
        .color-scale-label {
            margin-right: 5px;
        }
        
        .color-scale-boxes {
            display: flex;
            gap: 2px;
        }
        
        .color-scale-box {
            width: 10px;
            height: 10px;
            border-radius: 2px;
        }
    </style>
    
    <div class="contribution-calendar">
        <div class="calendar-container">
            <div class="day-labels">
                <div class="day-label-mon">Mon</div>
                <div class="day-label-wed">Wed</div>
                <div class="day-label-fri">Fri</div>
            </div>
            <div class="calendar-scroll-container">
                <div class="month-labels-row" id="month-labels"></div>
                <div class="calendar-grid" id="calendar-grid"></div>
            </div>
        </div>
        <div class="color-scale">
            <span class="color-scale-label">Less</span>
            <div class="color-scale-boxes">
                <div class="color-scale-box" style="background-color: #ebedf0;"></div>
                <div class="color-scale-box" style="background-color: #c6e48b;"></div>
                <div class="color-scale-box" style="background-color: #7bc96f;"></div>
                <div class="color-scale-box" style="background-color: #239a3b;"></div>
                <div class="color-scale-box" style="background-color: #196127;"></div>
            </div>
            <span class="color-scale-label" style="margin-left: 5px;">More</span>
        </div>
    </div>
    
    <div class="day-tooltip" id="day-tooltip"></div>
    
    <script>
        // Calendar data from PHP
        const calendarData = <?php echo json_encode($calendarData); ?>;
        const satelliteData = <?php echo json_encode($satelliteData); ?>;
        
        // Get date range for calendar
        const dates = Object.keys(calendarData).sort();
        if (dates.length === 0) {
            document.getElementById('calendar-grid').innerHTML = '<p>No data available for calendar view</p>';
        } else {
            const startDate = new Date(dates[0]);
            const endDate = new Date();
            
            // Calculate week offsets
            const startDay = startDate.getDay() || 7; // Convert Sunday (0) to 7
            startDate.setDate(startDate.getDate() - (startDay - 1)); // Adjust to start week from Monday
            
            // Generate calendar
            const weeks = [];
            let currentDate = new Date(startDate);
            
            // Find max value for scaling
            const maxValue = Math.max(...Object.values(calendarData), 1);
            
            // Generate weeks until we reach endDate
            while (currentDate <= endDate) {
                const week = [];
                for (let i = 0; i < 7; i++) {
                    const dateString = currentDate.toISOString().split('T')[0];
                    const count = calendarData[dateString] || 0;
                    const satellites = satelliteData[dateString] || 0;
                    
                    // Color based on activity level (0-4)
                    let colorLevel = 0;
                    if (count > 0) {
                        colorLevel = Math.ceil((count / maxValue) * 4);
                        colorLevel = Math.min(colorLevel, 4); // Cap at 4
                    }
                    
                    week.push({
                        date: dateString,
                        count: count,
                        satellites: satellites,
                        colorLevel: colorLevel
                    });
                    
                    // Move to next day
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                weeks.push(week);
            }
            
            // Render month labels
            const monthLabels = document.getElementById('month-labels');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthPositions = new Set();
            
            weeks.forEach((week, weekIndex) => {
                week.forEach(day => {
                    if (day.date) {
                        const date = new Date(day.date);
                        const month = date.getMonth();
                        const firstDayOfMonth = new Date(date.getFullYear(), month, 1);
                        const firstDayIsMonday = firstDayOfMonth.getDay() === 1;
                        
                        // If this is the first day of the month and a Monday (or close to the start)
                        if ((date.getDate() === 1 && firstDayIsMonday) || 
                            (date.getDate() <= 7 && weekIndex % 4 === 0)) {
                            const monthKey = `${date.getFullYear()}-${month}`;
                            if (!monthPositions.has(monthKey)) {
                                monthPositions.add(monthKey);
                                const monthLabel = document.createElement('div');
                                monthLabel.className = 'month-label';
                                monthLabel.textContent = months[month];
                                monthLabel.style.left = `${weekIndex * 17}px`; // Position based on week index
                                monthLabels.appendChild(monthLabel);
                            }
                        }
                    }
                });
            });
            
            // Render calendar grid
            const calendarGrid = document.getElementById('calendar-grid');
            const colors = ['#ebedf0', '#c6e48b', '#7bc96f', '#239a3b', '#196127'];
            
            weeks.forEach(week => {
                const weekEl = document.createElement('div');
                weekEl.className = 'calendar-week';
                
                week.forEach(day => {
                    const dayEl = document.createElement('div');
                    dayEl.className = 'calendar-day';
                    dayEl.style.backgroundColor = colors[day.colorLevel];
                    dayEl.setAttribute('data-date', day.date);
                    dayEl.setAttribute('data-count', day.count);
                    dayEl.setAttribute('data-satellites', day.satellites);
                    
                    // Add tooltip event
                    dayEl.addEventListener('mouseover', showTooltip);
                    dayEl.addEventListener('mouseout', hideTooltip);
                    
                    weekEl.appendChild(dayEl);
                });
                
                calendarGrid.appendChild(weekEl);
            });
        }
        
        // Tooltip functions
        function showTooltip(e) {
            const tooltip = document.getElementById('day-tooltip');
            const day = e.target;
            const date = day.getAttribute('data-date');
            const count = day.getAttribute('data-count');
            const satellites = day.getAttribute('data-satellites');
            
            if (date) {
                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                
                tooltip.innerHTML = `
                    <div><strong>${formattedDate}</strong></div>
                    <div>${count} import${count !== '1' ? 's' : ''}</div>
                    <div>${satellites} satellite${satellites !== '1' ? 's' : ''}</div>
                `;
                
                // Position the tooltip
                tooltip.style.display = 'block';
                tooltip.style.left = (e.pageX + 10) + 'px';
                tooltip.style.top = (e.pageY + 10) + 'px';
            }
        }
        
        function hideTooltip() {
            document.getElementById('day-tooltip').style.display = 'none';
        }
    </script>
    <?php
} else {
    echo "<p>No daily statistics found.</p>";
}
?> 