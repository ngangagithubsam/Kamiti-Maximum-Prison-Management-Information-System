<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

$body = jsonBody();
$action = trim((string)($body['action'] ?? ($_GET['action'] ?? 'dashboard')));
$pdo = getDB();

function logAnalyticsError(Throwable $e, string $context): void {
    error_log('[reports analytics] ' . $context . ': ' . $e->getMessage());
}

function ensureReportsTable(PDO $pdo): void {
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reports' AND TABLE_SCHEMA=DATABASE()")
            ->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $cols, true)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN status VARCHAR(20) DEFAULT 'Pending'");
        }
        if (!in_array('department', $cols, true)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN department VARCHAR(100) DEFAULT ''");
        }
        if (!in_array('block', $cols, true)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN block VARCHAR(100) DEFAULT ''");
        }
        if (!in_array('processing_time_hours', $cols, true)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN processing_time_hours DECIMAL(10,2) DEFAULT 0");
        }
    } catch (Throwable $e) {
        logAnalyticsError($e, 'schema migration');
    }
}

function normalizeFilterValue(mixed $value): string {
    if (is_array($value)) {
        return trim((string)($value[0] ?? ''));
    }
    return trim((string)$value);
}

function normalizeDateValue(mixed $value): string {
    $raw = normalizeFilterValue($value);
    if ($raw === '') {
        return '';
    }
    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return '';
    }
    return date('Y-m-d', $timestamp);
}

function normalizeEnumValue(mixed $value, array $allowed): string {
    $value = normalizeFilterValue($value);
    if ($value === '') {
        return '';
    }
    foreach ($allowed as $allowedValue) {
        if (strcasecmp($value, $allowedValue) === 0) {
            return $allowedValue;
        }
    }
    return '';
}

function buildReportFilters(array $input): array {
    $filters = $input['filters'] ?? $input['filter'] ?? [];
    if (!is_array($filters)) {
        $filters = [];
    }

    $allowedCategories = ['Incident', 'Medical', 'Rehabilitation', 'Visitor', 'Security', 'Court', 'General', 'Release'];
    $allowedStatuses = ['Draft', 'Pending', 'Approved', 'Rejected', 'Archived'];
    $allowedBlocks = ['A', 'B', 'C', 'D', 'Maximum Security', 'Isolation'];

    $dateFrom = normalizeDateValue($filters['dateFrom'] ?? $filters['date_from'] ?? ($input['dateFrom'] ?? $input['date_from'] ?? ''));
    $dateTo = normalizeDateValue($filters['dateTo'] ?? $filters['date_to'] ?? ($input['dateTo'] ?? $input['date_to'] ?? ''));
    if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
        $dateFrom = '';
        $dateTo = '';
    }

    $category = normalizeEnumValue($filters['category'] ?? ($input['category'] ?? ''), $allowedCategories);
    $reportType = normalizeEnumValue($filters['reportType'] ?? $filters['report_type'] ?? ($input['reportType'] ?? $input['report_type'] ?? ''), $allowedCategories);
    $status = normalizeEnumValue($filters['status'] ?? ($input['status'] ?? ''), $allowedStatuses);
    $officer = normalizeFilterValue($filters['officer'] ?? ($input['officer'] ?? ''));
    $block = normalizeEnumValue($filters['block'] ?? ($input['block'] ?? ''), $allowedBlocks);
    $reportTypes = $filters['reportTypes'] ?? $filters['report_types'] ?? ($input['reportTypes'] ?? $input['report_types'] ?? []);

    if (!is_array($reportTypes)) {
        $reportTypes = [];
    }

    $reportTypes = array_values(array_filter(array_map(function ($item) use ($allowedCategories) {
        return normalizeEnumValue($item, $allowedCategories);
    }, $reportTypes)));

    return [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'category' => $category,
        'report_type' => $reportType,
        'status' => $status,
        'officer' => $officer,
        'block' => $block,
        'report_types' => $reportTypes,
    ];
}

function operationalDashboard(PDO $pdo, array $input): array {
    $filters = $input['filters'] ?? [];
    $from = normalizeDateValue($filters['dateFrom'] ?? '');
    $to = normalizeDateValue($filters['dateTo'] ?? '');
    $status = normalizeFilterValue($filters['status'] ?? '');
    $block = normalizeFilterValue($filters['block'] ?? '');
    $type = normalizeFilterValue($filters['prisonerType'] ?? $filters['category'] ?? '');
    $security = normalizeFilterValue($filters['securityLevel'] ?? $filters['reportType'] ?? '');
    $prisoner = normalizeFilterValue($filters['prisoner'] ?? $filters['officer'] ?? '');
    $where = ['1=1'];
    $params = [];
    if ($from !== '') { $where[] = 'p.admdate >= ?'; $params[] = $from; }
    if ($to !== '') { $where[] = 'p.admdate <= ?'; $params[] = $to; }
    if ($status !== '') { $where[] = 'LOWER(p.status) = ?'; $params[] = strtolower($status); }
    if ($block !== '') { $where[] = 'LOWER(COALESCE(c.block, "")) = ?'; $params[] = strtolower($block); }
    if ($type !== '') { $where[] = 'LOWER(p.prisonerType) LIKE ?'; $params[] = '%' . strtolower($type) . '%'; }
    if ($security !== '') { $where[] = 'LOWER(p.security) = ?'; $params[] = strtolower($security); }
    if ($prisoner !== '') { $where[] = '(LOWER(p.prisonerId) LIKE ? OR LOWER(CONCAT(p.firstname, " ", p.lastname)) LIKE ?)'; $params[] = '%' . strtolower($prisoner) . '%'; $params[] = '%' . strtolower($prisoner) . '%'; }

    $base = 'FROM prisoners p LEFT JOIN cells c ON c.cellNumber = p.cell WHERE ' . implode(' AND ', $where);
    $count = static function (PDO $pdo, string $sql, array $values = []): int {
        $stmt = $pdo->prepare($sql); $stmt->execute($values); return (int)$stmt->fetchColumn();
    };
    $total = $count($pdo, 'SELECT COUNT(*) ' . $base, $params);
    $active = $count($pdo, 'SELECT COUNT(*) ' . $base . " AND LOWER(p.status) = 'active'", $params);
    $remand = $count($pdo, 'SELECT COUNT(*) ' . $base . " AND LOWER(p.prisonerType) LIKE '%remand%'", $params);
    $sentenced = $count($pdo, 'SELECT COUNT(*) ' . $base . " AND LOWER(p.prisonerType) LIKE '%sentenced%'", $params);
    $released = $count($pdo, 'SELECT COUNT(*) ' . $base . " AND LOWER(p.status) = 'released'", $params);
    $transferred = $count($pdo, 'SELECT COUNT(*) ' . $base . " AND LOWER(p.status) = 'transferred'", $params);
    $upcoming = $count($pdo, "SELECT COUNT(*) FROM prisoners p WHERE p.`release` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND LOWER(p.status) NOT IN ('released','transferred')");
    $hearings = $count($pdo, "SELECT COUNT(*) FROM court WHERE hearingdate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND LOWER(status) NOT IN ('completed','closed')");
    $incidents = $count($pdo, "SELECT COUNT(*) FROM incidents WHERE incidentDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $visits = $count($pdo, "SELECT COUNT(*) FROM visitations WHERE visitDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
    $capacity = (int)($pdo->query('SELECT COALESCE(SUM(capacity),0) FROM cells')->fetchColumn() ?: 0);
    $occupied = (int)($pdo->query('SELECT COALESCE(SUM(occupied),0) FROM cells')->fetchColumn() ?: 0);

    $rowsStmt = $pdo->prepare('SELECT p.id, p.prisonerId, p.firstname, p.middlename, p.lastname, p.dob, p.gender, p.nationality, p.prisonerType, p.status, p.admdate, p.release, p.security, p.cell, COALESCE(c.block, "") AS block FROM prisoners p LEFT JOIN cells c ON c.cellNumber = p.cell WHERE ' . implode(' AND ', $where) . ' ORDER BY p.admdate DESC, p.id DESC LIMIT 500');
    $rowsStmt->execute($params);
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['photo_url'] = null;
        $row['title'] = trim(($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $row['type'] = $row['prisonerType'] ?? '';
        $row['reportId'] = $row['prisonerId'] ?? '';
        $row['reportDate'] = $row['admdate'] ?? null;
    }
    unset($row);

    $group = static function (PDO $pdo, string $sql): array {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    };
    $statusData = $group($pdo, 'SELECT status, COUNT(*) FROM prisoners GROUP BY status');
    $blockData = $group($pdo, 'SELECT COALESCE(NULLIF(c.block,""),"Unassigned"), COUNT(*) FROM prisoners p LEFT JOIN cells c ON c.cellNumber=p.cell GROUP BY c.block');
    $admissions = $group($pdo, "SELECT DATE_FORMAT(admdate,'%Y-%m') AS period, COUNT(*) FROM prisoners WHERE admdate IS NOT NULL GROUP BY period ORDER BY period");
    $charts = [
        'timeline' => ['type'=>'line','labels'=>array_keys($admissions),'datasets'=>[['label'=>'Admissions','data'=>array_values($admissions),'borderColor'=>'#2563d4','fill'=>false]]],
        'category' => ['type'=>'bar','labels'=>array_keys($statusData),'datasets'=>[['label'=>'Prisoners','data'=>array_values($statusData),'backgroundColor'=>'#2563d4']]],
        'status' => ['type'=>'doughnut','labels'=>array_keys($statusData),'datasets'=>[['label'=>'Population','data'=>array_values($statusData),'backgroundColor'=>['#22c55e','#f59e0b','#4f8ef7','#ef4444','#888']]]],
        'officer' => ['type'=>'bar','labels'=>[],'datasets'=>[]],
        'block' => ['type'=>'bar','labels'=>array_keys($blockData),'datasets'=>[['label'=>'Population by block','data'=>array_values($blockData),'backgroundColor'=>'#14b8a6']]],
        'monthly' => ['type'=>'bar','labels'=>array_keys($admissions),'datasets'=>[['label'=>'Admissions','data'=>array_values($admissions),'backgroundColor'=>'#22c55e']]],
        'daily' => ['type'=>'line','labels'=>[],'datasets'=>[]],
        'department' => ['type'=>'bar','labels'=>['Capacity','Occupied','Available'],'datasets'=>[['label'=>'Cells','data'=>[$capacity,$occupied,max(0,$capacity-$occupied)],'backgroundColor'=>['#4f8ef7','#f59e0b','#22c55e']]]],
    ];
    return ['statistics'=>[
        'total'=>$total,'pending'=>$remand,'approved'=>$sentenced,'rejected'=>$released,'archived'=>$transferred,'thisMonth'=>$incidents,
        'monthChange'=>0,'avgPerDay'=>0,'topCategory'=>$upcoming,'topOfficer'=>$hearings,'approvalRate'=>$occupied,'rejectionRate'=>max(0,$capacity-$occupied),
        'statusCounts'=>$statusData,'categoryCounts'=>$blockData,'officerCounts'=>[],'monthlyCounts'=>$admissions,'dailyCounts'=>[],
        'departmentData'=>[['department'=>'Visits this month','total'=>$visits,'approvalRate'=>0,'avgProcessingTime'=>0]],
        'operational'=>['active'=>$active,'remand'=>$remand,'sentenced'=>$sentenced,'released'=>$released,'transferred'=>$transferred,'upcomingReleases'=>$upcoming,'pendingHearings'=>$hearings,'incidentsThisMonth'=>$incidents,'visitsThisMonth'=>$visits,'capacity'=>$capacity,'occupied'=>$occupied,'available'=>max(0,$capacity-$occupied)],
    ], 'charts'=>$charts, 'table'=>$rows, 'message'=>$rows ? '' : 'No data available for the selected period.'];
}

function buildReportQuery(array $filters): array {
    $where = ['1=1'];
    $params = [];

    if ($filters['date_from'] !== '') {
        $where[] = '(r.reportDate >= ? OR r.created_at >= ?)';
        $params[] = $filters['date_from'] . ' 00:00:00';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if ($filters['date_to'] !== '') {
        $where[] = '(r.reportDate <= ? OR r.created_at <= ?)';
        $params[] = $filters['date_to'] . ' 23:59:59';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    if ($filters['category'] !== '') {
        $where[] = 'LOWER(COALESCE(r.category, r.type, "")) = ?';
        $params[] = strtolower($filters['category']);
    }
    if ($filters['report_type'] !== '') {
        $where[] = 'LOWER(COALESCE(r.type, "")) = ?';
        $params[] = strtolower($filters['report_type']);
    }
    if ($filters['status'] !== '') {
        $where[] = 'LOWER(COALESCE(r.status, "Pending")) = ?';
        $params[] = strtolower($filters['status']);
    }
    if ($filters['officer'] !== '') {
        $where[] = 'LOWER(COALESCE(r.author, "")) LIKE ?';
        $params[] = '%' . strtolower($filters['officer']) . '%';
    }
    if ($filters['block'] !== '') {
        $where[] = 'LOWER(COALESCE(r.block, "")) = ?';
        $params[] = strtolower($filters['block']);
    }
    if (!empty($filters['report_types']) && $filters['report_type'] === '') {
        $placeholders = implode(',', array_fill(0, count($filters['report_types']), '?'));
        $where[] = 'LOWER(COALESCE(r.type, "")) IN (' . $placeholders . ')';
        foreach ($filters['report_types'] as $reportType) {
            $params[] = strtolower($reportType);
        }
    }

    $sql = 'SELECT r.*, COALESCE(r.block, "") AS prisoner_block FROM reports r WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(r.reportDate, r.created_at, NOW()) DESC';
    return ['sql' => $sql, 'params' => $params];
}

function normalizeReportRow(array $row): array {
    $status = trim((string)($row['status'] ?? ''));
    if ($status === '') {
        $status = 'Pending';
    }
    $type = trim((string)($row['type'] ?? $row['category'] ?? 'General'));
    $block = trim((string)($row['block'] ?? $row['prisoner_block'] ?? ''));
    $department = trim((string)($row['department'] ?? ''));
    $processingTime = (float)($row['processing_time_hours'] ?? 0);
    $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
    return [
        'id' => $row['id'] ?? 0,
        'reportId' => $row['reportId'] ?? '',
        'title' => $row['title'] ?? '',
        'type' => $type,
        'status' => $status,
        'department' => $department,
        'block' => $block,
        'processing_time_hours' => $processingTime,
        'author' => $row['author'] ?? '',
        'prisoner' => $row['prisoner'] ?? '',
        'body' => $row['body'] ?? '',
        'reportDate' => $dateValue,
        'created_at' => $row['created_at'] ?? null,
    ];
}

function calculateStats(array $rows): array {
    $total = count($rows);
    $statusCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Archived' => 0, 'Draft' => 0];
    $categoryCounts = [];
    $officerCounts = [];
    $monthlyCounts = [];
    $dailyCounts = [];
    $departmentTotals = [];
    $departmentApprovalTotals = [];
    $departmentProcessingTotals = [];

    foreach ($rows as $row) {
        $status = strtoupper((string)($row['status'] ?? 'Pending'));
        $statusKey = $status === 'APPROVED' ? 'Approved' : ($status === 'REJECTED' ? 'Rejected' : ($status === 'ARCHIVED' ? 'Archived' : ($status === 'DRAFT' ? 'Draft' : 'Pending')));
        if (isset($statusCounts[$statusKey])) {
            $statusCounts[$statusKey]++;
        }

        $category = trim((string)($row['type'] ?? 'General'));
        if ($category === '') {
            $category = 'General';
        }
        $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;

        $officer = trim((string)($row['author'] ?? 'Unknown'));
        $officerCounts[$officer] = ($officerCounts[$officer] ?? 0) + 1;

        $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
        if ($dateValue) {
            try {
                $dateObj = new DateTime($dateValue);
                $monthKey = $dateObj->format('Y-m');
                $dailyKey = $dateObj->format('Y-m-d');
                $monthlyCounts[$monthKey] = ($monthlyCounts[$monthKey] ?? 0) + 1;
                $dailyCounts[$dailyKey] = ($dailyCounts[$dailyKey] ?? 0) + 1;
            } catch (Throwable $e) {
                // ignore invalid dates and continue
            }
        }

        $department = trim((string)($row['department'] ?? ''));
        if ($department === '') {
            $department = 'General';
        }
        $departmentTotals[$department] = ($departmentTotals[$department] ?? 0) + 1;
        $departmentApprovalTotals[$department] = ($departmentApprovalTotals[$department] ?? 0) + ($row['status'] === 'Approved' ? 1 : 0);
        $departmentProcessingTotals[$department] = ($departmentProcessingTotals[$department] ?? 0) + ((float)($row['processing_time_hours'] ?? 0));
    }

    $thisMonth = 0;
    $currentMonth = date('Y-m');
    foreach ($rows as $row) {
        $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
        if ($dateValue && strtotime($dateValue) !== false && date('Y-m', strtotime($dateValue)) === $currentMonth) {
            $thisMonth++;
        }
    }

    $lastMonth = 0;
    $previousMonth = date('Y-m', strtotime('-1 month'));
    foreach ($rows as $row) {
        $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
        if ($dateValue && strtotime($dateValue) !== false && date('Y-m', strtotime($dateValue)) === $previousMonth) {
            $lastMonth++;
        }
    }

    $monthChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100) : 0;

    arsort($categoryCounts);
    arsort($officerCounts);
    ksort($monthlyCounts);
    ksort($dailyCounts);

    $topCategory = !empty($categoryCounts) ? array_key_first($categoryCounts) : 'N/A';
    $topOfficer = !empty($officerCounts) ? array_key_first($officerCounts) : 'N/A';
    $approved = $statusCounts['Approved'];
    $rejected = $statusCounts['Rejected'];
    $approvalRate = $total > 0 ? round(($approved / $total) * 100) : 0;
    $rejectionRate = $total > 0 ? round(($rejected / $total) * 100) : 0;
    $dayCount = 0;
    $daySet = [];
    foreach ($rows as $row) {
        $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
        if ($dateValue && strtotime($dateValue) !== false) {
            $dayKey = date('Y-m-d', strtotime($dateValue));
            if (!isset($daySet[$dayKey])) {
                $daySet[$dayKey] = true;
                $dayCount++;
            }
        }
    }
    $avgPerDay = $dayCount > 0 ? round($total / $dayCount, 2) : 0;

    $departmentData = [];
    foreach ($departmentTotals as $department => $count) {
        $approvalRateValue = $count > 0 ? round((($departmentApprovalTotals[$department] ?? 0) / $count) * 100) : 0;
        $avgProcessing = $count > 0 ? round((($departmentProcessingTotals[$department] ?? 0) / $count), 2) : 0;
        $departmentData[] = [
            'department' => $department,
            'total' => $count,
            'approvalRate' => $approvalRateValue,
            'avgProcessingTime' => $avgProcessing,
        ];
    }

    return [
        'total' => $total,
        'pending' => $statusCounts['Pending'],
        'approved' => $approved,
        'rejected' => $rejected,
        'archived' => $statusCounts['Archived'],
        'thisMonth' => $thisMonth,
        'monthChange' => $monthChange,
        'avgPerDay' => $avgPerDay,
        'topCategory' => $topCategory,
        'topOfficer' => $topOfficer,
        'approvalRate' => $approvalRate,
        'rejectionRate' => $rejectionRate,
        'statusCounts' => $statusCounts,
        'categoryCounts' => $categoryCounts,
        'officerCounts' => $officerCounts,
        'monthlyCounts' => $monthlyCounts,
        'dailyCounts' => $dailyCounts,
        'departmentData' => $departmentData,
    ];
}

function buildChartPayload(array $stats, array $rows): array {
    $timelineLabels = array_keys($stats['monthlyCounts']);
    $timelineData = array_values($stats['monthlyCounts']);

    $categoryLabels = array_keys($stats['categoryCounts']);
    $categoryData = array_values($stats['categoryCounts']);

    $statusLabels = ['Pending', 'Approved', 'Rejected', 'Archived', 'Draft'];
    $statusData = [];
    foreach ($statusLabels as $label) {
        $statusData[] = $stats['statusCounts'][$label] ?? 0;
    }

    $officerLabels = array_slice(array_keys($stats['officerCounts']), 0, 10);
    $officerData = array_values(array_slice($stats['officerCounts'], 0, 10, true));

    $blockLabels = [];
    $blockData = [];
    foreach ($rows as $row) {
        $block = trim((string)($row['block'] ?? ''));
        if ($block === '') {
            $block = 'Unassigned';
        }
        if (!isset($blockData[$block])) {
            $blockData[$block] = 0;
        }
        $blockData[$block]++;
    }
    if (!empty($blockData)) {
        arsort($blockData);
        $blockLabels = array_keys($blockData);
        $blockData = array_values($blockData);
    }

    $monthlyLabels = [];
    $monthlyDatasetData = [];
    $categoryNames = array_keys($stats['categoryCounts']);
    foreach ($categoryNames as $category) {
        $monthlyDatasetData[$category] = [];
    }
    foreach ($stats['monthlyCounts'] as $month => $count) {
        $monthlyLabels[] = $month;
    }

    foreach ($categoryNames as $category) {
        $series = [];
        foreach ($monthlyLabels as $month) {
            $series[] = 0;
        }
        $monthlyDatasetData[$category] = $series;
    }

    foreach ($rows as $row) {
        $dateValue = $row['reportDate'] ?? $row['created_at'] ?? null;
        if ($dateValue) {
            $month = date('Y-m', strtotime($dateValue));
            $category = trim((string)($row['type'] ?? 'General'));
            if ($category === '') {
                $category = 'General';
            }
            $idx = array_search($month, $monthlyLabels, true);
            if ($idx !== false && isset($monthlyDatasetData[$category])) {
                $monthlyDatasetData[$category][$idx] = ($monthlyDatasetData[$category][$idx] ?? 0) + 1;
            }
        }
    }

    $monthlyDatasets = [];
    $colors = ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c', '#34495e'];
    foreach ($categoryNames as $index => $category) {
        $monthlyDatasets[] = [
            'label' => $category,
            'data' => $monthlyDatasetData[$category] ?? [],
            'backgroundColor' => $colors[$index % count($colors)],
        ];
    }

    $dailyLabels = array_keys($stats['dailyCounts']);
    $dailyData = array_values($stats['dailyCounts']);
    $dailyLabels = array_slice($dailyLabels, -30);
    $dailyData = array_slice($dailyData, -30);

    $departmentLabels = [];
    $departmentTotals = [];
    $departmentApprovalRates = [];
    $departmentProcessingTimes = [];
    foreach ($stats['departmentData'] as $item) {
        $departmentLabels[] = $item['department'];
        $departmentTotals[] = $item['total'];
        $departmentApprovalRates[] = $item['approvalRate'];
        $departmentProcessingTimes[] = $item['avgProcessingTime'];
    }

    return [
        'timeline' => [
            'type' => 'line',
            'labels' => $timelineLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $timelineData,
                'borderColor' => '#3498db',
                'backgroundColor' => 'rgba(52, 152, 219, 0.15)',
                'fill' => false,
                'tension' => 0.3,
            ]],
        ],
        'category' => [
            'type' => 'bar',
            'labels' => $categoryLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $categoryData,
                'backgroundColor' => '#3498db',
            ]],
        ],
        'status' => [
            'type' => 'pie',
            'labels' => $statusLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $statusData,
                'backgroundColor' => ['#f39c12', '#2ecc71', '#e74c3c', '#95a5a6', '#7f8c8d'],
            ]],
        ],
        'officer' => [
            'type' => 'bar',
            'labels' => $officerLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $officerData,
                'backgroundColor' => '#9b59b6',
            ]],
            'options' => ['indexAxis' => 'y'],
        ],
        'block' => [
            'type' => 'bar',
            'labels' => $blockLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $blockData,
                'backgroundColor' => '#1abc9c',
            ]],
        ],
        'monthly' => [
            'type' => 'bar',
            'labels' => $monthlyLabels,
            'datasets' => $monthlyDatasets,
        ],
        'daily' => [
            'type' => 'line',
            'labels' => $dailyLabels,
            'datasets' => [[
                'label' => 'Reports',
                'data' => $dailyData,
                'borderColor' => '#e67e22',
                'backgroundColor' => 'rgba(230, 126, 34, 0.2)',
                'fill' => true,
                'tension' => 0.2,
            ]],
        ],
        'department' => [
            'type' => 'bar',
            'labels' => $departmentLabels,
            'datasets' => [
                [
                    'label' => 'Total Reports',
                    'data' => $departmentTotals,
                    'backgroundColor' => '#3498db',
                ],
                [
                    'label' => 'Approval Rate',
                    'data' => $departmentApprovalRates,
                    'backgroundColor' => '#2ecc71',
                ],
                [
                    'label' => 'Avg Processing Time',
                    'data' => $departmentProcessingTimes,
                    'backgroundColor' => '#f39c12',
                ],
            ],
        ],
    ];
}

ensureReportsTable($pdo);

$allowedActions = ['dashboard', 'operational_dashboard', 'summary', 'statistics', 'charts', 'export'];
if (!in_array($action, $allowedActions, true)) {
    jsonOut(['success' => false, 'statistics' => [], 'charts' => [], 'table' => [], 'message' => 'Unknown action'], 400);
}

try {
    if ($action === 'dashboard' || $action === 'operational_dashboard') {
        $payload = operationalDashboard($pdo, $body);
        jsonOut(['success' => true] + $payload);
    }
    $filters = buildReportFilters($body);
    $query = buildReportQuery($filters);
    $stmt = $pdo->prepare($query['sql']);
    $stmt->execute($query['params']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $normalizedRows = array_map('normalizeReportRow', $rows);
    $stats = calculateStats($normalizedRows);
    $charts = buildChartPayload($stats, $normalizedRows);

    if ($action === 'summary') {
        jsonOut([
            'success' => true,
            'statistics' => [
                'total' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved'],
                'rejected' => $stats['rejected'],
                'archived' => $stats['archived'],
                'thisMonth' => $stats['thisMonth'],
                'monthChange' => $stats['monthChange'],
            ],
            'charts' => [],
            'table' => $normalizedRows,
            'message' => $normalizedRows ? '' : 'No reports found for the selected filters.',
        ]);
    }

    if ($action === 'statistics') {
        jsonOut([
            'success' => true,
            'statistics' => [
                'avgPerDay' => $stats['avgPerDay'],
                'topCategory' => $stats['topCategory'],
                'topOfficer' => $stats['topOfficer'],
                'thisMonth' => $stats['thisMonth'],
                'approvalRate' => $stats['approvalRate'],
                'rejectionRate' => $stats['rejectionRate'],
            ],
            'charts' => [],
            'table' => $normalizedRows,
            'message' => $normalizedRows ? '' : 'No reports found for the selected filters.',
        ]);
    }

    if ($action === 'charts') {
        jsonOut([
            'success' => true,
            'statistics' => [],
            'charts' => $charts,
            'table' => [],
            'message' => $normalizedRows ? '' : 'No reports found for the selected filters.',
        ]);
    }

    if ($action === 'export') {
        jsonOut([
            'success' => true,
            'statistics' => $stats,
            'charts' => $charts,
            'table' => $normalizedRows,
            'message' => $normalizedRows ? '' : 'No reports found for the selected filters.',
        ]);
    }

    jsonOut([
        'success' => true,
        'statistics' => $stats,
        'charts' => $charts,
        'table' => $normalizedRows,
        'message' => $normalizedRows ? '' : 'No reports found for the selected filters.',
    ]);
} catch (Throwable $e) {
    logAnalyticsError($e, 'dashboard query');
    jsonOut([
        'success' => false,
        'statistics' => [],
        'charts' => [],
        'table' => [],
        'message' => 'Unable to load reports at the moment.',
    ], 500);
}
