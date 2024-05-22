<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>


    <div id="dashboard">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center poppins-bold text-primary">Dashboard</h2>
                    <div id="tabs" class="d-flex justify-content-between align-items-start gap-5 mt-5">
                        <ul>
                            <li><a href="#application" class="poppins-medium">Application</a></li>
                            <li><a href="#controls" class="poppins-medium">Controls</a></li>
                        </ul>
                        <div id="application">
                            <h2 class="text-center poppins-semibold text-primary mb-3">Api Credentials</h2>
                            <div id="api-credentials-form">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="api-client-id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control bg-transparent "
                                            placeholder="Enter Client ID" id="api-client-id"
                                            aria-describedby="clientID">
                                    </div>
                                    <div class="mb-3">
                                        <label for="refresh-token" class="form-label">Refresh Token</label>
                                        <input type="text" class="form-control bg-transparent "
                                            placeholder="Enter Refresh Token" id="refresh-token">
                                    </div>
                                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                                </form>
                            </div>
                        </div>
                        <div id="controls">
                            <h2 class="text-center poppins-semibold text-primary mb-3">Controls</h2>
                            <div id="controls-form">
                                <form method="POST">
                                    <div class="row flex-column ">
                                        <div class="col-sm-6">
                                            <div class="mb-3 row">
                                                <div class="col-sm-6">
                                                    <div>
                                                        <label for="api-client-id" class="form-label">Stock
                                                            Update:</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="jumia-radio">
                                                        <input class="form-check-input" type="radio" value="stock-enable"
                                                            name="stock-update" id="stock-enable">
                                                        <label class="form-check-label" for="stock-enable">
                                                            Enable
                                                        </label>
                                                        <input class="form-check-input ms-4" type="radio"
                                                            value="stock-disable" name="stock-update" id="stock-disable">
                                                        <label class="form-check-label" for="stock-disable">
                                                            Disable
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="mb-3 row">
                                                <div class="col-sm-6">
                                                    <div>
                                                        <label class="form-label">Price Update:</label>
                                                    </div>
                                                </div>

                                                <div class="col-sm-6">
                                                    <div class="jumia-radio">

                                                        <input class="form-check-input" type="radio" value="price-enable"
                                                            name="price-update" id="price-enable">
                                                        <label class="form-check-label" for="price-enable">
                                                            Enable
                                                        </label>

                                                        <input class="form-check-input ms-4" type="radio"
                                                            value="price-disable" name="price-update" id="price-disable">
                                                        <label class="form-check-label" for="price-disable">
                                                            Disable
                                                        </label>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"></script>
    <script src="../assets/js/app.js"></script>
</body>

</html>