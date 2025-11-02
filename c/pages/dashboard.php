<?php 
/* c/pages/dashboard.php - Client Dashboard Content */

// Enable error reporting for debugging (remove after fixing)
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    $client_id = $_SESSION['client_id'] ?? null;
    
    if (!$client_id) {
        throw new Exception('No client session found');
    }
    
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection not available');
    }

    // Get client information
    $clientSQL = "SELECT name, username, email FROM clients WHERE id = ?";
    $clientStmt = $conn->prepare($clientSQL);
    
    if (!$clientStmt) {
        throw new Exception('Failed to prepare client query: ' . $conn->error);
    }
    
    $clientStmt->bind_param("i", $client_id);
    $clientStmt->execute();
    
    // Bind results for compatibility with older MySQL drivers
    $clientStmt->bind_result($client_name, $client_username, $client_email);
    
    if ($clientStmt->fetch()) {
        $clientInfo = [
            'name' => $client_name,
            'username' => $client_username,
            'email' => $client_email
        ];
        $clientStmt->close();
    } else {
        $clientStmt->close();
        throw new Exception('Client information not found');
    }

    // Get client's investment statistics (simplified query to avoid column issues)
    $statsSQL = "SELECT 
        COUNT(DISTINCT ci.investment_id) as total_projects_invested,
        COALESCE(SUM(ci.invested_amount), 0) as total_invested_amount,
        COUNT(ci.id) as total_investments,
        MIN(ci.investment_date) as first_investment_date,
        MAX(ci.investment_date) as last_investment_date
    FROM client_investments ci
    WHERE ci.client_id = ?";

    $statsStmt = $conn->prepare($statsSQL);
    
    if (!$statsStmt) {
        throw new Exception('Failed to prepare stats query: ' . $conn->error);
    }
    
    $statsStmt->bind_param("i", $client_id);
    $statsStmt->execute();
    
    // Bind results for compatibility
    $statsStmt->bind_result($total_projects, $total_amount, $total_investments, $first_date, $last_date);
    
    if ($statsStmt->fetch()) {
        $stats = [
            'total_projects_invested' => $total_projects ?? 0,
            'total_invested_amount' => $total_amount ?? 0,
            'total_investments' => $total_investments ?? 0,
            'first_investment_date' => $first_date,
            'last_investment_date' => $last_date
        ];
        $statsStmt->close();
    } else {
        $statsStmt->close();
        // Set default values if no data found
        $stats = [
            'total_projects_invested' => 0,
            'total_invested_amount' => 0,
            'total_investments' => 0,
            'first_investment_date' => null,
            'last_investment_date' => null
        ];
    }
    
    // Calculate expected profit separately (safer approach)
    $profitSQL = "SELECT 
        COALESCE(SUM(ci.invested_amount * i.profit_percent / 100), 0) as total_expected_profit
    FROM client_investments ci
    LEFT JOIN investments i ON ci.investment_id = i.id
    WHERE ci.client_id = ?";
    
    $profitStmt = $conn->prepare($profitSQL);
    
    if ($profitStmt) {
        $profitStmt->bind_param("i", $client_id);
        $profitStmt->execute();
        
        // Bind result for profit calculation
        $profitStmt->bind_result($total_expected_profit);
        
        if ($profitStmt->fetch()) {
            $stats['total_expected_profit'] = $total_expected_profit ?? 0;
        } else {
            $stats['total_expected_profit'] = 0;
        }
        $profitStmt->close();
    } else {
        $stats['total_expected_profit'] = 0;
    }

    // Get client's investment portfolio details
    $portfolioSQL = "SELECT 
        i.title as project_title,
        i.total_goal,
        i.profit_percent,
        i.profit_percent_min,
        i.profit_percent_max,
        i.start_date,
        i.end_date,
        SUM(ci.invested_amount) as invested_in_project,
        SUM(ci.invested_amount * i.profit_percent / 100) as expected_profit_from_project,
        COUNT(ci.id) as investment_count,
        GROUP_CONCAT(DISTINCT ci.status ORDER BY 
            CASE ci.status
                WHEN 'rejected' THEN 1
                WHEN 'pending' THEN 2
                WHEN 'approved' THEN 3
                WHEN 'payment_partial' THEN 4
                WHEN 'payment_pending' THEN 5
                WHEN 'active' THEN 6
                WHEN 'completed' THEN 7
                ELSE 8
            END DESC
        SEPARATOR ',') as investment_statuses
    FROM client_investments ci
    JOIN investments i ON ci.investment_id = i.id
    WHERE ci.client_id = ?
    GROUP BY i.id
    ORDER BY MAX(ci.created_at) DESC";

    $portfolioStmt = $conn->prepare($portfolioSQL);
    
    if (!$portfolioStmt) {
        throw new Exception('Failed to prepare portfolio query: ' . $conn->error);
    }
    
    $portfolioStmt->bind_param("i", $client_id);
    $portfolioStmt->execute();
    
    // For portfolio, we'll use a different approach since it has multiple rows
    // Store results in an array
    $portfolioStmt->bind_result($project_title, $total_goal, $profit_percent, $profit_percent_min, $profit_percent_max, $start_date, $end_date, $invested_in_project, $expected_profit_from_project, $investment_count, $investment_statuses);
    
    $portfolioData = [];
    while ($portfolioStmt->fetch()) {
        $portfolioData[] = [
            'project_title' => $project_title,
            'total_goal' => $total_goal,
            'profit_percent' => $profit_percent,
            'profit_percent_min' => $profit_percent_min,
            'profit_percent_max' => $profit_percent_max,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'invested_in_project' => $invested_in_project,
            'expected_profit_from_project' => $expected_profit_from_project,
            'investment_count' => $investment_count,
            'investment_statuses' => $investment_statuses
        ];
    }
    $portfolioStmt->close();
    
    // Create a mock result object for compatibility with existing HTML code
    $portfolioResult = (object) [
        'num_rows' => count($portfolioData),
        'data' => $portfolioData,
        'current_index' => -1
    ];
    
    // Add a fetch_assoc method to our mock object
    $portfolioResult->fetch_assoc = function() use (&$portfolioResult) {
        $portfolioResult->current_index++;
        return isset($portfolioResult->data[$portfolioResult->current_index]) ? $portfolioResult->data[$portfolioResult->current_index] : null;
    };

    // Calculate ROI percentage
    $totalInvested = $stats['total_invested_amount'] ?: 0;
    $totalProfit = $stats['total_expected_profit'] ?: 0;
    $roiPercentage = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

} catch (Exception $e) {
    // Handle database errors gracefully
    $error_message = 'Dashboard Error: ' . $e->getMessage();
    
    // Set default values to prevent further errors
    $stats = [
        'total_projects_invested' => 0,
        'total_invested_amount' => 0,
        'total_expected_profit' => 0,
        'total_investments' => 0,
        'first_investment_date' => null,
        'last_investment_date' => null
    ];
    
    $portfolioResult = false;
    $clientInfo = ['name' => 'Unknown', 'username' => 'Unknown', 'email' => 'Unknown'];
    $totalInvested = 0;
    $totalProfit = 0;
    $roiPercentage = 0;
    
    // Log error for debugging
    if (isset($_GET['debug'])) {
        echo '<div class="alert alert-danger">Debug Error: ' . htmlspecialchars($error_message) . '</div>';
    }
}
?>

<!-- Error Display -->
<?php if (isset($error_message)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <strong>Notice:</strong> Some dashboard features may be limited due to a system issue.
                <?php if (isset($_GET['debug'])): ?>
                    <br><small><?= htmlspecialchars($error_message) ?></small>
                <?php endif; ?>
                <br><small><a href="dashboard_simple.php">Try Simple Dashboard</a> | <a href="dashboard_debug.php">Debug Information</a></small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- top tiles -->
<div class="row" style="display: inline-block;">
    <div class="tile_count">
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-briefcase"></i> Total Projects Invested</span>
            <div class="count"><?= number_format($stats['total_projects_invested']) ?></div>
            <span class="count_bottom"><i class="blue">Active Projects</i></span>
        </div>
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-dollar"></i> Total Invested Amount</span>
            <div class="count green">$<?= number_format($stats['total_invested_amount'], 0) ?></div>
            <span class="count_bottom"><i class="green">Capital Deployed</i></span>
        </div>
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-line-chart"></i> Total Expected Profit</span>
            <div class="count green">$<?= number_format($stats['total_expected_profit'], 0) ?></div>
            <span class="count_bottom"><i class="green">Projected Returns</i></span>
        </div>
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-percent"></i> Expected ROI</span>
            <div class="count"><?= number_format($roiPercentage, 1) ?>%</div>
            <span class="count_bottom"><i class="green">Return Rate</i></span>
        </div>
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-calculator"></i> Total Transactions</span>
            <div class="count"><?= number_format($stats['total_investments']) ?></div>
            <span class="count_bottom"><i class="blue">Investment Count</i></span>
        </div>
        <div class="col-md-2 col-sm-4 tile_stats_count">
            <span class="count_top"><i class="fa fa-calendar"></i> Member Since</span>
            <div class="count"><?= $stats['first_investment_date'] ? date('M Y', strtotime($stats['first_investment_date'])) : 'N/A' ?></div>
            <span class="count_bottom"><i class="green">First Investment</i></span>
        </div>
    </div>
</div>
<!-- /top tiles -->

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-pie-chart"></i> Your Investment Portfolio</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if (isset($portfolioData) && count($portfolioData) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project Title</th>
                                    <th>Project Goal</th>
                                    <th>Profit Rate</th>
                                    <th>Your Investment</th>
                                    <th>Expected Profit</th>
                                    <th>Project Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($portfolioData) && count($portfolioData) > 0): ?>
                                    <?php foreach($portfolioData as $portfolio): 
                                        // Handle profit range or fixed profit
                                        $profitPercentMin = $portfolio['profit_percent_min'] ?? $portfolio['profit_percent'];
                                        $profitPercentMax = $portfolio['profit_percent_max'] ?? $portfolio['profit_percent'];
                                        $isProfitRange = ($profitPercentMin != $profitPercentMax);
                                        
                                        if ($isProfitRange) {
                                            $profitDisplay = number_format($profitPercentMin, 1) . '% - ' . number_format($profitPercentMax, 1) . '%';
                                            $profitMin = $portfolio['invested_in_project'] * ($profitPercentMin / 100);
                                            $profitMax = $portfolio['invested_in_project'] * ($profitPercentMax / 100);
                                            $expectedProfitDisplay = '$' . number_format($profitMin, 0) . ' - $' . number_format($profitMax, 0);
                                        } else {
                                            $profitDisplay = number_format($portfolio['profit_percent'], 1) . '%';
                                            $expectedProfitDisplay = '$' . number_format($portfolio['expected_profit_from_project'], 0);
                                        }
                                        // Determine investment status based on client_investments status
                                        // If multiple investments in same project, show the most relevant status
                                        $statuses = explode(',', $portfolio['investment_statuses']);
                                        $primaryStatus = $statuses[0]; // Already ordered by priority in SQL
                                        
                                        switch($primaryStatus) {
                                          case 'pending':
                                            $statusLabel = '<span class="label label-warning">Pending Approval</span>';
                                            break;
                                          case 'approved':
                                            $statusLabel = '<span class="label label-info">Awaiting Payment</span>';
                                            break;
                                          case 'payment_partial':
                                            $statusLabel = '<span class="label label-primary">Partial Payment</span>';
                                            break;
                                          case 'payment_pending':
                                            $statusLabel = '<span class="label label-success">Fully Paid - Pending Activation</span>';
                                            break;
                                          case 'rejected':
                                            $statusLabel = '<span class="label label-danger">Rejected</span>';
                                            break;
                                          case 'active':
                                            $statusLabel = '<span class="label label-success">Active</span>';
                                            break;
                                          case 'completed':
                                            $statusLabel = '<span class="label label-default">Completed</span>';
                                            break;
                                          default:
                                            $statusLabel = '<span class="label label-default">Unknown</span>';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($portfolio['project_title']) ?></strong>
                                            </td>
                                            <td>$<?= number_format($portfolio['total_goal'], 0) ?></td>
                                            <td><span class="label label-success"><?= $profitDisplay ?></span></td>
                                            <td><strong class="text-primary">$<?= number_format($portfolio['invested_in_project'], 0) ?></strong></td>
                                            <td><strong class="text-success"><?= $expectedProfitDisplay ?></strong></td>
                                            <td>
                                                <?= date('M d, Y', strtotime($portfolio['start_date'])) ?><br>
                                                <small class="text-muted">to <?= date('M d, Y', strtotime($portfolio['end_date'])) ?></small>
                                            </td>
                                            <td><?= $statusLabel ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 50px;">
                        <i class="fa fa-briefcase fa-5x text-muted"></i>
                        <h3 style="margin-top: 20px;">No Investments Yet</h3>
                        <p class="text-muted">You haven't made any investments yet. Browse available projects to get started!</p>
                        <a href="available-projects.php" class="btn btn-primary">
                            <i class="fa fa-search"></i> Browse Available Projects
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-sm-6">
        <div class="x_panel">
            <div class="x_title">
                <h2>Quick Actions</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="dashboard-widget-content">
                    <ul class="quick-list">
                        <li><i class="fa fa-search"></i><a href="available-projects.php">Browse New Projects</a></li>
                        <li><i class="fa fa-briefcase"></i><a href="my-investments.php">View My Investments</a></li>
                        <li><i class="fa fa-history"></i><a href="investment-history.php">Investment History</a></li>
                        <li><i class="fa fa-file-text"></i><a href="documents.php">Download Documents</a></li>
                        <li><i class="fa fa-user"></i><a href="profile.php">Update Profile</a></li>
                        <li><i class="fa fa-sign-out"></i><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-sm-6">
        <div class="x_panel">
            <div class="x_title">
                <h2>Investment Summary</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="widget_summary">
                    <div class="w_left w_25">
                        <span>Total Invested</span>
                    </div>
                    <div class="w_center w_55">
                        <div class="progress">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="100" aria-valuemin="0"
                                aria-valuemax="100" style="width: 100%;">
                                <span class="sr-only">100% Complete</span>
                            </div>
                        </div>
                    </div>
                    <div class="w_right w_20">
                        <span>$<?= number_format($totalInvested, 0) ?></span>
                    </div>
                    <div class="clearfix"></div>
                </div>

                <div class="widget_summary">
                    <div class="w_left w_25">
                        <span>Expected Profit</span>
                    </div>
                    <div class="w_center w_55">
                        <div class="progress">
                            <div class="progress-bar bg-green" role="progressbar" aria-valuenow="<?= $roiPercentage ?>" aria-valuemin="0"
                                aria-valuemax="100" style="width: <?= min($roiPercentage, 100) ?>%;">
                                <span class="sr-only"><?= number_format($roiPercentage, 1) ?>% Complete</span>
                            </div>
                        </div>
                    </div>
                    <div class="w_right w_20">
                        <span>$<?= number_format($totalProfit, 0) ?></span>
                    </div>
                    <div class="clearfix"></div>
                </div>

                <div class="widget_summary">
                    <div class="w_left w_25">
                        <span>ROI Rate</span>
                    </div>
                    <div class="w_center w_55">
                        <div class="progress">
                            <div class="progress-bar bg-blue" role="progressbar" aria-valuenow="<?= $roiPercentage ?>" aria-valuemin="0"
                                aria-valuemax="50" style="width: <?= min($roiPercentage * 2, 100) ?>%;">
                                <span class="sr-only"><?= number_format($roiPercentage, 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="w_right w_20">
                        <span><?= number_format($roiPercentage, 1) ?>%</span>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</div>