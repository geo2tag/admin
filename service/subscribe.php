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
$auth_token = "";
$channel = "";
$user_exists = true;

// Confirm that data has been submitted via POST
if (!($_SERVER['REQUEST_METHOD'] == 'POST')) {
    $errors[] =  "Error: data must be submitted via POST.";
}


if (isset($_POST["auth_token"]))
    $auth_token = $_POST["auth_token"];
else {
    $errors[] = "Parameter auth_token must be specified!";
}


if (isset($_POST["channel"]))
    $channel = $_POST["channel"];
else {
    $errors[] = "Parameter channel must be specified!";
}

//If there is an error
if (count($errors) > 0){
    //Return an error message to the application submitting the HTTP request
    echo json_encode($errors);
} else {
    //Check to see if the user has the necessary access rights, if yes then
    //forward the request to Geo2tag server and then send the reply from Geo2tag
    //back to the application sending the HTTP request

    //check to see if there is a user with the supplied geo2tag_oauth_token
    //TODO: Move all database code into db_functions.php
    try {
        global $db_table_prefix;

        $results = array();

        $db = pdoConnect();

        $sqlVars = array();

        $query = "SELECT
            id,
            geo2tag_oauth_time_stamp,
            active,
            enabled
            FROM ".$db_table_prefix."users
            WHERE
            geo2tag_oauth_token = :data
            LIMIT 1";

        $stmt = $db->prepare($query);

        $sqlVars[':data'] = $auth_token;

        $stmt->execute($sqlVars);

        if (!($results = $stmt->fetch(PDO::FETCH_ASSOC))){
            // The user does not exist
            $errors[] = "Invalid auth_token";
            $user_exists = false;
        }

        $stmt = null;

    } catch (PDOException $e) {
        error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
    } catch (ErrorException $e) {
        error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
    } catch (RuntimeException $e) {
        error_log("Error in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
    }

    //if we were able to confirm from the sql query that a valid user with the auth_token exists
    if($user_exists === true)
    {
        //See if the user's account is activated
        if ($results["active"] == 0) {
            $errors[] = lang("ACCOUNT_INACTIVE");
        } // See if user's account is enabled
        else if ($results["enabled"] == 0) {
            $errors[] = lang("ACCOUNT_DISABLED");
        } else { //The auth_token belongs to a user whose account is enabled and activated

                //Get the time when the auth_token was generated
                $geo2tag_token_gen_time = $results["geo2tag_oauth_time_stamp"];
                //Set the auth_token time out value
                $geo2tag_token_timeout = 72*60*60; //72 hours (3 days)
                //calculate the time difference
                $geo2tag_token_life = time() - $geo2tag_token_gen_time;
                //check to see if the token is still valid based on the timeout value
                if($geo2tag_token_life >= $geo2tag_token_timeout)
                {
                    //if not valid send an error message to the user
                    $errors[] = "The token has expired! Please login again to get a valid token";
                }
                else{
                    //The time out value has not been exceeded
                   //Check to see if the user has the necessary access rights to perform this action
                   //A user must belong to a group with the same name as the channel they want to subscribe to
                    $user_id_num = $results["id"];
                    $group_details = fetchGroupDetailsByName($channel);
                    $group_id_num = $group_details["id"];
                    if (!userInGroup($user_id_num, $group_id_num)){
                        $errors[] = "Sorry but you don't have the necessary access rights to perform this action";
                    }
                    else{ //The user has the relevant access rights
                        //Forward a login request to the Geo2tag server

                        // Make sure Requests can load internal classes
                        Requests::register_autoloader();

                        $url = 'http://194.85.173.9:20005/service/login';
                        $headers = array('Content-Type' => 'application/json');
                        $data = array('login' => '??', 'password' => '??');
                        $response = Requests::post($url, $headers, json_encode($data));

                        if(!$response->success)
                        {//if the HTTP request was not successful
                            $errors[] = "There was an error processing the request please contact the server administrator";
                        }
                        else{ //We got a valid response from the Geo2tag server
                            //Forward the subscribe request to the Geo2tag server

                            $obj = json_decode($response->body);
                            $geo2tag_token = $obj->{'auth_token'};
                            $url2 = 'http://194.85.173.9:20005/service/subscribe';
                            $data2 = array('auth_token' => $geo2tag_token, 'channel' => $channel);
                            $response2 = Requests::post($url2, $headers, json_encode($data2));
                            if(!$response2->success)
                            {//if the HTTP request was not successful
                                $errors[] = "There was an error processing the request please contact the server administrator";
                            }
                            else{//We got a valid response from the Geo2tag server
                                $obj2 = json_decode($response2->body);
                                echo json_encode($obj2);
                            }

                            }


                    }

                }
            }

        }

    if (count($errors) > 0) {
        //Return an error message to the application submitting the HTTP request
        echo json_encode($errors);
    }
}