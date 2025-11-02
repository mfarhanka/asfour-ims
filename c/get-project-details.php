<?php
require_once __DIR__ . '/../config.php';

// Get project ID from query parameter
$projectId = intval($_GET['project_id'] ?? 0);

if ($projectId <= 0) {
    echo '<div class="alert alert-danger">Invalid project ID</div>';
    exit;
}

// Get project details with funding information
$projectSQL = "SELECT i.*, 
                      COALESCE(SUM(ci.invested_amount), 0) as total_invested,
                      COUNT(DISTINCT ci.client_id) as total_investors
               FROM investments i 
               LEFT JOIN client_investments ci ON i.id = ci.investment_id 
               WHERE i.id = ?
               GROUP BY i.id";

$stmt = $conn->prepare($projectSQL);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    echo '<div class="alert alert-danger">Project not found</div>';
    exit;
}

$project = $result->fetch_assoc();
$stmt->close();

// Calculate progress and status
$fundingProgress = ($project['total_invested'] / $project['total_goal']) * 100;
$remainingAmount = $project['total_goal'] - $project['total_invested'];

// Handle profit range or fixed profit
$profitPercentMin = $project['profit_percent_min'] ?? $project['profit_percent'];
$profitPercentMax = $project['profit_percent_max'] ?? $project['profit_percent'];
$isProfitRange = ($profitPercentMin != $profitPercentMax);

if ($isProfitRange) {
    $profitDisplay = number_format($profitPercentMin, 2) . '% - ' . number_format($profitPercentMax, 2) . '%';
    $expectedProfitMin = ($project['total_goal'] * $profitPercentMin) / 100;
    $expectedProfitMax = ($project['total_goal'] * $profitPercentMax) / 100;
    $currentProfitMin = ($project['total_invested'] * $profitPercentMin) / 100;
    $currentProfitMax = ($project['total_invested'] * $profitPercentMax) / 100;
} else {
    $profitDisplay = number_format($project['profit_percent'], 2) . '%';
    $expectedProfit = ($project['total_goal'] * $project['profit_percent']) / 100;
    $currentProfit = ($project['total_invested'] * $project['profit_percent']) / 100;
}

// Calculate time-based information
$startDate = new DateTime($project['start_date']);
$endDate = new DateTime($project['end_date']);
$currentDate = new DateTime();
$totalDuration = $startDate->diff($endDate)->days;

if ($currentDate > $endDate) {
    $timeStatus = 'Ended';
    $timeStatusClass = 'default';
    $daysRemaining = 0;
} elseif ($currentDate < $startDate) {
    $timeStatus = 'Upcoming';
    $timeStatusClass = 'warning';
    $daysRemaining = $currentDate->diff($endDate)->days;
} else {
    $timeStatus = 'Active';
    $timeStatusClass = 'info';
    $daysRemaining = $currentDate->diff($endDate)->days;
}

?>

<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h6 class="panel-title"><i class="fa fa-info-circle"></i> Basic Information</h6>
            </div>
            <div class="panel-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Project Title:</strong></td>
                        <td><?= htmlspecialchars($project['title']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><span class="label label-<?= $timeStatusClass ?>"><?= $timeStatus ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Start Date:</strong></td>
                        <td><?= date('M d, Y', strtotime($project['start_date'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong>End Date:</strong></td>
                        <td><?= date('M d, Y', strtotime($project['end_date'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Duration:</strong></td>
                        <td><?= $totalDuration ?> days</td>
                    </tr>
                    <?php if ($timeStatus != 'Ended'): ?>
                    <tr>
                        <td><strong>Days Remaining:</strong></td>
                        <td><?= $daysRemaining ?> days</td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Information -->
    <div class="col-md-6">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h6 class="panel-title"><i class="fa fa-dollar"></i> Financial Overview</h6>
            </div>
            <div class="panel-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Target Goal:</strong></td>
                        <td>$<?= number_format($project['total_goal'], 2) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Amount Raised:</strong></td>
                        <td>$<?= number_format($project['total_invested'], 2) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Remaining:</strong></td>
                        <td>$<?= number_format($remainingAmount, 2) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Progress:</strong></td>
                        <td><?= number_format($fundingProgress, 1) ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Total Investors:</strong></td>
                        <td><?= $project['total_investors'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Profit Rate:</strong></td>
                        <td><?= $profitDisplay ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h6 class="panel-title"><i class="fa fa-chart-bar"></i> Funding Progress</h6>
            </div>
            <div class="panel-body">
                <div class="progress" style="height: 25px;">
                    <?php 
                    $progressColor = $fundingProgress >= 100 ? 'success' : ($fundingProgress >= 75 ? 'info' : ($fundingProgress >= 50 ? 'warning' : 'danger'));
                    ?>
                    <div class="progress-bar progress-bar-<?= $progressColor ?>" role="progressbar" 
                         style="width: <?= min($fundingProgress, 100) ?>%;" 
                         aria-valuenow="<?= $fundingProgress ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= number_format($fundingProgress, 1) ?>%
                    </div>
                </div>
                <p class="text-center" style="margin-top: 10px;">
                    <strong>$<?= number_format($project['total_invested'], 2) ?></strong> of 
                    <strong>$<?= number_format($project['total_goal'], 2) ?></strong> raised
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Profit Calculations -->
<div class="row">
    <!-- Expected Profit (If Fully Funded) -->
    <div class="col-md-6">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h6 class="panel-title"><i class="fa fa-calculator"></i> Expected Profit (If Fully Funded)</h6>
            </div>
            <div class="panel-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Total Investment Goal:</strong></td>
                        <td>$<?= number_format($project['total_goal'], 2) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Profit Rate:</strong></td>
                        <td><?= $profitDisplay ?></td>
                    </tr>
                    <tr>
                        <td><strong>Expected Total Profit:</strong></td>
                        <td class="text-success">
                            <strong>
                                <?php if ($isProfitRange): ?>
                                    $<?= number_format($expectedProfitMin, 2) ?> - $<?= number_format($expectedProfitMax, 2) ?>
                                <?php else: ?>
                                    $<?= number_format($expectedProfit, 2) ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Total Return:</strong></td>
                        <td class="text-primary">
                            <strong>
                                <?php if ($isProfitRange): ?>
                                    $<?= number_format($project['total_goal'] + $expectedProfitMin, 2) ?> - $<?= number_format($project['total_goal'] + $expectedProfitMax, 2) ?>
                                <?php else: ?>
                                    $<?= number_format($project['total_goal'] + $expectedProfit, 2) ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Current Expected Profit -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6 class="panel-title"><i class="fa fa-money"></i> Current Expected Profit</h6>
            </div>
            <div class="panel-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Current Investment:</strong></td>
                        <td>$<?= number_format($project['total_invested'], 2) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Profit Rate:</strong></td>
                        <td><?= $profitDisplay ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current Expected Profit:</strong></td>
                        <td class="text-success">
                            <strong>
                                <?php if ($isProfitRange): ?>
                                    $<?= number_format($currentProfitMin, 2) ?> - $<?= number_format($currentProfitMax, 2) ?>
                                <?php else: ?>
                                    $<?= number_format($currentProfit, 2) ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Current Total Return:</strong></td>
                        <td class="text-primary">
                            <strong>
                                <?php if ($isProfitRange): ?>
                                    $<?= number_format($project['total_invested'] + $currentProfitMin, 2) ?> - $<?= number_format($project['total_invested'] + $currentProfitMax, 2) ?>
                                <?php else: ?>
                                    $<?= number_format($project['total_invested'] + $currentProfit, 2) ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
