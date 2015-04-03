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
global $email_login;

if ($email_login == 1) {
    $user_email_placeholder = 'Username or Email';
}else{
    $user_email_placeholder = 'Username';
}
//Social network login
if( isset( $_REQUEST["provider"] ) )
{
    // the selected provider
    $provider_name = $_REQUEST["provider"];

    try
    {
        // include HybridAuth library
        $config   = "hybridauth/config.php";
       require_once( "hybridauth/Hybrid/Auth.php" );

        // initialize Hybrid_Auth class with the config file
        $hybridauth = new Hybrid_Auth( $config );

        // try to authenticate with the selected provider
        $adapter = $hybridauth->authenticate( $provider_name );

        // then grab the user profile
      $user_profile = $adapter->getUserProfile();
    }
     // something went wrong
    catch( Exception $e )
    {
// Display the received error
switch( $e->getCode() ){
     case 0 : echo "Unspecified error."; break;
     case 1 : echo "Hybriauth configuration error."; break;
     case 2 : echo "Provider not properly configured."; break;
     case 3 : echo "Unknown or disabled provider."; break;
     case 4 : echo "Missing provider application credentials."; break;
     case 5 : echo "Authentification failed. "
          . "The user has canceled the authentication or the provider refused the connection.";
        break;
     case 6 : echo "User profile request failed. Most likely the user is not connected "
        . "to the provider and he should authenticate again.";
        break;
    case 7 : echo "User not connected to the provider.";
        break;
    case 8 : echo "Provider does not support this feature."; break;
   }
echo "<br /><br /><b>Original error message:</b> " . $e->getMessage();
  }
/*   // check if the current user already have authenticated using this provider before
   $user_exist = get_user_by_provider_and_id( $provider_name, $user_profile->identifier );

   // if the used didn't authenticate using the selected provider before
   // we create a new entry on database.users for him
   if( ! $user_exist )
   {
       create_new_hybridauth_user(
           $user_profile->email,
           $user_profile->firstName,
           $user_profile->lastName,
           $provider_name,
           $user_profile->identifier
       );
   }

   // set the user as connected and redirect him
   $_SESSION["user_connected"] = true;

   header("Location: http://www.example.com/user/home.php");*/
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
                  <li ><a href="index.php">Home</a></li>
                  <li class="active"><a href="login.php">Login</a></li>
                  <li ><a href="register.php">Register</a></li>
              </ul>
          </div><!--/.nav-collapse -->
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
			  <input type="text" class="form-control" id="inputUserName" placeholder="<?php echo $user_email_placeholder; ?>" name = 'username' value=''>
			</div>
		  </div>
		  <div class="form-group">
			<div class="col-md-offset-3 col-md-6">
			  <input type="password" class="form-control" id="inputPassword" placeholder="Password" name='password'>
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
              <p>  <a href="login.php?provider=facebook" class="zocial facebook">Sign in with Facebook</a> </p>
                <p>   <a href="login.php?provider=google" class="zocial googleplus">Sign in with Google+</a> </p>
                <p>   <a href="login.php?provider=linkedin" class="zocial linkedin">Sign in with LinkedIn</a> </p>
                <p>  <a href="login.php?provider=vkontakte" class="zocial vk">Sign in with VKontakte</a> </p>

            </fieldset>
        </div>
      <?php echo renderTemplate("footer.html"); ?>

    </div> <!-- /container -->

	<script>
        $(document).ready(function() {          

		  alertWidget('display-alerts');
			  
		  $("form[name='login']").submit(function(e){
			var form = $(this);
			var url = 'api/process_login.php';
			$.ajax({  
			  type: "POST",  
			  url: url,  
			  data: {
				username:	form.find('input[name="username"]').val(),
				password:	form.find('input[name="password"]').val(),
				ajaxMode:	"true"
			  },		  
			  success: function(result) {
				var resultJSON = processJSONResult(result);
				if (resultJSON['errors'] && resultJSON['errors'] > 0){
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