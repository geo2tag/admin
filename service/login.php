<?php
/*
Geo2tag user and rights management subsystem
 @author      Benjamin Ezepue
 @link        http://geo2tag.com

Based on UserFrosting Version: 0.2.2
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
require_once("../models/config.php");

$errors = array();
$login;

// Confirm that data has been submitted via POST
if (!($_SERVER['REQUEST_METHOD'] == 'POST')) {
    $errors[] = "Error: data must be submitted via POST.";
}


if (isset($_POST["login"]))
    $login = $_POST["login"];
else {
    $errors[] = "Parameter login must be specified!";
}


if (isset($_POST["password"]))
    $password = $_POST["password"];
else {
    $errors[] = "Parameter password must be specified!";
}

//If there is an error
if (count($errors) > 0) {
    //Return an error message to the application submitting the HTTP request
    echo json_encode($errors);
} else {
    //Try to log the user in and then if login was successful
    //send an authentication token back to the user


    //Try to log the user in
    $existsVar = !usernameExists($login);

    if ($existsVar) {
        $errors[] = lang("ACCOUNT_USER_OR_PASS_INVALID");
    } else {

        $userdetails = fetchUserAuthByUserName($login);

        //See if the user's account is activated
        if ($userdetails["active"] == 0) {
            $errors[] = lang("ACCOUNT_INACTIVE");
        } // See if user's account is enabled
        else if ($userdetails["enabled"] == 0) {
            $errors[] = lang("ACCOUNT_DISABLED");
        } else {
            // Validate the password
            if (!passwordVerifyUF($password, $userdetails["password"])) {
                //Again, we know the password is at fault here, but lets not give away the combination in case of someone bruteforcing
                $errors[] = lang("ACCOUNT_USER_OR_PASS_INVALID");
            } else {
                //Passwords match! we're good to go'

                //Construct a new logged in user object
                $loggedInUser = new loggedInUser();

                //Update last sign in
                $loggedInUser->updateLastSignIn();

                // Create a token for the user
                $auth_token = $loggedInUser->csrf_token(true);

                // Insert the Oauth token and time stamp into the database
                //TODO: Move all database code into db_functions.php
                try {
                    global $db_table_prefix;

                    $db = pdoConnect();

                    $sqlVars = array();
                    $query = "UPDATE " . $db_table_prefix . "users
            SET geo2tag_oauth_token = :token,
            geo2tag_oauth_time_stamp = :time
            WHERE user_name = :user_name";

                    $stmt = $db->prepare($query);

                    $sqlVars['token'] = $auth_token;
                    $sqlVars['time'] = time();
                    $sqlVars['user_name'] = $login;

                    if (!$stmt->execute($sqlVars)) {
                        // Error: column does not exist
                        error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
                    }

                } catch (PDOException $e) {
                    error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
                } catch (ErrorException $e) {
                    error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
                } catch (RuntimeException $e) {
                    error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
                }

                //send the auth_token back to the user
                echo json_encode($auth_token);

            }
        }
    }
    if (count($errors) > 0) {
        //Return an error message to the application submitting the HTTP request
        echo json_encode($errors);
    }
}


