<?php

use app\core\Application;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="<?= Application::url('/assets/img/apple-icon.png') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Application::url('/assets/img/logo-ct-dark.svg') ?>">
    <title>Satellite Tracker</title>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet"/>
    
    <!-- Material Design Icons - Local files -->
    <link href="<?= Application::url('/assets/css/mdi/materialdesignicons.min.css') ?>" rel="stylesheet"/>
    
    <!-- Material Design Icons already loaded -->
    <!-- CSS Files -->
    <link id="pagestyle" href="<?= Application::url('/assets/css/argon-dashboard.css?v=2.1.0') ?>" rel="stylesheet"/>

    <!-- Update to use local OpenLayers CSS -->
    <link rel="stylesheet" href="<?= Application::url('/assets/libs/openlayers/ol.css') ?>">
    
    <link rel="stylesheet" href="<?= Application::url('/assets/js/plugins/toastr/toastr.min.css') ?>">
    <script src="<?= Application::url('/assets/libs/jquery/dist/jquery.min.js') ?>"></script>
    <script src="<?= Application::url('/assets/js/plugins/toastr/toastr.min.js') ?>"></script>
    <script src="<?= Application::url('/assets/js/plugins/toastr/toastr-options.js') ?>"></script>
    <script src="<?= Application::url('/assets/js/plugins/chartjs.min.js') ?>"></script>
    
    <!-- Update to use local OpenLayers JS -->
    <script src="<?= Application::url('/assets/libs/openlayers/ol.js') ?>"></script>
    
    <!-- All icons are now directly using MDI -->
</head>

<body class="g-sidenav-show bg-gray-100">
<div class="min-height-300 bg-primary position-absolute w-100"></div>
<aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4 "
       id="sidenav-main">
    <div class="sidenav-header">
        <i class="mdi mdi-close-thick p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
           aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="<?= Application::url('/') ?>">
            <img src="<?= Application::url('/assets/img/logo-ct-dark.svg') ?>" width="26px" height="26px" class="navbar-brand-img h-100"
                 alt="main_logo">
            <span class="ms-1 font-weight-bold">Satellite Tracker</span>
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-home text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/satellites') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-satellite-variant text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Satellites</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/satelliteStatistics') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-chart-arc text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Satellite Statistics</span>
                </a>
            </li>
            
            <?php if (Application::$app->session->isInRole('Administrator')): ?>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/importSatellites') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-cloud-upload text-warning text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Import Satellites</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/importStatistics') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-chart-box text-danger text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Import Statistics</span>
                </a>
            </li>
            <!-- Position Statistics removed as functionality is not implemented -->
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/filterPositions') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-map-marker text-warning text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Satellites Near Me</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (Application::$app->session->get('user')): ?>
            <?php if (Application::$app->session->isInRole('Administrator')): ?>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/accounts') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-account-group text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Manage Accounts</span>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/account') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-account-circle text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Your Account</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/processLogout') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-exit-run text-dark text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Log out</span>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link " href="<?= Application::url('/login') ?>">
                    <div class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-key-variant text-dark text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Login</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</aside>
<main class="main-content position-relative border-radius-lg ">
    <div class="container-fluid py-4">
        {{ RENDER_SECTION }}
    </div>
</main>

<!--   Core JS Files   -->
<script src="<?= Application::url('/assets/js/core/popper.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/core/bootstrap.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/plugins/perfect-scrollbar.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/plugins/smooth-scrollbar.min.js') ?>"></script>
<script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
        var options = {
            damping: '0.5'
        }
        Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
</script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
<!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
<script src="<?= Application::url('/assets/js/argon-dashboard.min.js?v=2.1.0') ?>"></script>



</body>

<?php
Application::$app->session->showSuccessNotification();
Application::$app->session->showErrorNotification();
?>

</html> 