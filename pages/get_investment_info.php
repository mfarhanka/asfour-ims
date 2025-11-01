<?php
require_once __DIR__ . '/../config.php';

$clientInvestmentId = intval($_GET['client_investment_id'] ?? 0);

if ($clientInvestmentId <= 0) {
    echo '<div class="alert alert-danger">Invalid client investment ID</div>';
    exit;
}

// Get the specific client investment record with all details
$sql = "SELECT i.*, ci.invested_amount as client_invested_amount, ci.investment_date as client_investment_date,
               c.name as client_name, c.username as client_username, c.email as client_email, c.phone as client_phone, c.created_at as client_member_since,
               (SELECT COUNT(*) FROM client_investments WHERE investment_id = i.id) as total_investors,
               (SELECT COALESCE(SUM(invested_amount), 0) FROM client_investments WHERE investment_id = i.id) as total_invested
        FROM client_investments ci
        LEFT JOIN investments i ON ci.investment_id = i.id
        LEFT JOIN clients c ON ci.client_id = c.id
        WHERE ci.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $clientInvestmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $investment = $result->fetch_assoc();
    
    $currentExpectedProfit = ($investment['total_invested'] * $investment['profit_percent']) / 100;
    $clientExpectedProfit = ($investment['client_invested_amount'] * $investment['profit_percent']) / 100;
    
    $startDate = new DateTime($investment['start_date']);
    $endDate = new DateTime($investment['end_date']);
    $currentDate = new DateTime();
    $totalDuration = $startDate->diff($endDate)->days;
    $daysElapsed = $startDate->diff($currentDate)->days;
    
    echo '<div class="row">';
    
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header bg-primary text-white"><h6><i class="fa fa-user"></i> Investor</h6></div>';
    echo '<div class="card-body">';
    
    if ($investment['client_name']) {
        echo '<div class="text-center mb-3">';
        echo '<i class="fa fa-user-circle fa-4x text-primary"></i>';
        echo '<h5 class="mt-2">' . htmlspecialchars($investment['client_name']) . '</h5>';
        echo '<p class="text-muted">@' . htmlspecialchars($investment['client_username']) . '</p>';
        echo '</div>';
        
        echo '<table class="table table-sm">';
        echo '<tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($investment['client_email'] ?: 'N/A') . '</td></tr>';
        echo '<tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($investment['client_phone'] ?: 'N/A') . '</td></tr>';
        echo '<tr><td><strong>Member Since:</strong></td><td>' . date('M d, Y', strtotime($investment['client_member_since'])) . '</td></tr>';
        echo '</table>';
        

    } else {
        echo '<div class="alert alert-warning">';
        echo '<h6>Investor Not Found</h6>';
        echo '<p class="mb-0">Could not find investor information.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-6">';
    echo '<div class="card">';
    echo '<div class="card-header bg-info text-white"><h6><i class="fa fa-info-circle"></i> Investment Details</h6></div>';
    echo '<div class="card-body">';
    echo '<table class="table table-sm">';
    echo '<tr><td><strong>Project Title:</strong></td><td>' . htmlspecialchars($investment['title']) . '</td></tr>';
    echo '<tr><td><strong>Target Goal:</strong></td><td>$' . number_format($investment['total_goal'], 2) . '</td></tr>';
    echo '<tr><td><strong>Profit Rate:</strong></td><td>' . number_format($investment['profit_percent'], 2) . '%</td></tr>';
    echo '<tr><td><strong>Start Date:</strong></td><td>' . $investment['start_date'] . '</td></tr>';
    echo '<tr><td><strong>End Date:</strong></td><td>' . $investment['end_date'] . '</td></tr>';
    echo '<tr><td><strong>This Client Invested:</strong></td><td class="text-success"><strong>$' . number_format($investment['client_invested_amount'], 2) . '</strong></td></tr>';
    echo '<tr><td><strong>Investment Date:</strong></td><td>' . date('M d, Y', strtotime($investment['client_investment_date'])) . '</td></tr>';
    echo '<tr><td><strong>Expected Profit:</strong></td><td class="text-primary"><strong>$' . number_format($clientExpectedProfit, 2) . '</strong></td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="row mt-3">';
    echo '<div class="col-12">';
    echo '<div class="card">';
    echo '<div class="card-header bg-info text-white"><h6><i class="fa fa-calendar"></i> Timeline</h6></div>';
    echo '<div class="card-body">';
    
    $timelinePercentage = 0;
    if ($totalDuration > 0) {
        if ($currentDate <= $startDate) {
            $timelinePercentage = 0;
        } elseif ($currentDate >= $endDate) {
            $timelinePercentage = 100;
        } else {
            $timelinePercentage = ($daysElapsed / $totalDuration) * 100;
        }
    }
    
    echo '<div class="progress mb-3" style="height: 20px;">';
    echo '<div class="progress-bar" role="progressbar" style="width: ' . $timelinePercentage . '%;">';
    echo number_format($timelinePercentage, 1) . '%';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="row text-center">';
    echo '<div class="col-4"><small><strong>Start</strong><br>' . $investment['start_date'] . '</small></div>';
    echo '<div class="col-4"><small><strong>Today</strong><br>' . date('Y-m-d') . '</small></div>';
    echo '<div class="col-4"><small><strong>End</strong><br>' . $investment['end_date'] . '</small></div>';
    echo '</div>';
    
    echo '</div></div></div></div>';
    
} else {
    echo '<div class="alert alert-warning">';
    echo '<h6>Investment Not Found</h6>';
    echo '<p>The requested investment project could not be found.</p>';
    echo '</div>';
}

$stmt->close();
?>