<?php
// Import flag to check if we should show modal
$showImportModal = false;
$importSummary = \app\core\Application::$app->session->get('importSummary');
if ($importSummary && is_array($importSummary) && isset($importSummary['filename'])) {
    // Valid import data found
    $showImportModal = true;
    // Clear from session immediately to prevent showing again on refresh
    \app\core\Application::$app->session->delete('importSummary');
}

// Duplicate removal flag to check if we should show that modal
$showRemovalModal = false;
$removalSummary = \app\core\Application::$app->session->get('removalSummary');
if ($removalSummary && is_array($removalSummary) && isset($removalSummary['duplicatesRemoved'])) {
    // Valid removal data found
    $showRemovalModal = true;
    // Clear from session immediately to prevent showing again on refresh
    \app\core\Application::$app->session->delete('removalSummary');
}
?>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Satellites</h6>
                <div>
                    <a href="<?= \app\core\Application::url('/exportJson') ?>" class="btn btn-sm btn-outline-primary">Export JSON</a>
                    <a href="<?= \app\core\Application::url('/exportXml') ?>" class="btn btn-sm btn-outline-secondary">Export XML</a>
                    <?php if (\app\core\Application::$app->session->isInRole('Administrator')): ?>
                    <a href="<?= \app\core\Application::url('/importSatellites') ?>" class="btn btn-sm btn-primary">Import Satellites</a>
                    <a href="<?= \app\core\Application::url('/removeDuplicates') ?>" class="btn btn-sm btn-danger" style="background-color: #f5365c; border-color: #f5365c; color: white; font-weight: 600; letter-spacing: -0.025rem; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08); transition: all 0.15s ease;">Delete Duplicates</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0" style="max-height: 500px; overflow-y: auto;">
                    <table class="table align-items-center mb-0">
                        <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Added By</th>
                            <th class="text-secondary opacity-7"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $activeCategorySatellites = $model['satellitesByCategory'][$model['activeCategory']] ?? [];
                        foreach ($activeCategorySatellites as $satellite): 
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($satellite['name']) ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="badge badge-sm bg-gradient-success">User ID: <?= $satellite['added_by'] ?></span>
                                </td>
                                <td class="align-middle">
                                    <a href="<?= \app\core\Application::url('/satelliteDetail?id=' . $satellite['id']) ?>" class="text-secondary font-weight-bold text-xs"
                                       data-toggle="tooltip" data-original-title="View details">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activeCategorySatellites)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <p class="text-muted">No satellites found in this category.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
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
                <h6>Satellite Categories</h6>
            </div>
            <div class="card-body pb-2">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="categorySelect" class="form-control-label">Select Category:</label>
                            <select id="categorySelect" class="form-control" onchange="window.location.href=this.value">
                                <?php foreach ($model['categories'] as $category): ?>
                                <option value="<?= \app\core\Application::url('/satellites?category=' . urlencode($category)) ?>" 
                                        <?= ($category === $model['activeCategory']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?> 
                                    (<?= count($model['satellitesByCategory'][$category]) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="chart-pie" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php if (\app\core\Application::$app->session->isInRole('Administrator')): ?>
                <hr>
                <div class="p-4">
                    <h5>Reassign Satellites Tool</h5>
                    <p class="text-sm">Use this tool to transfer satellites from one user to another (useful before deleting a user account).</p>
                    
                    <?php
                    // Connect to database
                    $db = new \app\core\Database();
                    $conn = $db->getConnection();
                    
                    // Handle form submission directly
                    if (isset($_POST['from_user']) && isset($_POST['to_user'])) {
                        $fromUserId = (int)$_POST['from_user'];
                        $toUserId = (int)$_POST['to_user'];
                        
                        // Count satellites to be reassigned
                        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
                        $countStmt->bind_param("i", $fromUserId);
                        $countStmt->execute();
                        $countResult = $countStmt->get_result();
                        $satelliteCount = $countResult->fetch_assoc()['count'];
                        
                        if ($satelliteCount === 0) {
                            echo '<div class="alert alert-warning">No satellites found for user ID ' . $fromUserId . ' to reassign.</div>';
                        } else {
                            // Update satellites
                            $updateStmt = $conn->prepare("UPDATE satellites SET added_by = ? WHERE added_by = ?");
                            $updateStmt->bind_param("ii", $toUserId, $fromUserId);
                            
                            if ($updateStmt->execute()) {
                                $affectedRows = $updateStmt->affected_rows;
                                echo '<div class="alert alert-success">Successfully reassigned ' . $affectedRows . ' satellites from user ID ' . $fromUserId . ' to user ID ' . $toUserId . '.</div>';
                            } else {
                                echo '<div class="alert alert-danger">Error reassigning satellites: ' . $conn->error . '</div>';
                            }
                        }
                    }
                    
                    // Get all users
                    $users = [];
                    $result = $conn->query("SELECT id, email, first_name, last_name FROM users ORDER BY id");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $users[] = $row;
                        }
                    }
                    
                    // Get satellite counts for each user
                    $satelliteCounts = [];
                    $countResult = $conn->query("SELECT added_by, COUNT(*) as count FROM satellites GROUP BY added_by");
                    if ($countResult) {
                        while ($row = $countResult->fetch_assoc()) {
                            $satelliteCounts[$row['added_by']] = $row['count'];
                        }
                    }
                    ?>
                    
                    <form method="post" action="<?= \app\core\Application::url('/reassignSatellites') ?>">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="form-control-label">From User (Source)</label>
                                    <select name="from_user" class="form-control" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($users as $user): 
                                            $satelliteCount = isset($satelliteCounts[$user['id']]) ? $satelliteCounts[$user['id']] : 0;
                                            if ($satelliteCount > 0): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> 
                                                (ID: <?= $user['id'] ?>, Satellites: <?= $satelliteCount ?>)
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="form-control-label">To User (Destination)</label>
                                    <select name="to_user" class="form-control" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>">
                                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> 
                                            (ID: <?= $user['id'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Reassign</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // SVG-based pie chart implementation (no Chart.js dependency)
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var container = document.getElementById("chart-pie");
            if (!container) {
                console.error("Chart container not found");
                return;
            }
            
            // Clear any existing content
            container.innerHTML = '';
            
            // Get the data
        var categories = <?= json_encode($model['categories']) ?>;
        var categoryData = [];
            
            <?php foreach ($model['categories'] as $category): ?>
            categoryData.push(<?= count($model['satellitesByCategory'][$category]) ?>);
            <?php endforeach; ?>
        
            // Basic colors
            var colors = [
                '#5e72e4', '#2dce89', '#fb6340', '#11cdef', '#f5365c',
                '#8965e0', '#ffd600', '#f3a4b5', '#2bffc6', '#5603ad'
            ];
            
            // Calculate total
            var total = categoryData.reduce(function(a, b) { return a + b; }, 0);
            
            // No data case
            if (total === 0 || categories.length === 0) {
                container.innerHTML = '<div style="padding:20px;text-align:center;">No data available</div>';
                return;
            }
            
            // Create custom SVG pie chart
            var size = 200;
            var radius = size / 2;
            var centerX = radius;
            var centerY = radius;
            
            // Create SVG element
            var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("width", size);
            svg.setAttribute("height", size);
            svg.setAttribute("viewBox", "0 0 " + size + " " + size);
            container.appendChild(svg);
            
            // Create a group for the pie chart
            var g = document.createElementNS("http://www.w3.org/2000/svg", "g");
            svg.appendChild(g);
            
            // Create pie slices
            var currentAngle = 0;
            var legend = document.createElement('div');
            legend.style.marginTop = '10px';
            
            for (var i = 0; i < categories.length; i++) {
                var value = categoryData[i];
                var percentage = (value / total) * 100;
                var angle = (value / total) * 360;
                var color = colors[i % colors.length];
                
                // Calculate coordinates for arc
                var startX = centerX + radius * Math.cos(currentAngle * Math.PI / 180);
                var startY = centerY + radius * Math.sin(currentAngle * Math.PI / 180);
                
                currentAngle += angle;
                
                var endX = centerX + radius * Math.cos(currentAngle * Math.PI / 180);
                var endY = centerY + radius * Math.sin(currentAngle * Math.PI / 180);
                
                // Create path for slice
                var path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                
                // Arc path
                var largeArcFlag = angle > 180 ? 1 : 0;
                var d = [
                    "M", centerX, centerY,
                    "L", startX, startY,
                    "A", radius, radius, 0, largeArcFlag, 1, endX, endY,
                    "Z"
                ].join(" ");
                
                path.setAttribute("d", d);
                path.setAttribute("fill", color);
                path.setAttribute("stroke", "#fff");
                path.setAttribute("stroke-width", "1");
                
                // Add data attributes for tooltips
                path.setAttribute("data-category", categories[i]);
                path.setAttribute("data-value", value);
                path.setAttribute("data-percentage", percentage.toFixed(2) + "%");
                
                // Add slice to the chart
                g.appendChild(path);
                
                // Add to legend
                var legendItem = document.createElement('div');
                legendItem.style.display = 'flex';
                legendItem.style.alignItems = 'center';
                legendItem.style.marginBottom = '5px';
                legendItem.innerHTML = 
                    '<div style="width:12px;height:12px;background-color:' + color + ';margin-right:5px;"></div>' +
                    '<div style="font-size:12px;">' + categories[i] + ': ' + value + ' (' + percentage.toFixed(1) + '%)</div>';
                legend.appendChild(legendItem);
                
                // Simple tooltip
                path.addEventListener('mouseover', function(event) {
                    var tooltip = document.createElement('div');
                    tooltip.className = 'pie-tooltip';
                    tooltip.innerHTML = this.getAttribute('data-category') + ': ' + 
                                       this.getAttribute('data-value') + ' (' + 
                                       this.getAttribute('data-percentage') + ')';
                    tooltip.style.position = 'absolute';
                    tooltip.style.backgroundColor = 'rgba(0,0,0,0.8)';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '5px';
                    tooltip.style.borderRadius = '3px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.left = (event.pageX + 10) + 'px';
                    tooltip.style.top = (event.pageY + 10) + 'px';
                    document.body.appendChild(tooltip);
                    
                    this.tooltip = tooltip;
                    this.setAttribute('stroke-width', '2');
                });
                
                path.addEventListener('mousemove', function(event) {
                    if (this.tooltip) {
                        this.tooltip.style.left = (event.pageX + 10) + 'px';
                        this.tooltip.style.top = (event.pageY + 10) + 'px';
                    }
                });
                
                path.addEventListener('mouseout', function() {
                    if (this.tooltip) {
                        document.body.removeChild(this.tooltip);
                        this.tooltip = null;
                    }
                    this.setAttribute('stroke-width', '1');
                });
            }
            
            // Add legend to container
            container.appendChild(legend);
            
        } catch (error) {
            console.error("Error creating SVG chart:", error);
            
            // Fallback to simple text
            container.innerHTML = '<div style="padding:10px;"><p>Categories visualization not available.</p></div>';
        }
    });
</script>

<!-- Styles for Import Summary Modal -->
<style>
    /* Modal specific styles */
    #importSummaryModal, #removalSummaryModal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        overflow: auto;
        display: none;
    }
    
    #importSummaryModal .modal-dialog, #removalSummaryModal .modal-dialog {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 100px auto;
    }
    
    #importSummaryModal .modal-content, #removalSummaryModal .modal-content {
        background-color: #fff;
        border-radius: 0.4375rem;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    }
    
    #importSummaryModal .modal-header, #removalSummaryModal .modal-header {
        padding: 15px;
        border-top-left-radius: 0.4375rem;
        border-top-right-radius: 0.4375rem;
        color: white;
    }
    
    #importSummaryModal .modal-header {
        background-color: #2dce89;
    }
    
    #removalSummaryModal .modal-header {
        background-color: #f5365c;
    }
    
    #importSummaryModal .btn-close, #removalSummaryModal .btn-close {
        background-color: transparent;
        border: none;
        color: white;
        font-size: 1.5rem;
        opacity: 0.7;
        filter: brightness(0) invert(1);
    }
    
    #importSummaryModal .btn-close:hover, #removalSummaryModal .btn-close:hover {
        opacity: 1;
    }
    
    #importSummaryModal .modal-title, #removalSummaryModal .modal-title {
        font-weight: 600;
    }
    
    #importSummaryModal .modal-body, #removalSummaryModal .modal-body {
        padding: 20px;
    }
    
    #importSummaryModal .modal-footer, #removalSummaryModal .modal-footer {
        padding: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
    }
</style>

<!-- Import Summary Modal -->
<div class="modal" id="importSummaryModal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Completed Successfully</h5>
        <button type="button" class="btn-close" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($showImportModal && $importSummary): ?>
        <div class="card">
            <div class="card-body p-3">
                <h6 class="text-center mb-3">Import Summary</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>File:</strong></td>
                                <td><?= htmlspecialchars($importSummary['filename']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>New Satellites:</strong></td>
                                <td><span class="text-success"><?= $importSummary['new'] ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Updated Satellites:</strong></td>
                                <td><span class="text-primary"><?= $importSummary['updated'] ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Total Processed:</strong></td>
                                <td><strong><?= $importSummary['total'] ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>Categories:</strong></td>
                                <td><?= htmlspecialchars($importSummary['categories']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="modalOkBtn">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Duplicate Removal Summary Modal -->
<div class="modal" id="removalSummaryModal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Duplicates Removed Successfully</h5>
        <button type="button" class="btn-close" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($showRemovalModal && $removalSummary): ?>
        <div class="card">
            <div class="card-body p-3">
                <h6 class="text-center mb-3">Removal Summary</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>Unique Satellite Names:</strong></td>
                                <td><?= $removalSummary['uniqueNames'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Duplicates Removed:</strong></td>
                                <td><span class="text-danger"><?= $removalSummary['duplicatesRemoved'] ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Total Satellites:</strong></td>
                                <td><strong><?= $removalSummary['satellitesChecked'] - $removalSummary['duplicatesRemoved'] ?></strong> (after removal)</td>
                            </tr>
                            <tr>
                                <td><strong>Completed:</strong></td>
                                <td><?= $removalSummary['timestamp'] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="removalOkBtn">OK</button>
      </div>
    </div>
  </div>
</div>

<?php if ($showImportModal): ?>
<!-- Script for import summary modal -->
<script>
function showImportModal() {
    // Get the modal element
    var modalElement = document.getElementById('importSummaryModal');
    
    // Position the dialog in the center of the viewport
    var dialogElement = modalElement.querySelector('.modal-dialog');
    if (dialogElement) {
        // Reset any previous positioning
        dialogElement.style.margin = '100px auto';
    }
    
    // Show the modal
    modalElement.style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Add backdrop that covers the entire page
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    document.body.appendChild(backdrop);
    
    // Prevent body from scrolling while modal is open
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '15px'; // Compensate for scrollbar
    
    // Add event listener to close button
    var closeButton = modalElement.querySelector('.btn-close');
    if (closeButton) {
        closeButton.onclick = function() {
            closeModal();
        };
    }
    
    // Add event listener to OK button
    var okButton = document.getElementById('modalOkBtn');
    if (okButton) {
        okButton.onclick = function() {
            closeModal();
        };
    }
}

function closeModal() {
    var modal = document.getElementById('importSummaryModal');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    var backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.parentNode.removeChild(backdrop);
    }
    
    // Restore body scrolling
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Show the modal after page loads
    setTimeout(showImportModal, 300);
});
</script>
<?php endif; ?>

<?php if ($showRemovalModal): ?>
<!-- Script for removal summary modal -->
<script>
function showRemovalModal() {
    // Get the modal element
    var modalElement = document.getElementById('removalSummaryModal');
    
    // Position the dialog in the center of the viewport
    var dialogElement = modalElement.querySelector('.modal-dialog');
    if (dialogElement) {
        // Reset any previous positioning
        dialogElement.style.margin = '100px auto';
    }
    
    // Show the modal
    modalElement.style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Add backdrop that covers the entire page
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    document.body.appendChild(backdrop);
    
    // Prevent body from scrolling while modal is open
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '15px'; // Compensate for scrollbar
    
    // Add event listener to close button
    var closeButton = modalElement.querySelector('.btn-close');
    if (closeButton) {
        closeButton.onclick = function() {
            closeRemovalModal();
        };
    }
    
    // Add event listener to OK button
    var okButton = document.getElementById('removalOkBtn');
    if (okButton) {
        okButton.onclick = function() {
            closeRemovalModal();
        };
    }
}

function closeRemovalModal() {
    var modal = document.getElementById('removalSummaryModal');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    var backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.parentNode.removeChild(backdrop);
    }
    
    // Restore body scrolling
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Show the modal after page loads
    setTimeout(showRemovalModal, 300);
    });
</script> 
<?php endif; ?> 