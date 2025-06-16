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
    <title>Satellite Tracker - Page Not Found</title>
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet"/>
    <!-- Material Design Icons -->
    <link href="<?= Application::url('/assets/css/mdi/materialdesignicons.min.css') ?>" rel="stylesheet"/>
    <!-- CSS Files -->
    <link id="pagestyle" href="<?= Application::url('/assets/css/argon-dashboard.css?v=2.1.0') ?>" rel="stylesheet"/>
</head>

<body class="bg-gray-100">
<div class="container position-sticky z-index-sticky top-0">
    <div class="row">
        <div class="col-12">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg blur border-radius-lg top-0 z-index-3 shadow position-absolute mt-4 py-2 start-0 end-0 mx-4">
                <div class="container-fluid">
                    <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3" href="<?= Application::url('/') ?>">
                        <img src="<?= Application::url('/assets/img/logo-ct-dark.svg') ?>" width="26px" height="26px" class="navbar-brand-img h-100 me-1"
                             alt="main_logo">
                        <span class="ms-1 font-weight-bold">Satellite Tracker</span>
                    </a>
                    <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false"
                            aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon mt-2">
                          <span class="navbar-toggler-bar bar1"></span>
                          <span class="navbar-toggler-bar bar2"></span>
                          <span class="navbar-toggler-bar bar3"></span>
                        </span>
                    </button>
                    <div class="collapse navbar-collapse" id="navigation">
                        <ul class="navbar-nav mx-auto">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center me-2 active" aria-current="page"
                                   href="<?= Application::url('/') ?>">
                                    <i class="mdi mdi-home opacity-6 text-dark me-1"></i>
                                    Home
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- End Navbar -->
        </div>
    </div>
</div>
<main class="main-content mt-0">
    <section>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-7 col-md-8 d-flex flex-column mx-lg-0 mx-auto">
                        <div class="card card-plain">
                            <div class="card-header pb-0 text-center">
                                <h1 class="font-weight-bolder text-danger">404</h1>
                                <h4 class="font-weight-bolder">Page Not Found</h4>
                                <p class="mb-0">The page you are looking for does not exist.</p>
                            </div>
                            <div class="card-body text-center">
                                <a href="<?= Application::url('/') ?>" class="btn btn-primary">Go to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<!--   Core JS Files   -->
<script src="<?= Application::url('/assets/js/core/popper.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/core/bootstrap.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/plugins/perfect-scrollbar.min.js') ?>"></script>
<script src="<?= Application::url('/assets/js/plugins/smooth-scrollbar.min.js') ?>"></script>
<!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
<script src="<?= Application::url('/assets/js/argon-dashboard.min.js?v=2.1.0') ?>"></script>
</body>

</html> 