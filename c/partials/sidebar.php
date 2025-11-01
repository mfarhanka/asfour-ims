<!-- c/partials/sidebar.php - Client Portal Sidebar -->
<div class="col-md-3 left_col">
  <div class="left_col scroll-view">
    <div class="navbar nav_title" style="border: 0;">
      <a href="dashboard.php" class="site_title">
        <i class="fa fa-line-chart"></i> 
        <span>Client Portal</span>
      </a>
    </div>

    <div class="clearfix"></div>

    <!-- menu profile quick info -->
    <div class="profile clearfix">
      <div class="profile_pic">
        <img src="../build/images/user.png" alt="..." class="img-circle profile_img">
      </div>
      <div class="profile_info">
        <span>Welcome,</span>
        <h2><?= isset($_SESSION['client_name']) ? htmlspecialchars($_SESSION['client_name']) : 'Client' ?></h2>
      </div>
    </div>
    <!-- /menu profile quick info -->

    <br />

    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
      <div class="menu_section">
        <h3>Investment Management</h3>
        <ul class="nav side-menu">
          <li>
            <a href="dashboard.php">
              <i class="fa fa-dashboard"></i> Dashboard
            </a>
          </li>
          <li>
            <a href="my-investments.php">
              <i class="fa fa-briefcase"></i> My Investments
            </a>
          </li>
          <li>
            <a href="available-projects.php">
              <i class="fa fa-search"></i> Available Projects
            </a>
          </li>
        </ul>
      </div>
      
      <div class="menu_section">
        <h3>Account</h3>
        <ul class="nav side-menu">
          <li>
            <a href="#" onclick="return false;">
              <i class="fa fa-user"></i> Profile <span class="fa fa-chevron-down"></span>
            </a>
            <ul class="nav child_menu">
              <li><a href="#" onclick="return false;">View Profile</a></li>
              <li><a href="#" onclick="return false;">Edit Profile</a></li>
            </ul>
          </li>
          <li>
            <a href="logout.php">
              <i class="fa fa-sign-out"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
    <!-- /sidebar menu -->

    <!-- /menu footer buttons -->
    <div class="sidebar-footer hidden-small">
      <a data-toggle="tooltip" data-placement="top" title="Settings" href="#" onclick="return false;">
        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="FullScreen" onclick="toggleFullScreen();">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="Lock" href="#" onclick="return false;">
        <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="Logout" href="logout.php">
        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
      </a>
    </div>
    <!-- /menu footer buttons -->
  </div>
</div>

<script>
function toggleFullScreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
}
</script>