<?php
session_start();
require_once 'config.php';

if ( isset( $_SESSION['login'] ) ) {
    header( 'Location: admin/dashboard.php' );
    exit();
}

// Check if the login form is submitted
if ( isset( $_POST['login'] ) ) {

    // Get username and password from form
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql  = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare( $sql );
    $stmt->bindParam( ':username', $username, PDO::PARAM_STR );
    $stmt->execute();
    $dbUser = $stmt->fetch( PDO::FETCH_ASSOC );

    $error = '';

    if ( $dbUser && $username === $dbUser['username'] && md5( $password ) === $dbUser['password'] ) {
        $_SESSION['login']    = 'successful';
        $_SESSION['username'] = $username;

        // Check if "Keep me logged in" is checked
        if ( isset( $_POST['keep_me_logged_in'] ) ) {
            // Set a cookie for 30 days
            setcookie( 'username', $username, time() + ( 30 * 24 * 60 * 60 ), "/" );
            setcookie( 'password', md5( $password ), time() + ( 30 * 24 * 60 * 60 ), "/" );
        }

        header( 'Location: admin/dashboard.php' );
        exit();
    } else {
        $error = 'Invalid username or password';
    }

} elseif ( isset( $_COOKIE['username'] ) && isset( $_COOKIE['password'] ) ) {
    // Check if login cookies are present
    $username = $_COOKIE['username'];
    $password = $_COOKIE['password'];

    $sql  = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare( $sql );
    $stmt->bindParam( ':username', $username, PDO::PARAM_STR );
    $stmt->execute();
    $dbUser = $stmt->fetch( PDO::FETCH_ASSOC );

    if ( $dbUser && $username === $dbUser['username'] && $password === $dbUser['password'] ) {
        $_SESSION['login']    = 'successful';
        $_SESSION['username'] = $username;
        header( 'Location: admin/dashboard.php' );
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div id="login-form">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <form method="POST">
                        <h2 class="text-center poppins-semibold">Login</h2>
                        <?php if ( isset( $error ) ) : ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter username"
                                id="username" aria-describedby="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Enter password" name="password"
                                id="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="keep_me_logged_in"
                                id="keep-me-logged-in">
                            <label class="form-check-label" for="keep-me-logged-in">Keep me logged in</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>