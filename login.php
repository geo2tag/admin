<?php
/*

UserFrosting Version: 0.2.2
By Alex Weissman
Copyright (c) 2014

Based on the UserCake user management system, v2.0.2.
Copyright (c) 2009-2012

UserFrosting, like UserCake, is 100% free and open-source.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the 'Software'), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/
require_once("models/config.php");

set_error_handler('logAllErrors');

// Public page

setReferralPage(getAbsoluteDocumentPath(__FILE__));

//Forward the user to their default page if he/she is already logged in
if (isUserLoggedIn()) {
    addAlert("warning", "You're already logged in!");
    header("Location: account");
    exit();
}
global $email_login;

if ($email_login == 1) {
    $user_email_placeholder = 'Username or Email';
} else {
    $user_email_placeholder = 'Username';
}
//Social network login
if (isset($_REQUEST["provider"])) {

    $errors = array();

    // the selected provider
    $provider_name = $_REQUEST["provider"];

    // $config = '/hybridauth/config.php';
    $config = dirname(__FILE__) . '/hybridauth/config.php';
    require_once("hybridauth/Hybrid/Auth.php");
    try {
        // initialize Hybrid_Auth class with the config file
        $hybridauth = new Hybrid_Auth($config);

        // try to authenticate with the selected provider
        $adapter = $hybridauth->authenticate($provider_name);

        // then grab the user profile
        $user_profile = $adapter->getUserProfile();

        //check to see if the user's email address already exists in our database
        $existsVar = !emailExists($user_profile->email);

        if ($existsVar) {
            //if the email doesn't exist in the database we first register the user

            $OAuth_provider_name = $provider_name;
            $OAuth_provider_user_id = $user_profile->identifier;
            $user_name = $user_profile->firstName;
            $display_name = $user_profile->firstName;
            $email = $user_profile->email;
            $title = $new_user_title;
            //Construct a unique activation token
            $activation_token = generateActivationToken();
            $active = 1;
            // generate a random password for the user, the user never needs to use this password since they are logging in via OAuth
            $password_temp = md5(str_shuffle("0123456789abcdefghijklmnoABCDEFGHIJ"));
            //We nevertheless hash the password to prevent an attacker from guessing it
            $password = passwordHashUF($password_temp);
            //Accounts created by using social networks are enabled by default
            $enabled = 1;

            try {
                global $db_table_prefix;

                $db = pdoConnect();

                $query = "INSERT INTO " . $db_table_prefix . "users (
            user_name,
            display_name,
            password,
            email,
            activation_token,
            last_activation_request,
            lost_password_request,
            lost_password_timestamp,
            active,
            title,
            sign_up_stamp,
            last_sign_in_stamp,
            enabled,
            hybridauth_provider_name,
            hybridauth_provider_uid
            )
            VALUES (
            :user_name,
            :display_name,
            :password,
            :email,
            :activation_token,
            '" . time() . "',
            '0',
            '" . time() . "',
            :active,
            :title,
            '" . time() . "',
            '0',
            :enabled,
            :hybridauth_provider_name,
            :hybridauth_provider_uid
            )";

                $sqlVars = array(
                    ':user_name' => $user_name,
                    ':display_name' => $display_name,
                    ':title' => $title,
                    ':password' => $password,
                    ':email' => $email,
                    ':active' => $active,
                    ':activation_token' => $activation_token,
                    ':enabled' => $enabled,
                    ':hybridauth_provider_name' => $OAuth_provider_name,
                    ':hybridauth_provider_uid' => $OAuth_provider_user_id
                );

                $stmt = $db->prepare($query);

                if (!$stmt->execute($sqlVars)) {
                    addAlert("danger", "Oops, looks like our database encountered an error.");
                    apiReturnError(false, SITE_ROOT . "login.php");
                }

                $inserted_id = $db->lastInsertId();

                $stmt = null;

            } catch (PDOException $e) {
                addAlert("danger", "Oops, looks like our database encountered an error.");
                error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
                apiReturnError(false, SITE_ROOT . "login.php");
            } catch (ErrorException $e) {
                addAlert("danger", "Oops, looks like our server might have goofed.  If you're an admin, please check the PHP error logs.");
                apiReturnError(false, SITE_ROOT . "login.php");
            }

            //The new user's id
            $new_user_id = $inserted_id;

            //Add user to the default group
            if (dbAddUserToDefaultGroups($new_user_id)) {
            } else {
                apiReturnError(false, SITE_ROOT . "login.php");
            }
        }
        //Fetch the user details
        $userdetails = fetchUserAuthByEmail($user_profile->email);

        // See if user's account is enabled
        if ($userdetails["enabled"] == 0) {
            $errors[] = lang("ACCOUNT_DISABLED");
        } else {
            //the user's account is enabled, we can now log them in

            //Construct a new logged in user object
            //Transfer some db data to the session object
            $loggedInUser = new loggedInUser();
            $loggedInUser->email = $userdetails["email"];
            $loggedInUser->user_id = $userdetails["id"];
            $loggedInUser->hash_pw = $userdetails["password"];
            $loggedInUser->title = $userdetails["title"];
            $loggedInUser->displayname = $userdetails["display_name"];
            $loggedInUser->username = $userdetails["user_name"];
            $loggedInUser->alerts = array();

            //Update last sign in
            $loggedInUser->updateLastSignIn();
            // Create the user's CSRF token
            $loggedInUser->csrf_token(true);

            $_SESSION["userCakeUser"] = $loggedInUser;

            $successes = array();
            $successes[] = "Welcome, " . $loggedInUser->displayname;


        }

    } // something went wrong
    catch (Exception $e) {
// Add the received error to the error buffer
        switch ($e->getCode()) {
            case 0 :
                $errors[] = "Unspecified error.";
                break;
            case 1 :
                $errors[] = "Hybriauth configuration error.";
                break;
            case 2 :
                $errors[] = "Provider not properly configured.";
                break;
            case 3 :
                $errors[] = "Unknown or disabled provider.";
                break;
            case 4 :
                $errors[] = "Missing provider application credentials.";
                break;
            case 5 :
                $errors[] = "Authentification failed. "
                    . "The user has canceled the authentication or the provider refused the connection.";
                break;
            case 6 :
                $errors[] = "User profile request failed. Most likely the user is not connected "
                    . "to the provider and he should authenticate again.";
                break;
            case 7 :
                $errors[] = "User not connected to the provider.";
                break;
            case 8 :
                $errors[] = "Provider does not support this feature.";
                break;
        }
        $errors[] = "<br /><br /><b>Original error message:</b> " . $e->getMessage();
    }

//Add error messages to the buffer
    restore_error_handler();
    foreach ($errors as $error) {
        addAlert("danger", $error);
    }
    //Add success messages to the buffer
    foreach ($successes as $success) {
        addAlert("success", $success);
    }
//Send the user back to the login page if there is an error
//else send them to their account page
    if (count($errors) > 0) {
        apiReturnError(false, SITE_ROOT . "login.php");
    } else {
        apiReturnSuccess(false, ACCOUNT_ROOT);

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<?php
echo renderTemplate("head.html", array("#SITE_ROOT#" => SITE_ROOT, "#SITE_TITLE#" => SITE_TITLE, "#PAGE_TITLE#" => "Login"));
?>

<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                    aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php">Geo2tag</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a href="index.php">Home</a></li>
                <li class="active"><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
        <!--/.nav-collapse -->
    </div>
</nav>

<div class="container">
    <div class="starter-template">
        <h1>Please sign in here:</h1>
    </div>

    <div class="jumbotron">

        <form class='form-horizontal' role='form' name='login' action='api/process_login.php' method='post'>
            <div class="row">
                <div id='display-alerts' class="col-lg-12">

                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-3 col-md-6">
                    <input type="text" class="form-control" id="inputUserName"
                           placeholder="<?php echo $user_email_placeholder; ?>" name='username' value=''>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-3 col-md-6">
                    <input type="password" class="form-control" id="inputPassword" placeholder="Password"
                           name='password'>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary submit" value='Login'>Login</button>
                </div>
            </div>
        </form>

        <fieldset>
            <legend>Or</legend>
            <p><a href="login.php?provider=Facebook" class="zocial facebook">Sign in with Facebook</a></p>

            <p><a href="login.php?provider=Google" class="zocial googleplus">Sign in with Google+</a></p>

            <p><a href="login.php?provider=Linkedin" class="zocial linkedin">Sign in with LinkedIn</a></p>

            <p><a href="login.php?provider=Vkontakte" class="zocial vk">Sign in with VKontakte</a></p>

        </fieldset>
    </div>
    <?php echo renderTemplate("footer.html"); ?>

</div>
<!-- /container -->

<script>
    $(document).ready(function () {

        alertWidget('display-alerts');

        $("form[name='login']").submit(function (e) {
            var form = $(this);
            var url = 'api/process_login.php';
            $.ajax({
                type: "POST",
                url: url,
                data: {
                    username: form.find('input[name="username"]').val(),
                    password: form.find('input[name="password"]').val(),
                    ajaxMode: "true"
                },
                success: function (result) {
                    var resultJSON = processJSONResult(result);
                    if (resultJSON['errors'] && resultJSON['errors'] > 0) {
                        alertWidget('display-alerts');
                    } else {
                        window.location.replace("account");
                    }
                }
            });
            // Prevent form from submitting twice
            e.preventDefault();
        });

    });
</script>
</body>
</html>