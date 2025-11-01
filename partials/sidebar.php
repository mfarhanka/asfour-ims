<?php /* partials/sidebar.php */ ?>
<div class="col-md-3 left_col">
  <div class="left_col scroll-view">
    <div class="navbar nav_title" style="border: 0;">
      <a href="index.php" class="site_title">
        <i class="fa fa-paw"></i> <span>AIMS</span>
      </a>
    </div>
    <div class="clearfix"></div>

    <!-- profile quick info (optional) -->
    <div class="profile clearfix">
      <div class="profile_pic">
        <img src="images/img.jpg" alt="..." class="img-circle profile_img">
      </div>
      <div class="profile_info">
        <span>Welcome,</span>
        <h2>Farhan</h2>
      </div>
    </div>
    <br />

    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
      <div class="menu_section">
        <h3>General</h3>
        <ul class="nav side-menu">
          <li><a href="index.php?p=dashboard"><i class="fa fa-home"></i> Dashboard</a></li>
          <!-- Rentals menu removed for investment context -->
          <li><a href="index.php?p=client-list"><i class="fa fa-users"></i> Client List</a></li>
          <li><a href="index.php?p=investment-list"><i class="fa fa-line-chart"></i> Investment Management</a></li>
          <li><a href="index.php?p=client-investment"><i class="fa fa-money"></i> Client Investments</a></li>
          <li><a href="index.php?p=pending-investments"><i class="fa fa-clock-o"></i> Pending Approvals <span class="badge bg-orange">New</span></a></li>
          <li><a href="index.php?p=verify-payments"><i class="fa fa-check-square-o"></i> Verify Payments <span class="badge bg-green">New</span></a></li>
          <li><a href="index.php?p=admin-list"><i class="fa fa-user-secret"></i> Admin Management</a></li>
          <!-- Car-related menu removed for investment context -->
          <!-- add more as needed -->
        </ul>
      </div>
    </div>
    <!-- /sidebar menu -->

    <!-- sidebar footer buttons (optional) -->
    <div class="sidebar-footer hidden-small">
      <a data-toggle="tooltip" data-placement="top" title="Settings">
        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="FullScreen">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="Lock">
        <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="Logout" href="logout.php">
        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
      </a>
    </div>
  </div>
</div>
