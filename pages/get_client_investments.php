<?php
require_once __DIR__ . '/../config.php';

$clientId = intval($_GET['client_id'] ?? 0);

if ($clientId <= 0) {
    echo '<div class="alert alert-danger">Invalid client ID</div>';
    exit;
}

// Get client information
$clientSQL = "SELECT name, username, email FROM clients WHERE id = ?";
$clientStmt = $conn->prepare($clientSQL);
$clientStmt->bind_param("i", $clientId);
$clientStmt->execute();
$clientResult = $clientStmt->get_result();

if (!$clientResult || $clientResult->num_rows == 0) {
    echo '<div class="alert alert-danger">Client not found</div>';
    exit;
}

$client = $clientResult->fetch_assoc();
$clientStmt->close();

// Get client investments
$investmentsSQL = "SELECT ci.id, ci.invested_amount, ci.investment_date, ci.created_at,
                          i.title, i.total_goal, i.profit_percent, i.start_date, i.end_date
                   FROM client_investments ci
                   LEFT JOIN investments i ON ci.investment_id = i.id
                   WHERE ci.client_id = ?
                   ORDER BY ci.investment_date DESC";

$investmentsStmt = $conn->prepare($investmentsSQL);
$investmentsStmt->bind_param("i", $clientId);
$investmentsStmt->execute();
$investmentsResult = $investmentsStmt->get_result();

// Display client header
echo '<div class="row mb-3">';
echo '<div class="col-12">';
echo '<div class="card bg-light">';
echo '<div class="card-body">';
echo '<div class="row align-items-center">';
echo '<div class="col-2 text-center">';
echo '<i class="fa fa-user-circle fa-4x text-primary"></i>';
echo '</div>';
echo '<div class="col-10">';
echo '<h5 class="mb-1">' . htmlspecialchars($client['name']) . '</h5>';
echo '<p class="text-muted mb-1">@' . htmlspecialchars($client['username']) . '</p>';
echo '<p class="text-muted mb-0">' . htmlspecialchars($client['email']) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

if ($investmentsResult && $investmentsResult->num_rows > 0) {
    $totalInvested = 0;
    $totalExpectedProfit = 0;
    
    echo '<h6><i class="fa fa-list"></i> Investment Projects (' . $investmentsResult->num_rows . ')</h6>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-sm">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Project Title</th>';
    echo '<th>Amount Invested</th>';
    echo '<th>Investment Date</th>';
    echo '<th>Profit Rate</th>';
    echo '<th>Expected Profit</th>';
    echo '<th>Total Return</th>';
    echo '<th>Project Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($investment = $investmentsResult->fetch_assoc()) {
        $expectedProfit = ($investment['invested_amount'] * $investment['profit_percent']) / 100;
        $totalReturn = $investment['invested_amount'] + $expectedProfit;
        $totalInvested += $investment['invested_amount'];
        $totalExpectedProfit += $expectedProfit;
        
        // Determine project status
        $startDate = new DateTime($investment['start_date']);
        $endDate = new DateTime($investment['end_date']);
        $currentDate = new DateTime();
        
        if ($currentDate > $endDate) {
            $status = '<span class="badge badge-danger">Expired</span>';
        } elseif ($currentDate < $startDate) {
            $status = '<span class="badge badge-warning">Not Started</span>';
        } else {
            $status = '<span class="badge badge-success">Active</span>';
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($investment['title']) . '</td>';
        echo '<td>$' . number_format($investment['invested_amount'], 2) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($investment['investment_date'])) . '</td>';
        echo '<td>' . number_format($investment['profit_percent'], 2) . '%</td>';
        echo '<td class="text-success">$' . number_format($expectedProfit, 2) . '</td>';
        echo '<td class="text-primary">$' . number_format($totalReturn, 2) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Summary section
    echo '<div class="row mt-3">';
    echo '<div class="col-12">';
    echo '<div class="card bg-info text-white">';
    echo '<div class="card-body">';
    echo '<h6><i class="fa fa-chart-bar"></i> Investment Summary</h6>';
    echo '<div class="row">';
    echo '<div class="col-4 text-center">';
    echo '<h4>$' . number_format($totalInvested, 2) . '</h4>';
    echo '<small>Total Invested</small>';
    echo '</div>';
    echo '<div class="col-4 text-center">';
    echo '<h4>$' . number_format($totalExpectedProfit, 2) . '</h4>';
    echo '<small>Expected Profit</small>';
    echo '</div>';
    echo '<div class="col-4 text-center">';
    echo '<h4>$' . number_format($totalInvested + $totalExpectedProfit, 2) . '</h4>';
    echo '<small>Total Expected Return</small>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
} else {
    echo '<div class="alert alert-info">';
    echo '<h6>No Investments</h6>';
    echo '<p class="mb-0">This client has not made any investments yet.</p>';
    echo '</div>';
}

$investmentsStmt->close();
?>