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
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter username" id="username"
                                aria-describedby="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Enter password" name="password" id="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="keep-me-logged-in">
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