<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/performance.php';
require_once '../includes/cache.php';

// Check if user is admin
checkAdminAccess();

$page_title = "Performance Monitor";

// Get performance metrics
$metrics = PerformanceDashboard::getMetrics();

// Handle cache operations
if ($_POST['action'] ?? '') {
    switch ($_POST['action']) {
        case 'clear_cache':
            $deleted = cache_flush();
            $success_message = "Cache cleared successfully. Deleted {$deleted} entries.";
            break;
            
        case 'cleanup_cache':
            $deleted = cleanupCache();
            $success_message = "Cache cleanup completed. Deleted {$deleted} expired entries.";
            break;
            
        case 'warmup_cache':
            warmupCache();
            $success_message = "Cache warmed up successfully.";
            break;
            
        case 'analyze_indexes':
            $table = $_POST['table'] ?? '';
            if ($table) {
                $index_analysis = analyzeTableIndexes($table);
                if ($index_analysis) {
                    $_SESSION['index_analysis'] = $index_analysis;
                    $success_message = "Index analysis completed for table: {$table}";
                }
            }
            break;
    }
    
    // Refresh metrics after action
    $metrics = PerformanceDashboard::getMetrics();
}

// Get slow query log (if available)
$slow_queries = [];
try {
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'slow_query_log'");
    $slow_log_enabled = $stmt->fetch();
    
    if ($slow_log_enabled && $slow_log_enabled['Value'] === 'ON') {
        // Get recent slow queries (simplified - would need actual log file parsing)
        $slow_queries = [];
    }
} catch (Exception $e) {
    // Ignore if slow query log is not available
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> Performance Monitor</h1>
                <p class="lead">Monitor system performance, cache statistics, and database metrics</p>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- System Overview -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-memory fa-2x text-primary mb-2"></i>
                            <h5>Memory Usage</h5>
                            <h3 class="text-primary"><?= $metrics['memory']['current_usage_mb'] ?>MB</h3>
                            <small class="text-muted">Peak: <?= $metrics['memory']['peak_usage_mb'] ?>MB</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-2x text-success mb-2"></i>
                            <h5>Database Queries</h5>
                            <h3 class="text-success"><?= number_format($metrics['database']['total_queries'] ?? 0) ?></h3>
                            <small class="text-muted">QPS: <?= $metrics['database']['queries_per_second'] ?? 0 ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-rocket fa-2x text-info mb-2"></i>
                            <h5>Cache Files</h5>
                            <h3 class="text-info"><?= number_format($metrics['cache']['total_files']) ?></h3>
                            <small class="text-muted">Size: <?= $metrics['cache']['total_size_mb'] ?>MB</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-hdd fa-2x text-warning mb-2"></i>
                            <h5>Disk Space</h5>
                            <h3 class="text-warning"><?= $metrics['system']['disk_free_space_gb'] ?>GB</h3>
                            <small class="text-muted">Free space available</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Charts -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Memory Usage</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="memoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie"></i> Cache Statistics</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="cacheChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cache Management -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cogs"></i> Cache Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Cache Statistics</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Total Files:</strong> <?= number_format($metrics['cache']['total_files']) ?></li>
                                        <li><strong>Total Size:</strong> <?= $metrics['cache']['total_size_mb'] ?>MB</li>
                                        <li><strong>Expired Files:</strong> <?= number_format($metrics['cache']['expired_files']) ?></li>
                                        <li><strong>Hit Rate:</strong> <?= $metrics['cache']['hit_rate'] ?></li>
                                        <li><strong>Total Hits:</strong> <?= number_format($metrics['cache']['total_hits']) ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Cache Actions</h6>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="cleanup_cache">
                                        <button type="submit" class="btn btn-outline-warning btn-sm me-2 mb-2">
                                            <i class="fas fa-broom"></i> Cleanup Expired
                                        </button>
                                    </form>
                                    
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="warmup_cache">
                                        <button type="submit" class="btn btn-outline-success btn-sm me-2 mb-2">
                                            <i class="fas fa-fire"></i> Warm Up Cache
                                        </button>
                                    </form>
                                    
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button type="submit" class="btn btn-outline-danger btn-sm mb-2" 
                                                onclick="return confirm('Are you sure you want to clear all cache?')">
                                            <i class="fas fa-trash"></i> Clear All Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-server"></i> System Info</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><strong>PHP Version:</strong> <?= $metrics['system']['php_version'] ?></li>
                                <li><strong>Server:</strong> <?= htmlspecialchars($metrics['system']['server_software']) ?></li>
                                <li><strong>Memory Limit:</strong> <?= $metrics['memory']['limit'] ?></li>
                                <li><strong>Load Average:</strong> 
                                    <?php if (is_array($metrics['system']['load_average'])): ?>
                                        <?= implode(', ', array_map(function($load) { return number_format($load, 2); }, $metrics['system']['load_average'])) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Database Analysis -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-database"></i> Database Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Query Statistics</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Total Queries:</strong> <?= number_format($metrics['database']['total_queries'] ?? 0) ?></li>
                                        <li><strong>Slow Queries:</strong> <?= number_format($metrics['database']['slow_queries'] ?? 0) ?></li>
                                        <li><strong>Queries per Second:</strong> <?= $metrics['database']['queries_per_second'] ?? 0 ?></li>
                                        <li><strong>Uptime:</strong> <?= gmdate("H:i:s", $metrics['database']['uptime_seconds'] ?? 0) ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Index Analysis</h6>
                                    <form method="post" class="mb-3">
                                        <input type="hidden" name="action" value="analyze_indexes">
                                        <div class="input-group">
                                            <select name="table" class="form-select" required>
                                                <option value="">Select table to analyze</option>
                                                <option value="students">students</option>
                                                <option value="teachers">teachers</option>
                                                <option value="classes">classes</option>
                                                <option value="attendance">attendance</option>
                                                <option value="fee_payments">fee_payments</option>
                                                <option value="subjects">subjects</option>
                                                <option value="exams">exams</option>
                                                <option value="marks">marks</option>
                                            </select>
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i> Analyze
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <?php if (isset($_SESSION['index_analysis'])): ?>
                                    <div class="mt-3">
                                        <h6>Analysis Results:</h6>
                                        <?php $analysis = $_SESSION['index_analysis']; ?>
                                        <ul class="list-unstyled small">
                                            <li><strong>Table:</strong> <?= htmlspecialchars($analysis['table_name']) ?></li>
                                            <li><strong>Rows:</strong> <?= number_format($analysis['row_count']) ?></li>
                                            <li><strong>Data Size:</strong> <?= round($analysis['data_length'] / 1024 / 1024, 2) ?>MB</li>
                                            <li><strong>Index Size:</strong> <?= round($analysis['index_length'] / 1024 / 1024, 2) ?>MB</li>
                                            <li><strong>Indexes:</strong> <?= count($analysis['indexes']) ?></li>
                                        </ul>
                                        <?php unset($_SESSION['index_analysis']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Recommendations -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-lightbulb"></i> Performance Recommendations</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Automatic Recommendations</h6>
                                    <div class="recommendations">
                                        <?php
                                        $recommendations = [];
                                        
                                        // Memory recommendations
                                        if ($metrics['memory']['current_usage_mb'] > 128) {
                                            $recommendations[] = [
                                                'type' => 'warning',
                                                'icon' => 'fas fa-memory',
                                                'title' => 'High Memory Usage',
                                                'description' => 'Consider optimizing queries or increasing pagination limits.'
                                            ];
                                        }
                                        
                                        // Cache recommendations
                                        if ($metrics['cache']['expired_files'] > 100) {
                                            $recommendations[] = [
                                                'type' => 'info',
                                                'icon' => 'fas fa-clock',
                                                'title' => 'Cache Cleanup Needed',
                                                'description' => 'Many expired cache files detected. Run cleanup to free space.'
                                            ];
                                        }
                                        
                                        if ($metrics['cache']['hit_rate'] < 0.5) {
                                            $recommendations[] = [
                                                'type' => 'warning',
                                                'icon' => 'fas fa-target',
                                                'title' => 'Low Cache Hit Rate',
                                                'description' => 'Consider increasing cache TTL for frequently accessed data.'
                                            ];
                                        }
                                        
                                        // Database recommendations
                                        if (($metrics['database']['slow_queries'] ?? 0) > 10) {
                                            $recommendations[] = [
                                                'type' => 'danger',
                                                'icon' => 'fas fa-database',
                                                'title' => 'Slow Queries Detected',
                                                'description' => 'Review and optimize slow queries. Consider adding indexes.'
                                            ];
                                        }
                                        
                                        // Disk space recommendations
                                        if ($metrics['system']['disk_free_space_gb'] < 1) {
                                            $recommendations[] = [
                                                'type' => 'danger',
                                                'icon' => 'fas fa-hdd',
                                                'title' => 'Low Disk Space',
                                                'description' => 'Free up disk space to prevent system issues.'
                                            ];
                                        }
                                        
                                        if (empty($recommendations)) {
                                            echo '<div class="alert alert-success"><i class="fas fa-check"></i> System performance looks good!</div>';
                                        } else {
                                            foreach ($recommendations as $rec) {
                                                echo '<div class="alert alert-' . $rec['type'] . ' alert-sm">';
                                                echo '<i class="' . $rec['icon'] . '"></i> ';
                                                echo '<strong>' . htmlspecialchars($rec['title']) . '</strong><br>';
                                                echo '<small>' . htmlspecialchars($rec['description']) . '</small>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Manual Optimizations</h6>
                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <strong>Database Indexes</strong>
                                            <p class="mb-1 small">Analyze table indexes to identify missing or unused indexes.</p>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Query Optimization</strong>
                                            <p class="mb-1 small">Review slow queries and optimize with better WHERE clauses and JOINs.</p>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Image Compression</strong>
                                            <p class="mb-1 small">Compress uploaded images to reduce storage and bandwidth usage.</p>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Asset Minification</strong>
                                            <p class="mb-1 small">Minify CSS and JavaScript files to reduce page load times.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for performance graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Memory Usage Chart
const memoryCtx = document.getElementById('memoryChart').getContext('2d');
const memoryChart = new Chart(memoryCtx, {
    type: 'doughnut',
    data: {
        labels: ['Used Memory', 'Available Memory'],
        datasets: [{
            data: [
                <?= $metrics['memory']['current_usage_mb'] ?>,
                <?= max(0, 256 - $metrics['memory']['current_usage_mb']) ?> // Assuming 256MB limit
            ],
            backgroundColor: ['#dc3545', '#28a745'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Memory Usage (MB)'
            }
        }
    }
});

// Cache Statistics Chart
const cacheCtx = document.getElementById('cacheChart').getContext('2d');
const cacheChart = new Chart(cacheCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active Cache', 'Expired Cache'],
        datasets: [{
            data: [
                <?= $metrics['cache']['total_files'] - $metrics['cache']['expired_files'] ?>,
                <?= $metrics['cache']['expired_files'] ?>
            ],
            backgroundColor: ['#007bff', '#ffc107'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Cache File Status'
            }
        }
    }
});

// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);

// Real-time memory usage (simplified)
function updateMemoryUsage() {
    fetch('performance_api.php?action=memory_usage')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                memoryChart.data.datasets[0].data = [
                    data.current_usage_mb,
                    Math.max(0, 256 - data.current_usage_mb)
                ];
                memoryChart.update();
            }
        })
        .catch(error => console.error('Error updating memory usage:', error));
}

// Update memory usage every 5 seconds
setInterval(updateMemoryUsage, 5000);
</script>

<style>
.recommendations .alert {
    margin-bottom: 0.5rem;
}

.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: none;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin-bottom: 0.5rem;
}

.card-body.text-center h3 {
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.list-group-item {
    border: none;
    border-left: 3px solid #dee2e6;
    margin-bottom: 0.5rem;
}

.list-group-item:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}
</style>

<?php include '../includes/footer.php'; ?>
