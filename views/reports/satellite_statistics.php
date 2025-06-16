<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0">Export Chart Data</h6>
                <div>
                    <a href="<?= \app\core\Application::url('/exportSatelliteStatsJson') ?>" class="btn btn-sm btn-outline-primary">Export JSON</a>
                    <a href="<?= \app\core\Application::url('/exportSatelliteStatsXml') ?>" class="btn btn-sm btn-outline-secondary">Export XML</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Satellite Count by Category</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="chart">
                    <canvas id="chart-bars" class="chart-canvas" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Category Percentage Distribution</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="chart">
                    <canvas id="chart-pie" class="chart-canvas" height="500"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Satellite Speed vs Launch Year</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="chart">
                    <canvas id="chart-scatter" class="chart-canvas" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ensure Chart.js is loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
    // Debug Chart.js loading
    console.log("Chart.js loaded:", typeof Chart !== 'undefined');
    
    // If Chart.js isn't loaded by the time this script runs, try to load it again
    if (typeof Chart === 'undefined') {
        console.log("Chart.js not detected, attempting to load it directly");
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        script.onload = function() {
            console.log("Chart.js loaded successfully, initializing charts");
            initCharts();
        };
        document.head.appendChild(script);
    } else {
        console.log("Chart.js already loaded, initializing charts directly");
        initCharts();
    }
    
    function initCharts() {
        // Define vibrant colors for categories
        var categories = <?= json_encode($model['categories']) ?>;
        var categoryData = <?= json_encode(array_values($model['categoryData'])) ?>;
        
        // Generate vibrant colors for better visibility
        function generateVibrantColors(count) {
            var colors = [
                'rgba(255, 99, 132, 0.9)',   // Bright Pink
                'rgba(54, 162, 235, 0.9)',   // Bright Blue
                'rgba(255, 206, 86, 0.9)',   // Bright Yellow
                'rgba(75, 192, 192, 0.9)',   // Bright Teal
                'rgba(153, 102, 255, 0.9)',  // Bright Purple
                'rgba(255, 159, 64, 0.9)',   // Bright Orange
                'rgba(0, 204, 102, 0.9)',    // Bright Green
                'rgba(255, 0, 0, 0.9)',      // Bright Red
                'rgba(0, 0, 255, 0.9)',      // Bright Blue
                'rgba(128, 0, 128, 0.9)'     // Bright Purple
            ];
            
            // If we need more colors than in our predefined list
            if (count > colors.length) {
                for (var i = colors.length; i < count; i++) {
                    // Generate additional vibrant colors
                    var h = Math.floor(Math.random() * 360);
                    colors.push(`hsla(${h}, 90%, 60%, 0.9)`);
                }
            }
            
            return colors.slice(0, count);
        }
        
        var backgroundColors = generateVibrantColors(categories.length);
        
        try {
            // Bar chart with vibrant styling
            var ctx1 = document.getElementById("chart-bars");
            if (!ctx1) {
                console.error("Cannot find chart-bars canvas element");
                return;
            }
            
            console.log("Creating bar chart");
            var barChart = new Chart(ctx1.getContext("2d"), {
                type: "bar",
                data: {
                    labels: categories,
                    datasets: [{
                        label: "Satellites",
                        tension: 0.4,
                        borderWidth: 0,
                        borderRadius: 6,
                        borderSkipped: false,
                        backgroundColor: backgroundColors,
                        data: categoryData,
                        maxBarThickness: 50
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Numerical Distribution per Category',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            color: '#333333',
                            padding: {
                                top: 10,
                                bottom: 20
                            },
                            align: 'center'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw;
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        y: {
                            grid: {
                                drawBorder: false,
                                display: true,
                                drawOnChartArea: true,
                                drawTicks: false,
                                borderDash: [5, 5]
                            },
                            ticks: {
                                suggestedMin: 0,
                                suggestedMax: Math.max(...categoryData) * 1.1,
                                beginAtZero: true,
                                padding: 10,
                                font: {
                                    size: 14,
                                    weight: 300,
                                    family: "Roboto",
                                    style: 'normal',
                                    lineHeight: 2
                                },
                                color: "#333"
                            },
                        },
                        x: {
                            grid: {
                                drawBorder: false,
                                display: true,
                                drawOnChartArea: true,
                                drawTicks: false,
                                borderDash: [5, 5]
                            },
                            ticks: {
                                display: true,
                                padding: 10,
                                font: {
                                    size: 14,
                                    weight: 300,
                                    family: "Roboto",
                                    style: 'normal',
                                    lineHeight: 2
                                },
                                color: "#333"
                            }
                        },
                    }
                },
                plugins: [{
                    afterDraw: function(chart) {
                        var ctx = chart.ctx;
                        chart.data.datasets.forEach(function(dataset, datasetIndex) {
                            var meta = chart.getDatasetMeta(datasetIndex);
                            if (!meta.hidden) {
                                meta.data.forEach(function(element, index) {
                                    // Draw the data value on top of the bar
                                    var dataValue = dataset.data[index];
                                    var position = element.tooltipPosition();
                                    
                                    ctx.fillStyle = '#333';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.font = 'bold 14px Roboto';
                                    ctx.fillText(dataValue, position.x, position.y - 5);
                                });
                            }
                        });
                    }
                }]
            });
            console.log("Bar chart created successfully");
            
            // Pie chart with enhanced styling
            var ctx2 = document.getElementById("chart-pie");
            if (!ctx2) {
                console.error("Cannot find chart-pie canvas element");
                return;
            }
            
            console.log("Creating pie chart");
            var pieChart = new Chart(ctx2.getContext("2d"), {
                type: "doughnut", // Changed to doughnut for more visual distinction
                data: {
                    labels: categories,
                    datasets: [{
                        label: "Percentage",
                        weight: 9,
                        cutout: '40%',
                        tension: 0.9,
                        pointRadius: 2,
                        borderWidth: 1,
                        borderColor: '#000000',
                        backgroundColor: backgroundColors,
                        data: categoryData,
                        fill: false,
                        hoverOffset: 15
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20,
                            right: 120, // More padding on the right to balance the legend
                            bottom: 10,
                            left: 120  // Added left padding to balance the title
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                font: {
                                    size: 13
                                },
                                padding: 15,
                                boxWidth: 15,
                                boxHeight: 15,
                                generateLabels: function(chart) {
                                    // Get the default legend items
                                    var original = Chart.overrides.doughnut.plugins.legend.labels.generateLabels(chart);
                                    
                                    // Calculate the total
                                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    
                                    // Add percentage to each label
                                    original.forEach((label, i) => {
                                        const value = chart.data.datasets[0].data[i];
                                        const percentage = ((value / total) * 100).toFixed(2);
                                        label.text = `${label.text}: ${percentage}%`;
                                    });
                                    
                                    return original;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Percentage Distribution per Category',
                            position: 'top',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            color: '#333333',
                            padding: {
                                top: 10,
                                bottom: 20
                            },
                            align: 'center'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = ((value / total) * 100).toFixed(2) + '%';
                                    return `${context.label}: ${value} (${percentage})`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 14
                            },
                            padding: 15,
                            cornerRadius: 6
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                },
            });
            console.log("Pie chart created successfully");
            
            // Create the scatter plot for satellite speed vs launch year
            var ctx3 = document.getElementById("chart-scatter");
            if (!ctx3) {
                console.error("Cannot find chart-scatter canvas element");
                return;
            }
            
            console.log("Creating scatter plot");
            var scatterData = <?= json_encode($model['scatterData'] ?? []) ?>;
            
            var scatterChart = new Chart(ctx3.getContext("2d"), {
                type: "scatter",
                data: {
                    datasets: [{
                        label: "Satellite Speed (revolutions per day)",
                        data: scatterData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20,
                            right: 30,
                            bottom: 10,
                            left: 30
                        }
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            position: 'bottom',
                            title: {
                                display: true,
                                text: 'Launch Year',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return Math.floor(value); // Only display full years
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Revolutions Per Day',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Satellite Speed vs Launch Year',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            color: '#333333',
                            padding: {
                                top: 10,
                                bottom: 20
                            },
                            align: 'center'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const point = context.raw;
                                    return `${point.name}: ${point.y.toFixed(2)} rev/day (${point.x})`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
            console.log("Scatter plot created successfully");
        } catch (error) {
            console.error("Error creating charts:", error);
        }
    }
</script>

<!-- Force bypass cache -->
<script>
    // Add a timestamp to force reload of resources
    document.querySelectorAll('script').forEach(script => {
        if (script.src && !script.src.includes('?')) {
            script.src = script.src + '?v=' + new Date().getTime();
        }
    });
    
    // Also try a full page reload if needed
    if (!window.chartRefreshed) {
        window.chartRefreshed = true;
        // Uncomment if needed: location.reload(true);
    }
</script> 