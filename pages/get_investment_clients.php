<?php
require_once __DIR__ . '/../config.php';

// Get investment ID from query parameter
$investmentId = intval($_GET['investment_id'] ?? 0);

if ($investmentId <= 0) {
    echo '<div class="alert alert-danger">Invalid investment ID</div>';
    exit;
}

// Get investment details with client investment summary
$investmentSQL = "SELECT i.*, 
                         COUNT(ci.id) as total_investors,
                         COALESCE(SUM(ci.invested_amount), 0) as total_invested
                  FROM investments i 
                  LEFT JOIN client_investments ci ON i.id = ci.investment_id 
                  WHERE i.id = ?
                  GROUP BY i.id";

$stmt = $conn->prepare($investmentSQL);
$stmt->bind_param("i", $investmentId);
$stmt->execute();
$investmentResult = $stmt->get_result();

if (!$investmentResult || $investmentResult->num_rows == 0) {
    echo '<div class="alert alert-danger">Investment not found</div>';
    exit;
}

$investment = $investmentResult->fetch_assoc();
$stmt->close();

// Get client investment details for this investment
$sql = "SELECT ci.id, ci.invested_amount, ci.investment_date, ci.created_at,
               c.name as client_name, c.username as client_username, c.email as client_email, c.phone as client_phone
        FROM client_investments ci 
        LEFT JOIN clients c ON ci.client_id = c.id 
        WHERE ci.investment_id = ?
        ORDER BY ci.investment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $investmentId);
$stmt->execute();
$result = $stmt->get_result();

// Calculate progress and expected profits
$progressPercent = ($investment['total_invested'] / $investment['total_goal']) * 100;
$remainingAmount = $investment['total_goal'] - $investment['total_invested'];

// Handle profit range or fixed profit
$profitPercentMin = $investment['profit_percent_min'] ?? $investment['profit_percent'];
$profitPercentMax = $investment['profit_percent_max'] ?? $investment['profit_percent'];
$isProfitRange = ($profitPercentMin != $profitPercentMax);

if ($isProfitRange) {
    // Calculate profit range
    $expectedTotalProfitMin = ($investment['total_goal'] * $profitPercentMin) / 100;
    $expectedTotalProfitMax = ($investment['total_goal'] * $profitPercentMax) / 100;
    $currentExpectedProfitMin = ($investment['total_invested'] * $profitPercentMin) / 100;
    $currentExpectedProfitMax = ($investment['total_invested'] * $profitPercentMax) / 100;
    $profitDisplay = number_format($profitPercentMin, 2) . '% - ' . number_format($profitPercentMax, 2) . '%';
} else {
    // Fixed profit
    $expectedTotalProfit = ($investment['total_goal'] * $investment['profit_percent']) / 100;
    $currentExpectedProfit = ($investment['total_invested'] * $investment['profit_percent']) / 100;
    $profitDisplay = number_format($investment['profit_percent'], 2) . '%';
}

// Calculate time-based information
$startDate = new DateTime($investment['start_date']);
$endDate = new DateTime($investment['end_date']);
$currentDate = new DateTime();
$totalDuration = $startDate->diff($endDate)->days;
$daysElapsed = $startDate->diff($currentDate)->days;
$daysRemaining = $currentDate->diff($endDate)->days;

// Calculate project period if duration is set
$projectPeriodStart = null;
$projectPeriodEnd = null;
if ($investment['duration']) {
    $investmentEndDate = new DateTime($investment['end_date']);
    $projectPeriodStart = clone $investmentEndDate;
    $projectPeriodStart->modify('+1 day');
    
    // Parse duration
    $months = 0;
    if (strpos($investment['duration'], 'month') !== false) {
        $months = intval($investment['duration']);
    } elseif (strpos($investment['duration'], 'year') !== false) {
        $months = intval($investment['duration']) * 12;
    }
    
    if ($months > 0) {
        $projectPeriodEnd = clone $projectPeriodStart;
        $projectPeriodEnd->modify("+{$months} months");
    }
}

if ($currentDate > $endDate) {
    $timeStatus = 'Expired';
    $timeStatusClass = 'danger';
    $daysRemaining = 0;
} elseif ($currentDate < $startDate) {
    $timeStatus = 'Not Started';
    $timeStatusClass = 'warning';
} else {
    $timeStatus = 'Active';
    $timeStatusClass = 'success';
}

echo '<div class="row">';

// Basic Investment Information
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-primary text-white"><h6><i class="fa fa-info-circle"></i> Basic Information</h6></div>';
echo '<div class="card-body">';
echo '<table class="table table-sm">';
echo '<tr><td><strong>Title:</strong></td><td>' . htmlspecialchars($investment['title']) . '</td></tr>';
echo '<tr><td><strong>Status:</strong></td><td><span class="badge badge-' . $timeStatusClass . '">' . $timeStatus . '</span></td></tr>';
echo '<tr><td><strong>Investment Period:</strong></td><td>' . htmlspecialchars($investment['start_date']) . ' - ' . htmlspecialchars($investment['end_date']) . '</td></tr>';
echo '<tr><td><strong>Investment Duration:</strong></td><td>' . $totalDuration . ' days</td></tr>';
if ($timeStatus == 'Active') {
    echo '<tr><td><strong>Days Remaining:</strong></td><td>' . $daysRemaining . ' days to invest</td></tr>';
}
if ($investment['duration']) {
    echo '<tr><td><strong>Project Duration:</strong></td><td>' . htmlspecialchars($investment['duration']) . '</td></tr>';
    if ($projectPeriodStart && $projectPeriodEnd) {
        echo '<tr><td><strong>Project Period:</strong></td><td>' . $projectPeriodStart->format('M d, Y') . ' - ' . $projectPeriodEnd->format('M d, Y') . '</td></tr>';
    }
}
echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';

// Financial Information
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-success text-white"><h6><i class="fa fa-dollar"></i> Financial Overview</h6></div>';
echo '<div class="card-body">';
echo '<table class="table table-sm">';
echo '<tr><td><strong>Target Goal:</strong></td><td>$' . number_format($investment['total_goal'], 2) . '</td></tr>';
echo '<tr><td><strong>Amount Raised:</strong></td><td>$' . number_format($investment['total_invested'], 2) . '</td></tr>';
echo '<tr><td><strong>Remaining:</strong></td><td>$' . number_format($remainingAmount, 2) . '</td></tr>';
echo '<tr><td><strong>Progress:</strong></td><td>' . number_format($progressPercent, 1) . '%</td></tr>';
echo '<tr><td><strong>Total Investors:</strong></td><td>' . $investment['total_investors'] . '</td></tr>';
echo '<tr><td><strong>Profit Rate:</strong></td><td>' . $profitDisplay . '</td></tr>';
echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End row

// Progress Bar
echo '<div class="row mt-3">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header bg-info text-white"><h6><i class="fa fa-chart-bar"></i> Funding Progress</h6></div>';
echo '<div class="card-body">';
echo '<div class="progress mb-3" style="height: 25px;">';
$progressColor = $progressPercent >= 100 ? 'bg-success' : ($progressPercent >= 75 ? 'bg-info' : ($progressPercent >= 50 ? 'bg-warning' : 'bg-danger'));
echo '<div class="progress-bar ' . $progressColor . '" role="progressbar" style="width: ' . min($progressPercent, 100) . '%;" aria-valuenow="' . $progressPercent . '" aria-valuemin="0" aria-valuemax="100">';
echo number_format($progressPercent, 1) . '%';
echo '</div>';
echo '</div>';
echo '<p class="text-center"><strong>$' . number_format($investment['total_invested'], 2) . '</strong> of <strong>$' . number_format($investment['total_goal'], 2) . '</strong> raised</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Profit Calculations
echo '<div class="row mt-3">';
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-warning text-dark"><h6><i class="fa fa-calculator"></i> Expected Profit (If Fully Funded)</h6></div>';
echo '<div class="card-body">';
echo '<table class="table table-sm">';
echo '<tr><td><strong>Total Investment Goal:</strong></td><td>$' . number_format($investment['total_goal'], 2) . '</td></tr>';
echo '<tr><td><strong>Profit Rate:</strong></td><td>' . $profitDisplay . '</td></tr>';
if ($isProfitRange) {
    echo '<tr><td><strong>Expected Total Profit:</strong></td><td class="text-success"><strong>$' . number_format($expectedTotalProfitMin, 2) . ' - $' . number_format($expectedTotalProfitMax, 2) . '</strong></td></tr>';
    echo '<tr><td><strong>Total Return:</strong></td><td class="text-primary"><strong>$' . number_format($investment['total_goal'] + $expectedTotalProfitMin, 2) . ' - $' . number_format($investment['total_goal'] + $expectedTotalProfitMax, 2) . '</strong></td></tr>';
} else {
    echo '<tr><td><strong>Expected Total Profit:</strong></td><td class="text-success"><strong>$' . number_format($expectedTotalProfit, 2) . '</strong></td></tr>';
    echo '<tr><td><strong>Total Return:</strong></td><td class="text-primary"><strong>$' . number_format($investment['total_goal'] + $expectedTotalProfit, 2) . '</strong></td></tr>';
}
echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header bg-secondary text-white"><h6><i class="fa fa-money"></i> Expected Profit (Current Funded)</h6></div>';
echo '<div class="card-body">';
echo '<table class="table table-sm">';
echo '<tr><td><strong>Current Investment:</strong></td><td>$' . number_format($investment['total_invested'], 2) . '</td></tr>';
echo '<tr><td><strong>Profit Rate:</strong></td><td>' . $profitDisplay . '</td></tr>';
if ($isProfitRange) {
    echo '<tr><td><strong>Current Expected Profit:</strong></td><td class="text-success"><strong>$' . number_format($currentExpectedProfitMin, 2) . ' - $' . number_format($currentExpectedProfitMax, 2) . '</strong></td></tr>';
    echo '<tr><td><strong>Current Total Return:</strong></td><td class="text-primary"><strong>$' . number_format($investment['total_invested'] + $currentExpectedProfitMin, 2) . ' - $' . number_format($investment['total_invested'] + $currentExpectedProfitMax, 2) . '</strong></td></tr>';
} else {
    echo '<tr><td><strong>Current Expected Profit:</strong></td><td class="text-success"><strong>$' . number_format($currentExpectedProfit, 2) . '</strong></td></tr>';
    echo '<tr><td><strong>Current Total Return:</strong></td><td class="text-primary"><strong>$' . number_format($investment['total_invested'] + $currentExpectedProfit, 2) . '</strong></td></tr>';
}
echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Client Investments Section
echo '<div class="row mt-3">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header bg-dark text-white"><h6><i class="fa fa-users"></i> Client Investments</h6></div>';
echo '<div class="card-body">';

if ($result && $result->num_rows > 0) {
    // Store client data
    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    echo '<div class="mb-3">';
    echo '<h6><strong>Number of Investors: ' . count($clients) . '</strong></h6>';
    echo '</div>';
    
    echo '<table class="table table-striped table-sm">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Client Name</th>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Amount Invested</th>';
    echo '<th>Investment Date</th>';
    echo '<th>Record Created</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($clients as $client) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($client['client_name'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($client['client_username'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($client['client_email'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($client['client_phone'] ?: 'N/A') . '</td>';
        echo '<td>$' . number_format($client['invested_amount'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($client['investment_date']) . '</td>';
        echo '<td>' . htmlspecialchars($client['created_at']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<div class="alert alert-info">';
    echo '<h6>No Clients Have Invested Yet</h6>';
    echo '<p>This investment project has not received any client investments.</p>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

$stmt->close();
?>