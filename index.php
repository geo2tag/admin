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

// Public page

setReferralPage(getAbsoluteDocumentPath(__FILE__));

//Forward the user to their default page if he/she is already logged in
if(isUserLoggedIn()) {
    addAlert("warning", "You're already logged in!");
    header("Location: account");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<?php
echo renderTemplate("head.html", array("#SITE_ROOT#" => SITE_ROOT, "#SITE_TITLE#" => SITE_TITLE, "#PAGE_TITLE#" => "Geo2tag User Management System"));
?>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">Geo2tag</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="index.php">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="container">

      <div class="starter-template">
        <h1>Welcome to Geo2tag User and Rights Management System!</h1>
        <p class="lead">A user and rights management system for the Geo2tag platform</p>
      </div>


        <div class="row">
            <div class="col-xs-6">
                <h2>Already <br> Registered?</h2>
                <p class="lead">If you have already registered and wish to log in to the site. You can do so by clicking the login button.  <a href="login.php" class="btn btn-primary" role="button" value='Login'>Login</a></p>
            </div>
            <div class="col-xs-6"><h2>Not Yet<br> Registered?</h2>
                <p class="lead">Please click here to create your free account <a href='register.php' class='btn btn-primary' role='button' value='Register'>Register here.</a></p>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-6"> <h2>Forgot<br> Your <br> Password?</h2>
                <p class="lead">Please click the Forgot Password button to recover your forgotten password <a href='forgot_password.php' class='btn btn-primary' role='button' value='Forgot Password'>Forgot password</a> </p>
            </div>
            <div class="col-xs-6"><h2>Resend<br> Activation<br> Email </h2>
                <p class="lead">Please click the Resend Activation Email button to have your activation email resent to your email address <a href='resend_activation.php' class='btn btn-primary' role='button' value='Activate'>Resend activation email</a> </p>
            </div>
        </div>




       <?php echo renderTemplate("footer.html"); ?>
    </div><!-- /.container -->


  </body>
</html>
<script>
    $(document).ready(function() {
        alertWidget('display-alerts');
    });
</script>