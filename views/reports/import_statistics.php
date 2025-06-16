<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <!-- Removed header div entirely -->
            <div class="card-body px-0 pb-2">
                <div class="container">
<div class="row">
                        <div class="col-md-4 text-center py-3">
                            <h2 class="font-weight-bolder"><?= number_format($model['summary']['total_imports']) ?></h2>
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Imports</p>
                        </div>
                        <div class="col-md-4 text-center py-3">
                            <h2 class="font-weight-bolder"><?= number_format($model['summary']['total_satellites']) ?></h2>
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Satellites Imported</p>
                        </div>
                        <div class="col-md-4 text-center py-3">
                            <h2 class="font-weight-bolder"><?= number_format($model['summary']['avg_satellites_per_import'], 1) ?></h2>
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Avg Satellites per Import</p>
                        </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6>Satellite Import Activity (Past Year)</h6>
                <p class="text-muted mb-0">Color intensity indicates the number of satellites imported on each day</p>
            </div>
            <div class="card-body px-3 pt-3 pb-2">
                <div id="contribution-calendar" class="px-4 py-3">
                    <!-- Calendar will be rendered here -->
                    <div class="d-flex align-items-center justify-content-center" style="height: 150px;">
                        <div class="text-muted me-2">Loading contribution data...</div>
                        <div class="spinner-border spinner-border-sm text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                                        </div>
                                    </div>
                
                <!-- Fallback message for demonstration mode -->
                <div id="sample-data-notice" class="alert alert-info mt-3" style="display: none;">
                    <p class="mb-0"><strong>Demonstration Mode:</strong> The calendar above shows sample data. 
                    Once you start importing satellite data files (.TXT/.3LE format), this will show your actual import activity.</p>
                </div>
                
                <!-- Debug info to help troubleshoot -->
                <?php if (isset($model['debug'])): ?>
                <div class="debug-panel mt-3">
                    <button id="toggle-debug" class="btn btn-sm btn-outline-secondary">Show Debug Info</button>
                    <div id="debug-info" class="debug-info small text-muted mt-2" style="display: none;">
                        <p>Debug info: 
                            Has data: <?= $model['debug']['has_data'] ? 'Yes' : 'No' ?>, 
                            Daily count: <?= $model['debug']['daily_count'] ?>, 
                            Calendar count: <?= $model['debug']['calendar_count'] ?>, 
                            Time: <?= $model['debug']['timestamp'] ?>
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div class="debug-panel mt-3">
                    <button id="toggle-debug" class="btn btn-sm btn-outline-secondary">Show Debug Info</button>
                    <div id="debug-info" class="debug-info small text-muted mt-2" style="display: none;">
                        <p>Debug info: <?= $model['debugInfo'] ?? 'No debug info available' ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add GitHub-like calendar style -->
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
        margin: 0 5px;
    }
    
    .scale-box {
        width: 10px;
        height: 10px;
        border-radius: 2px;
    }
    
    /* GitHub's actual colors */
    .level-0 { background-color: #ebedf0; }
    .level-1 { background-color: #9be9a8; }
    .level-2 { background-color: #40c463; }
    .level-3 { background-color: #30a14e; }
    .level-4 { background-color: #216e39; }
</style>

<script>
    // Fix for toastr not defined
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof toastr === 'undefined') {
            window.toastr = {
                options: {},
                success: function(msg) { console.log('Success:', msg); },
                error: function(msg) { console.log('Error:', msg); },
                warning: function(msg) { console.log('Warning:', msg); },
                info: function(msg) { console.log('Info:', msg); }
            };
        }
    });
    
    // GitHub-like contribution calendar
    document.addEventListener('DOMContentLoaded', function() {
        try {
            console.log("DOM loaded, initializing contribution calendar");
            
            // Set up debug toggle
            const toggleDebug = document.getElementById('toggle-debug');
            const debugInfo = document.getElementById('debug-info');
            
            if (toggleDebug && debugInfo) {
                toggleDebug.addEventListener('click', function() {
                    if (debugInfo.style.display === 'none') {
                        debugInfo.style.display = 'block';
                        toggleDebug.textContent = 'Hide Debug Info';
                    } else {
                        debugInfo.style.display = 'none';
                        toggleDebug.textContent = 'Show Debug Info';
                    }
                });
            }
            
            // Get and parse data safely
            let calendarData = {};
            let satelliteData = {};
            
            try {
                // Dump raw data to console for debugging
                console.log("Raw calendarData:", <?= json_encode($model['calendarData'] ?? []) ?>);
                console.log("Raw dailyStats:", <?= json_encode($model['dailyStats'] ?? []) ?>);
                console.log("Raw summary:", <?= json_encode($model['summary'] ?? []) ?>);
                console.log("Raw satelliteData:", <?= json_encode($model['satelliteData'] ?? []) ?>);
                
                calendarData = <?= json_encode($model['calendarData'] ?? []) ?>;
                
                // Use the satelliteData directly if available
                if (typeof <?= json_encode($model['satelliteData'] ?? []) ?> === 'object') {
                    satelliteData = <?= json_encode($model['satelliteData'] ?? []) ?>;
                } else {
                    satelliteData = {};
                }
                
                // If satelliteData is empty but dailyStats is not, use the dailyStats to populate it
                if (Object.keys(satelliteData).length === 0) {
                    // Check if dailyStats has data
                    const dailyStats = <?= json_encode($model['dailyStats'] ?? []) ?>;
                    if (Array.isArray(dailyStats) && dailyStats.length > 0) {
                        dailyStats.forEach(stat => {
                            if (stat.date && stat.total_satellites) {
                                satelliteData[stat.date] = parseInt(stat.total_satellites, 10);
                            }
                        });
                        console.log("Created satelliteData from dailyStats:", Object.keys(satelliteData).length);
                    }
                }
            } catch (e) {
                console.error("Error parsing PHP data for calendar:", e);
                calendarData = {};
                satelliteData = {};
            }
            
            console.log("Calendar data keys:", Object.keys(calendarData).length);
            console.log("Satellite data keys:", Object.keys(satelliteData).length);
            
            // If no data, create sample data for demonstration
            let usingSampleData = Object.keys(calendarData).length === 0;
            
            if (usingSampleData) {
                console.log("No calendar data found, using sample data for demonstration");
                
                // Show the sample data notice
                const sampleNotice = document.getElementById('sample-data-notice');
                if (sampleNotice) {
                    sampleNotice.style.display = 'block';
                }
                
                calendarData = {};
                satelliteData = {};
                
                // Generate sample data for the last year
                const today = new Date();
                const startDate = new Date(today);
                startDate.setFullYear(today.getFullYear() - 1);
                
                // Create a date loop from start date to today
                let currentDate = new Date(startDate);
                while (currentDate <= today) {
                    const dateStr = currentDate.toISOString().split('T')[0];
                    
                    // Random data for demonstration
                    // Higher probability of zeros to mimic real-world data
                    const random = Math.random();
                    if (random > 0.85) {
                        const count = Math.floor(Math.random() * 3) + 1;
                        calendarData[dateStr] = count;
                        
                        // Random satellite count, higher than import count
                        const satellites = count * (Math.floor(Math.random() * 10) + 1);
                        satelliteData[dateStr] = satellites;
                    } else {
                        calendarData[dateStr] = 0;
                        satelliteData[dateStr] = 0;
                    }
                    
                    // Move to next day
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                // Add some "hot" days with more activity
                for (let i = 0; i < 5; i++) {
                    const daysAgo = Math.floor(Math.random() * 364);
                    const hotDate = new Date(today);
                    hotDate.setDate(hotDate.getDate() - daysAgo);
                    const hotDateStr = hotDate.toISOString().split('T')[0];
                    
                    const count = Math.floor(Math.random() * 5) + 3;
                    calendarData[hotDateStr] = count;
                    satelliteData[hotDateStr] = count * (Math.floor(Math.random() * 50) + 30);
                }
                
                console.log("Generated sample data points:", Object.keys(calendarData).length);
            } else {
                console.log("Using real data from database with " + Object.keys(calendarData).length + " data points");
                
                // Hide the sample data notice
                const sampleNotice = document.getElementById('sample-data-notice');
                if (sampleNotice) {
                    sampleNotice.style.display = 'none';
                }
                
                // Show some sample data points
                const dataPoints = Object.keys(calendarData);
                if (dataPoints.length > 0) {
                    console.log("Sample data points:");
                    for (let i = 0; i < Math.min(5, dataPoints.length); i++) {
                        const date = dataPoints[i];
                        console.log(`  ${date}: ${calendarData[date]} imports, ${satelliteData[date] || 0} satellites`);
                    }
                }
            }
            
            // Create tooltip element
            const tooltip = document.createElement('div');
            tooltip.className = 'day-tooltip';
            document.body.appendChild(tooltip);
            
            // Find max satellite count for scaling colors
            let maxSatelliteCount = 0;
            for (const date in satelliteData) {
                if (satelliteData[date] > maxSatelliteCount) {
                    maxSatelliteCount = satelliteData[date];
                }
            }
            
            console.log("Max satellite count:", maxSatelliteCount);
            
            // Create the calendar
            const calendarContainer = document.getElementById('contribution-calendar');
            if (!calendarContainer) {
                console.error("Contribution calendar container not found!");
                return;
            }
            
            console.log("Creating GitHub calendar");
            const calendarHTML = createGitHubCalendar(calendarData, satelliteData, maxSatelliteCount);
            calendarContainer.innerHTML = calendarHTML;
            
            // Add tooltip functionality
            const days = document.querySelectorAll('.calendar-day');
            days.forEach(day => {
                day.addEventListener('mouseover', function(e) {
                    const date = this.getAttribute('data-date');
                    const count = this.getAttribute('data-count');
                    const satellites = this.getAttribute('data-satellites') || 0;
                    
                    const dateObj = new Date(date);
                    const formattedDate = dateObj.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long', 
                        day: 'numeric'
                    });
                    
                    if (satellites > 0) {
                        tooltip.innerHTML = `<strong>${formattedDate}</strong><br>${satellites} satellite${satellites == 1 ? '' : 's'} in ${count} import${count == 1 ? '' : 's'}`;
                    } else {
                        tooltip.innerHTML = `<strong>${formattedDate}</strong><br>No satellite imports`;
                    }
                    
                    tooltip.style.display = 'block';
                    tooltip.style.left = (e.pageX + 10) + 'px';
                    tooltip.style.top = (e.pageY + 10) + 'px';
                });
                
                day.addEventListener('mouseout', function() {
                    tooltip.style.display = 'none';
                });
                
                day.addEventListener('mousemove', function(e) {
                    tooltip.style.left = (e.pageX + 10) + 'px';
                    tooltip.style.top = (e.pageY + 10) + 'px';
                });
            });
            
            console.log("Calendar initialization complete with " + days.length + " days");
        } catch (e) {
            console.error("Fatal error in calendar initialization:", e);
            const calendarContainer = document.getElementById('contribution-calendar');
            if (calendarContainer) {
                calendarContainer.innerHTML = `<div class="alert alert-danger">
                    <p>Error loading calendar: ${e.message}</p>
                    <p>Check the browser console for more details.</p>
                </div>`;
            }
        }
    });
    
    function createGitHubCalendar(data, satelliteData, maxSatelliteCount) {
        try {
            console.log("Creating GitHub calendar with data points:", Object.keys(data).length);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Handle empty data case
            if (Object.keys(data).length === 0) {
                console.warn("No dates found in data");
                return '<div class="alert alert-info">No import data available. Sample data will appear shortly.</div>';
            }
            
            // Get date range - always use past year
            const today = new Date();
            const oneYearAgo = new Date();
            oneYearAgo.setFullYear(today.getFullYear() - 1);
            
            console.log("Using date range:", oneYearAgo.toISOString().split('T')[0], "to", today.toISOString().split('T')[0]);
            
            // Build calendar data structure with weeks as columns (GitHub style)
            // First, create an array of all days in the past year
            const allDays = [];
            let currentDate = new Date(oneYearAgo);
            
            while (currentDate <= today) {
                const dateStr = currentDate.toISOString().split('T')[0];
                const count = data[dateStr] || 0;
                const satellites = satelliteData[dateStr] || 0;
                const level = getContributionLevel(satellites, maxSatelliteCount);
                
                allDays.push({
                    date: dateStr,
                    dayOfWeek: currentDate.getDay(), // 0 = Sunday, 6 = Saturday
                    month: currentDate.getMonth(),
                    year: currentDate.getFullYear(),
                    dayOfMonth: currentDate.getDate(),  // Add day of month for better month detection
                    count,
                    satellites,
                    level
                });
                
                // Move to next day
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            // Now organize days into weeks (columns)
            const weeks = [];
            let currentWeek = [];
            
            // GitHub starts weeks on Sunday (0)
            allDays.forEach(day => {
                // If it's Sunday (0) or first day, start a new week
                if (day.dayOfWeek === 0 || currentWeek.length === 0) {
                    if (currentWeek.length > 0) {
                        weeks.push(currentWeek);
                    }
                    currentWeek = [day];
                } else {
                    currentWeek.push(day);
                }
            });
            
            // Add the last week if it has days
            if (currentWeek.length > 0) {
                weeks.push(currentWeek);
            }
            
            // Find start and end weeks for each month to properly center labels
            const monthRanges = {};
            
            // Collect week indices for each month
            weeks.forEach((week, weekIndex) => {
                if (week.length > 0) {
                    const monthYear = `${week[0].year}-${week[0].month}`;
                    
                    if (!monthRanges[monthYear]) {
                        monthRanges[monthYear] = {
                            month: week[0].month,
                            year: week[0].year,
                            name: months[week[0].month],
                            weeks: []
                        };
                    }
                    
                    monthRanges[monthYear].weeks.push(weekIndex);
                }
            });
            
            // Calculate month label positions
            const monthLabels = [];
            const weekWidth = 17; // 14px width + 3px margin
            
            // Convert month ranges to centered label positions
            Object.values(monthRanges).forEach(monthInfo => {
                // Sort weeks to find first and last
                const sortedWeeks = [...monthInfo.weeks].sort((a, b) => a - b);
                const firstWeekIndex = sortedWeeks[0];
                const lastWeekIndex = sortedWeeks[sortedWeeks.length - 1];
                
                // Calculate start and end positions in pixels
                const startPosition = firstWeekIndex * weekWidth;
                const endPosition = (lastWeekIndex + 1) * weekWidth; // +1 to include the full last week
                
                // Calculate center position for the label
                const centerPosition = startPosition + (endPosition - startPosition) / 2 - 10; // -10 to adjust for label width
                
                monthLabels.push({
                    month: monthInfo.month,
                    year: monthInfo.year,
                    name: monthInfo.name,
                    position: centerPosition,
                    monthYear: `${monthInfo.year}-${monthInfo.month}`,
                    weekCount: sortedWeeks.length
                });
            });
            
            // Sort month labels by position
            monthLabels.sort((a, b) => a.position - b.position);
            
            // Now build the calendar HTML with all elements properly aligned
            let calendarHTML = '<div class="contribution-calendar">';
            
            // Day labels on the left with precise positioning
            calendarHTML += '<div class="day-labels">';
            calendarHTML += '<div class="day-label-mon">Mon</div>';
            calendarHTML += '<div class="day-label-wed">Wed</div>';
            calendarHTML += '<div class="day-label-fri">Fri</div>';
            calendarHTML += '</div>';
            
            // Calendar container with month labels and weeks
            calendarHTML += '<div class="calendar-container">';
            calendarHTML += '<div class="calendar-scroll-container">';
            
            // Month labels at the top
            calendarHTML += '<div class="month-labels-row">';
            monthLabels.forEach(label => {
                calendarHTML += `<div class="month-label" style="left: ${label.position}px;">${label.name}</div>`;
            });
            calendarHTML += '</div>';
            
            // Weeks grid
            calendarHTML += '<div class="calendar-grid">';
            
            // For each week column
            weeks.forEach(week => {
                // Create the week column
                calendarHTML += '<div class="calendar-week">';
                
                // Fill in empty cells for partial weeks at the beginning
                const firstDayOfWeek = week[0].dayOfWeek;
                for (let i = 0; i < firstDayOfWeek; i++) {
                    calendarHTML += '<div class="calendar-day" style="visibility:hidden;"></div>';
                }
                
                // Add each day in this week
                week.forEach(day => {
                    calendarHTML += `<div class="calendar-day level-${day.level}" 
                        data-date="${day.date}" 
                        data-count="${day.count}"
                        data-satellites="${day.satellites}"></div>`;
                });
                
                // Fill in empty cells for partial weeks at the end
                const lastDayOfWeek = week[week.length - 1].dayOfWeek;
                for (let i = lastDayOfWeek + 1; i < 7; i++) {
                    calendarHTML += '<div class="calendar-day" style="visibility:hidden;"></div>';
                }
                
                calendarHTML += '</div>';
            });
            
            calendarHTML += '</div>'; // End calendar-grid
            calendarHTML += '</div>'; // End calendar-scroll-container
            calendarHTML += '</div>'; // End calendar-container
            
            // Add color scale below the calendar (left-aligned)
            calendarHTML += `<div class="color-scale">
                <span class="color-scale-label">Fewer satellites</span>
                <div class="color-scale-boxes">
                    <div class="scale-box level-0"></div>
                    <div class="scale-box level-1"></div>
                    <div class="scale-box level-2"></div>
                    <div class="scale-box level-3"></div>
                    <div class="scale-box level-4"></div>
                </div>
                <span class="color-scale-label">More satellites</span>
            </div>`;
            
            calendarHTML += '</div>'; // End contribution-calendar
            return calendarHTML;
        } catch (e) {
            console.error("Error creating GitHub calendar:", e);
            return `<div class="alert alert-danger">Error rendering calendar: ${e.message}</div>`;
        }
    }
    
    function getContributionLevel(count, maxCount) {
        if (count === 0) return 0;
        
        // Create a logarithmic scale for better distribution
        const maxLevel = 4;
        if (maxCount <= 1) return 1;
        
        const ratio = Math.log(count + 1) / Math.log(maxCount + 1);
        const level = Math.ceil(ratio * maxLevel);
        return Math.min(level, maxLevel);
    }
</script> 