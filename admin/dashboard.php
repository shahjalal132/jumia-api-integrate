<?php
session_start();
require_once '../config.php';

if ( !isset( $_SESSION['login'] ) ) {
    header( 'location: ../index.php' );
    exit();
}

$error   = '';
$success = '';

// Handle form submission for API credentials
if ( isset( $_POST['save-api-credentials'] ) ) {
    $clientId     = $_POST['client_id'];
    $refreshToken = $_POST['refresh_token'];

    if ( !empty( $clientId ) && !empty( $refreshToken ) ) {
        try {
            $sql  = "INSERT INTO api_credentials (client_id, refresh_token) VALUES (:client_id, :refresh_token)
                    ON DUPLICATE KEY UPDATE refresh_token = :refresh_token";
            $stmt = $conn->prepare( $sql );
            $stmt->bindParam( ':client_id', $clientId );
            $stmt->bindParam( ':refresh_token', $refreshToken );
            $stmt->execute();
            $success = 'API credentials saved successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving API credentials: ' . $e->getMessage();
        }
    } else {
        $error = 'Both Client ID and Refresh Token are required.';
    }
}

// Handle form submission for controls
if ( isset( $_POST['save-controls'] ) ) {
    $stockUpdate = $_POST['stock-update'];
    $priceUpdate = $_POST['price-update'];

    if ( !empty( $stockUpdate ) && !empty( $priceUpdate ) ) {
        try {
            $conn->beginTransaction();

            // Insert or update stock-update
            $sql  = "INSERT INTO controls (control_key, control_value) VALUES ('stock_update', :stock_update)
                    ON DUPLICATE KEY UPDATE control_value = :stock_update";
            $stmt = $conn->prepare( $sql );
            $stmt->bindParam( ':stock_update', $stockUpdate );
            $stmt->execute();

            // Insert or update price-update
            $sql  = "INSERT INTO controls (control_key, control_value) VALUES ('price_update', :price_update)
                    ON DUPLICATE KEY UPDATE control_value = :price_update";
            $stmt = $conn->prepare( $sql );
            $stmt->bindParam( ':price_update', $priceUpdate );
            $stmt->execute();

            $conn->commit();
            $success = 'Control settings saved successfully.';
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Error saving control settings: ' . $e->getMessage();
        }
    } else {
        $error = 'Both Stock Update and Price Update are required.';
    }
}

?>

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
                            <h2 class="text-center poppins-semibold text-primary mb-3">API Credentials</h2>
                            <div id="api-credentials-form">
                                <form method="POST">
                                    <?php if ( $error ) : ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <?php if ( $success ) : ?>
                                        <div class="alert alert-success"><?php echo $success; ?></div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="api-client-id" class="form-label">Client ID</label>
                                        <input type="text" name="client_id" class="form-control bg-transparent"
                                            placeholder="Enter Client ID" id="api-client-id" aria-describedby="clientID"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="refresh-token" class="form-label">Refresh Token</label>
                                        <input type="text" name="refresh_token" class="form-control bg-transparent"
                                            placeholder="Enter Refresh Token" id="refresh-token" required>
                                    </div>
                                    <button type="submit" name="save-api-credentials"
                                        class="btn btn-primary">Save</button>
                                </form>
                            </div>
                        </div>
                        <div id="controls">
                            <h2 class="text-center poppins-semibold text-primary mb-3">Controls</h2>
                            <div id="controls-form">
                                <form method="POST">
                                    <?php if ( $error ) : ?>
                                        <div class="alert alert-danger"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <?php if ( $success ) : ?>
                                        <div class="alert alert-success"><?php echo $success; ?></div>
                                    <?php endif; ?>
                                    <div class="row flex-column">
                                        <div class="col-sm-6">
                                            <div class="mb-3 row">
                                                <div class="col-sm-6">
                                                    <div>
                                                        <label for="stock-update" class="form-label">Stock
                                                            Update:</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="jumia-radio">
                                                        <input class="form-check-input" type="radio"
                                                            value="stock-enable" name="stock-update" id="stock-enable"
                                                            required>
                                                        <label class="form-check-label"
                                                            for="stock-enable">Enable</label>
                                                        <input class="form-check-input ms-4" type="radio"
                                                            value="stock-disable" name="stock-update"
                                                            id="stock-disable">
                                                        <label class="form-check-label"
                                                            for="stock-disable">Disable</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="mb-3 row">
                                                <div class="col-sm-6">
                                                    <div>
                                                        <label for="price-update" class="form-label">Price
                                                            Update:</label>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="jumia-radio">
                                                        <input class="form-check-input" type="radio"
                                                            value="price-enable" name="price-update" id="price-enable"
                                                            required>
                                                        <label class="form-check-label"
                                                            for="price-enable">Enable</label>
                                                        <input class="form-check-input ms-4" type="radio"
                                                            value="price-disable" name="price-update"
                                                            id="price-disable">
                                                        <label class="form-check-label"
                                                            for="price-disable">Disable</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="save-controls" class="btn btn-primary">Save</button>
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