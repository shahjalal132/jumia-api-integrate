<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>


    <div id="dashboard">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <!-- overlay -->
                    <div id="sidebar-overlay" class="overlay w-100 vh-100 position-fixed d-none"></div>

                    <!-- sidebar -->
                    <div class="col-md-3 col-lg-2 position-fixed shadow-sm sidebar" id="sidebar">

                        <div class="list-group rounded-0">
                            <a href="#"
                                class="list-group-item list-group-item-action active border-0 d-flex align-items-center">
                                <i class="bi bi-app me-2"></i>
                                <span class="ml-2">Application</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action border-0 align-items-center">
                                <i class="bi bi-controller me-2"></i>
                                <span class="ml-2">Controls</span>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-9 col-lg-10 ml-md-auto px-0 ms-md-auto">

                        <!-- main content -->
                        <main class="p-4 min-vh-100">
                            <section class="row">
                                <div class="col-md-6 col-lg-4">
                                    <!-- card -->
                                    <article class="p-4 rounded shadow-sm border-left
       mb-4">
                                        <a href="#" class="d-flex align-items-center">
                                            <span class="bi bi-box h5"></span>
                                            <h5 class="ml-2">Products</h5>
                                        </a>
                                    </article>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <article class="p-4 rounded shadow-sm border-left mb-4">
                                        <a href="#" class="d-flex align-items-center">
                                            <span class="bi bi-person h5"></span>
                                            <h5 class="ml-2">Customers</h5>
                                        </a>
                                    </article>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <article class="p-4 rounded shadow-sm border-left mb-4">
                                        <a href="#" class="d-flex align-items-center">
                                            <span class="bi bi-person-check h5"></span>
                                            <h5 class="ml-2">Sellers</h5>
                                        </a>
                                    </article>
                                </div>
                            </section>

                            <div class="jumbotron jumbotron-fluid rounded bg-white border-0 shadow-sm border-left px-4">
                                <div class="container">
                                    <h1 class="display-4 mb-2 text-primary">Simple</h1>
                                    <p class="lead text-muted">Simple Admin Dashboard with Bootstrap.</p>
                                </div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>

</html>