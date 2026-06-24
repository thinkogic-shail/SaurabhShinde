<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();
$totalRequests = 0;
$closedRequests = 0;
$openRequests = 0;
$inProgressRequests = 0;
$trendLabels = [];
$raisedTrendData = [];
$inProgressTrendData = [];
$closedTrendData = [];

try {
    $pdo = app_pdo();
    $totalRequests = (int) $pdo->query('SELECT COUNT(*) FROM CitizenRequest')->fetchColumn();
    $closedRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) IN ('completed', 'declined')"
    );
    $closedRequests = (int) $closedRequestsStmt->fetchColumn();
    $openRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) = 'raised'"
    );
    $openRequests = (int) $openRequestsStmt->fetchColumn();
    $inProgressRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) = 'in progress'"
    );
    $inProgressRequests = (int) $inProgressRequestsStmt->fetchColumn();

    $trendRows = $pdo->query(
        "SELECT DATE_FORMAT(cr.RaisedDate, '%Y-%m') AS month_key,
                SUM(CASE WHEN TRIM(LOWER(rsm.StatusName)) = 'raised' THEN 1 ELSE 0 END) AS raised_count,
                SUM(CASE WHEN TRIM(LOWER(rsm.StatusName)) = 'in progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN TRIM(LOWER(rsm.StatusName)) IN ('completed', 'declined') THEN 1 ELSE 0 END) AS closed_count
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE cr.RaisedDate IS NOT NULL
         GROUP BY DATE_FORMAT(cr.RaisedDate, '%Y-%m')
         ORDER BY month_key ASC"
    )->fetchAll();

    $trendMap = [];
    foreach ($trendRows as $trendRow) {
        $trendMap[(string) $trendRow['month_key']] = [
            'raised' => (int) $trendRow['raised_count'],
            'in_progress' => (int) $trendRow['in_progress_count'],
            'closed' => (int) $trendRow['closed_count'],
        ];
    }

    $currentMonth = new DateTimeImmutable('first day of this month');
    for ($offset = 11; $offset >= 0; $offset--) {
        $monthDate = $currentMonth->modify("-{$offset} months");
        $monthKey = $monthDate->format('Y-m');
        $trendLabels[] = $monthDate->format('M Y');
        $raisedTrendData[] = $trendMap[$monthKey]['raised'] ?? 0;
        $inProgressTrendData[] = $trendMap[$monthKey]['in_progress'] ?? 0;
        $closedTrendData[] = $trendMap[$monthKey]['closed'] ?? 0;
    }
} catch (Throwable $e) {
    $totalRequests = 0;
    $closedRequests = 0;
    $openRequests = 0;
    $inProgressRequests = 0;
    $currentMonth = new DateTimeImmutable('first day of this month');
    for ($offset = 11; $offset >= 0; $offset--) {
        $monthDate = $currentMonth->modify("-{$offset} months");
        $trendLabels[] = $monthDate->format('M Y');
        $raisedTrendData[] = 0;
        $inProgressTrendData[] = 0;
        $closedTrendData[] = 0;
    }
}

render_admin_header('Dashboard', [], 'dashboard');
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .request-trend-card .card-header {
        background-color: transparent;
        border-bottom: 1px solid #eef2f7;
        padding-bottom: 0.75rem;
    }
</style>

<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #2b5ab4; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($totalRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Total Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-file-list-3-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #16A34A; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($closedRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Closed Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-checkbox-circle-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #9242E3; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($openRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Open Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-folder-open-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #e0a800; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($inProgressRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">In progress Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-loader-4-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card request-trend-card">
            <div class="card-body">
                <div class="card-header px-0 pt-0 mb-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <h4 class="card-title mb-1">Request Trend</h4>
                            <p class="text-muted mb-0">Monthly status-wise trend of citizen requests.</p>
                        </div>
                    </div>
                </div>
                <div id="request-trend-chart" class="apex-charts" dir="ltr"></div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartElement = document.querySelector('#request-trend-chart');

        if (!chartElement || typeof ApexCharts === 'undefined') {
            return;
        }

        const requestTrendOptions = {
            series: [
                {
                    name: 'Raised (Open)',
                    data: <?php echo json_encode($raisedTrendData, JSON_UNESCAPED_SLASHES); ?>
                },
                {
                    name: 'In Progress',
                    data: <?php echo json_encode($inProgressTrendData, JSON_UNESCAPED_SLASHES); ?>
                },
                {
                    name: 'Closed',
                    data: <?php echo json_encode($closedTrendData, JSON_UNESCAPED_SLASHES); ?>
                }
            ],
            chart: {
                height: 320,
                type: 'area',
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.32,
                    opacityTo: 0.08,
                    stops: [0, 90, 100]
                }
            },
            colors: ['#9242E3', '#F6AF2E', '#16A34A'],
            markers: {
                size: 0,
                hover: {
                    size: 5
                }
            },
            xaxis: {
                categories: <?php echo json_encode($trendLabels, JSON_UNESCAPED_SLASHES); ?>,
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: function (value) {
                        return Math.round(value);
                    }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 3
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            tooltip: {
                shared: true,
                intersect: false
            },
            responsive: [
                {
                    breakpoint: 768,
                    options: {
                        chart: {
                            height: 280
                        },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center'
                        }
                    }
                }
            ],
            noData: {
                text: 'No request trend data available'
            }
        };

        const requestTrendChart = new ApexCharts(chartElement, requestTrendOptions);
        requestTrendChart.render();
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/apexcharts/apexcharts.min.js'),
]);
