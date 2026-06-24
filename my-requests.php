<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();
$pdo = app_pdo();
$requests = $pdo->query(
    'SELECT cr.CitizenRequestId, cr.RequestNo, cu.Name, cu.MobileNo, rtm.RequestTypeName,
            w.WardName, a.AreaName, rsm.StatusName, cr.RaisedDate, cr.IsActive
     FROM CitizenRequest cr
     LEFT JOIN CitizenUser cu ON cu.CitizenUserId = cr.CitizenUserId
     LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
     LEFT JOIN Ward w ON w.WardId = cr.WardId
     LEFT JOIN Area a ON a.AreaId = cr.AreaId
     LEFT JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
     ORDER BY cr.CitizenRequestId DESC'
)->fetchAll();

$requestTypes = array_filter(array_unique(array_column($requests, 'RequestTypeName')));
sort($requestTypes);
$wards = array_filter(array_unique(array_column($requests, 'WardName')));
sort($wards);
$areas = array_filter(array_unique(array_column($requests, 'AreaName')));
sort($areas);
$statusOptions = $pdo->query('SELECT StatusName FROM RequestStatusMaster WHERE IsActive = 1')->fetchAll(PDO::FETCH_COLUMN);
$statuses = array_unique(array_merge($statusOptions, array_map(function($s) {
    return (string)$s !== '' ? (string)$s : 'Not Set';
}, array_column($requests, 'StatusName'))));
sort($statuses);

render_admin_header('My Requests', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
    app_asset('assets/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css'),
], 'my-requests', false);
?>
<style>
    #my-requests-table td, #my-requests-table th {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .dataTables_paginate .page-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
    #filter-date-range .input-group-text {
        background-color: #fff;
        border-left: 0;
        border-right: 0;
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }
    #filter-date-range #filter-date-from {
        border-right: 0;
    }
    #filter-date-range #filter-date-to {
        border-left: 0;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    var alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        var alert = new bootstrap.Alert(alertNode);
                        alert.close();
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="page-title-box">
                        <h4 class="mb-1">My Requests</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">My Requests</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row mb-3 align-items-end">
                    <div class="col">
                        <label class="form-label" for="filter-date-range">Date Range</label>
                        <div class="input-daterange input-group" id="filter-date-range">
                            <input type="text" id="filter-date-from" class="form-control" placeholder="From Date" readonly>
                            <span class="input-group-text">-</span>
                            <input type="text" id="filter-date-to" class="form-control" placeholder="To Date" readonly>
                        </div>
                    </div>
                    <div class="col">
                        <label class="form-label" for="filter-request-type">Request Type</label>
                        <select id="filter-request-type" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($requestTypes as $rt): ?>
                                <option value="<?php echo htmlspecialchars($rt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($rt, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label" for="filter-ward">Ward</label>
                        <select id="filter-ward" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($wards as $w): ?>
                                <option value="<?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label" for="filter-area">Area</label>
                        <select id="filter-area" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?php echo htmlspecialchars($a, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($a, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label" for="filter-status">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                       <!-- <label class="form-label" for="custom-search"></label>-->
                        <div class="search-box">
                            <div class="position-relative">
                                <input type="search" id="custom-search" class="form-control rounded" placeholder="Search...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="my-requests-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Request No</th>
                                <th>Name</th>
                                <th>Mobile No</th>
                                <th>Request Type</th>
                                <th>Ward</th>
                                <th>Area</th>
                                <th>Status</th>
                                <th>Raised Date</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php
                                $fullName = (string) ($request['Name'] ?? '');
                                $displayName = mb_strlen($fullName) > 20 ? mb_substr($fullName, 0, 20) . '...' : $fullName;
                                $fullRequestType = (string) ($request['RequestTypeName'] ?? '');
                                $displayRequestType = mb_strlen($fullRequestType) > 20 ? mb_substr($fullRequestType, 0, 20) . '...' : $fullRequestType;
                                $fullWard = (string) ($request['WardName'] ?? '');
                                $displayWard = mb_strlen($fullWard) > 20 ? mb_substr($fullWard, 0, 20) . '...' : $fullWard;
                                $fullArea = (string) ($request['AreaName'] ?? '');
                                $displayArea = mb_strlen($fullArea) > 20 ? mb_substr($fullArea, 0, 20) . '...' : $fullArea;
                                $statusName = (string) ($request['StatusName'] ?? '');
                                $normalizedStatus = strtolower(trim($statusName));
                                if ($normalizedStatus === '' || $normalizedStatus === 'not set') {
                                    $badgeClass = 'bg-secondary';
                                    $badgeStyle = '';
                                } elseif ($normalizedStatus === 'raised') {
                                    $badgeClass = '';
                                    $badgeStyle = 'background-color: #F8D7DA; color: #842029;';
                                } elseif (in_array($normalizedStatus, ['declined', 'rejected', 'decline'], true)) {
                                    $badgeClass = 'text-white';
                                    $badgeStyle = 'background-color: #E24949;';
                                } elseif (in_array($normalizedStatus, ['pending', 'in progress', 'processing'], true)) {
                                    $badgeClass = 'bg-warning';
                                    $badgeStyle = '';
                                } else {
                                    $badgeClass = 'bg-success';
                                    $badgeStyle = '';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['RequestNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($request['MobileNo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullRequestType, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayRequestType, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullWard, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayWard, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullArea, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayArea, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $badgeClass; ?>"<?php echo $badgeStyle !== '' ? ' style="' . htmlspecialchars($badgeStyle, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                                            <?php echo htmlspecialchars($statusName !== '' ? $statusName : 'Not Set', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($request['RaisedDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="my-request-form.php?id=<?php echo (int) $request['CitizenRequestId']; ?>" class="btn btn-sm" style="background-color: #002253; border-color: #002253; color: white;">
                                            <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableElement = document.getElementById('my-requests-table');
        const dateFromInput = document.getElementById('filter-date-from');
        const dateToInput = document.getElementById('filter-date-to');
        const myRequestsTable = $('#my-requests-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[7, 'desc']]
        });

        $.fn.dataTable.ext.search.push(function(settings, data) {
            if (settings.nTable !== tableElement) {
                return true;
            }

            const fromValue = dateFromInput.value.trim();
            const toValue = dateToInput.value.trim();
            const rowDate = new Date((data[7] || '').replace(' ', 'T'));

            if (Number.isNaN(rowDate.getTime())) {
                return true;
            }

            if (fromValue !== '') {
                const fromDate = new Date(fromValue + 'T00:00:00');

                if (!Number.isNaN(fromDate.getTime()) && rowDate < fromDate) {
                    return false;
                }
            }

            if (toValue !== '') {
                const toDate = new Date(toValue + 'T23:59:59');

                if (!Number.isNaN(toDate.getTime()) && rowDate > toDate) {
                    return false;
                }
            }

            return true;
        });

        $('#filter-date-range').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        }).on('changeDate clearDate', function() {
            myRequestsTable.draw();
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            myRequestsTable.search(this.value).draw();
        });

        $('#filter-request-type').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            myRequestsTable.column(3).search(val ? val : '', true, false).draw();
        });
        $('#filter-ward').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            myRequestsTable.column(4).search(val ? val : '', true, false).draw();
        });
        $('#filter-area').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            myRequestsTable.column(5).search(val ? val : '', true, false).draw();
        });
        $('#filter-status').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            myRequestsTable.column(6).search(val ? val : '', true, false).draw();
        });

        $('#filter-date-from, #filter-date-to').on('keydown paste', function(event) {
            event.preventDefault();
        });
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js'),
    app_asset('assets/libs/datatables.net/js/jquery.dataTables.min.js'),
    app_asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js'),
    app_asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js'),
    app_asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js'),
]);
