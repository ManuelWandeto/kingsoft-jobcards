<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link href="../assets/img/favicon.ico" rel="icon" type="image/x-icon" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>
        Kingsoft Jobcards
    </title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
        name='viewport' />
    <!--     Fonts and icons     -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <!-- CSS Files -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../assets/css/now-ui-dashboard.css?v=1.5.0" rel="stylesheet" />
    <link href="../assets/kingsoft/kingsoft-jobcards.css" rel="stylesheet" />
    <link href="../assets/kingsoft/color-codes.css" rel="stylesheet" />
    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link href="../assets/demo/demo.css" rel="stylesheet" />
    <!-- <script src="../assets/js/app.js" defer></script> -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@caneara/iodine@8.3.0/dist/iodine.min.umd.js"></script>

</head>

<body class="login" style="height: 100vh; width: 100vw;">
    <div class="content">
        <div class="illustration mb-4">
            <h1>K. JOBCARDS</h1>
        </div>
        <?php
            if(isset($_GET["error"])) {
                // display login errors
                if($_GET["error"] == "user not found") {
                    echo 
                        "<div class='alert alert-warning' style='position: absolute; top: 32px; right: 32px;' role='alert'>
                            No user exists by that username or email
                        </div>";
                }
                if($_GET["error"] == "invalid password") {
                    echo 
                        "<div class='alert alert-warning' style='position: absolute; top: 32px; right: 32px;' role='alert'>
                            The password you provided is invalid
                        </div>";
                }
                // display internal server error
                if($_GET["error"] == "internal error") {
                    echo 
                        "<div class='alert alert-warning' style='position: absolute; top: 32px; right: 32px;' role='alert'>
                            Internal error, please try again
                        </div>";
                }
            }
        ?>
        <form x-data="formData()" action="../controllers/login.php" method="POST">
            <div class="mb-4">
                <div class="icon-input">
                    <div class="icon-input icon">
                        <i class="now-ui-icons users_single-02"></i>
                    </div>
                    <input id="username" required type="text" name="username" placeholder="Enter username or email"
                        aria-describedby="user-icon-addon" x-model="fields.username.value"
                        @blur="validateField(fields.username)" />
                </div>
                <span class="text-warning mt-1" x-text="fields.username.error" x-cloak></span>
            </div>
            <div class="mb-4">
                <div class="icon-input">
                    <div class="icon-input icon">
                        <i id="password-icon-adon" class="now-ui-icons objects_key-25"></i>
                    </div>
                    <input id="current-password" required type="password" name="password" placeholder="Enter password"
                        aria-describedby="password-icon-addon" x-model="fields.password.value"
                        @blur="validateField(fields.password)" />
                </div>
                <span class="text-warning mt-1" x-text="fields.password.error" x-cloak></span>
            </div>
            <button :disabled="isFormInvalid" type="submit" name="submit" class="btn btn-light">
                LOGIN
            </button>
            <span>Forgot Password?</span>
        </form>
    </div>
    <script>
        function formData() {
            return {
                fields: {
                    username: {
                        value: null, error: null,
                        rules: ["required", "maxLength:80", "minLength:3"]
                    },
                    password: {
                        value: null, error: null,
                        rules: ["required", "minLength:8"]
                    }
                },
                isFormInvalid: true,
                validateField(field) {
                    let res = Iodine.assert(field.value, field.rules);
                    field.error = res.valid ? null : res.error;
                    this.isFormValid();
                },
                isFormValid() {
                    this.isFormInvalid = Object.values(this.fields).some(
                        (field) => field.error
                    );
                    return !this.isFormInvalid;
                }
            }
        }
    </script>
</body>