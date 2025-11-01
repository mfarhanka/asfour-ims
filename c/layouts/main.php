<?php 
/* c/layouts/main.php */

// Error handling for debugging
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    session_start();
    
    // Check config file exists
    $config_path = __DIR__ . '/../../config.php';
    if (!file_exists($config_path)) {
        die('Configuration file not found. Please check your installation.');
    }
    
    require_once $config_path; 

    // Check if client is logged in
    if (!isset($_SESSION['client_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Check if database connection exists
    if (!isset($conn) || !($conn instanceof mysqli)) {
        die('Database connection not available. Please contact administrator.');
    }
    
} catch (Exception $e) {
    if (isset($_GET['debug'])) {
        die('Layout Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    } else {
        die('System temporarily unavailable. Please try again later.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php 
$head_file = __DIR__ . '/../partials/head.php';
if (file_exists($head_file)) {
    include $head_file;
} else {
    echo '<head><title>Client Portal</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>';
}
?>
<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php 
      $sidebar_file = __DIR__ . '/../partials/sidebar.php';
      if (file_exists($sidebar_file)) {
          include $sidebar_file;
      } else {
          echo '<div class="col-md-3 left_col"><div class="left_col scroll-view"><div class="navbar nav_title"><a href="dashboard.php">Client Portal</a></div></div></div>';
      }
      ?>

      <!-- top navigation -->
      <?php 
      $header_file = __DIR__ . '/../partials/header.php';
      if (file_exists($header_file)) {
          include $header_file;
      } else {
          echo '<div class="top_nav"><div class="nav_menu"><nav class="nav navbar-nav"><ul class="navbar-right"><li><a href="logout.php">Logout</a></li></ul></nav></div></div>';
      }
      ?>
      <!-- /top navigation -->

      <!-- page content -->
      <div class="right_col" role="main">
        <?php 
        // Display success messages
        if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
            <strong>Success!</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php 
        // Display error messages
        if (isset($_SESSION['error_messages'])): ?>
          <div class="alert alert-danger alert-dismissible fade in" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
            <strong>Error!</strong>
            <ul style="margin-bottom: 0; margin-top: 10px;">
              <?php foreach ($_SESSION['error_messages'] as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php unset($_SESSION['error_messages']); ?>
        <?php endif; ?>
        
        <?php 
        if (isset($view) && file_exists($view)) {
            include $view;
        } else {
            echo '<div class="alert alert-danger">Page content not found: ' . ($view ?? 'undefined') . '</div>';
            echo '<p><a href="dashboard_simple.php">Try Simple Dashboard</a> | <a href="dashboard_debug.php">Debug Information</a></p>';
        }
        ?>
      </div>
      <!-- /page content -->

      <!-- footer content -->
      <?php 
      $footer_file = __DIR__ . '/../partials/footer.php';
      if (file_exists($footer_file)) {
          include $footer_file;
      } else {
          echo '<footer><div class="pull-right">Client Portal &copy; ' . date('Y') . '</div><div class="clearfix"></div></footer>';
      }
      ?>
      <!-- /footer content -->
    </div>
  </div>

  <!-- jQuery -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../vendors/nprogress/nprogress.js"></script>
    <!-- Chart.js -->
    <script src="../vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- gauge.js -->
    <script src="../vendors/gauge.js/dist/gauge.min.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="../vendors/iCheck/icheck.min.js"></script>
    <!-- Skycons -->
    <script src="../vendors/skycons/skycons.js"></script>
    <!-- Flot -->
    <script src="../vendors/Flot/jquery.flot.js"></script>
    <script src="../vendors/Flot/jquery.flot.pie.js"></script>
    <script src="../vendors/Flot/jquery.flot.time.js"></script>
    <script src="../vendors/Flot/jquery.flot.stack.js"></script>
    <script src="../vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
    <script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
    <script src="../vendors/flot.curvedlines/curvedLines.js"></script>
    <!-- DateJS -->
    <script src="../vendors/DateJS/build/date.js"></script>
    <!-- JQVMap -->
    <script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
    <script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
    <script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="../vendors/moment/min/moment.min.js"></script>
    <script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="../build/js/custom.min.js"></script>
</body>
</html>