<?php

App::uses('Utility', 'Lib');
App::uses('Message', 'Lib');
App::uses('CustomEmail', 'Lib');

class ApiController extends AppController
{

    //public $components = array('Email');

    public $autoRender = false;
    public $layout = false;


    public function beforeFilter()
    {



        $this->loadModel('User');
        $json = file_get_contents('php://input');
        $json_error = Utility::isJsonError($json);

        if ($this->request->isPost()) {

            if (!function_exists('apache_request_headers')) {
                $headers = Utility::apache_request_headers();
            } else {
                $headers = apache_request_headers();
            }


            $user_id = 0;
            if (array_key_exists("User-Id", $headers)) {
                $user_id = $headers['User-Id'];

            } else if (array_key_exists("USER-ID", $headers)) {

                $user_id = $headers['USER-ID'];
            }





            $client_api_key = 0;
            if (array_key_exists("Api-Key", $headers)) {
                $client_api_key = $headers['Api-Key'];

            } else if (array_key_exists("API-KEY", $headers)) {

                $client_api_key = $headers['API-KEY'];
            } else if (array_key_exists("api-key", $headers)) {

                $client_api_key = $headers['api-key'];
            }


            if ($client_api_key > 0) {


                if ($client_api_key != API_KEY) {

                    Message::ACCESSRESTRICTED();
                    die();

                }
            } else {
                $output['code'] = 201;
                $output['msg'] = "API KEY is missing";

                echo json_encode($output);
                die();

            }

            if ($user_id > 0) {


                $userDetails = $this->User->getUserDetailsFromID($user_id);

                if (count($userDetails) > 0) {


                    $social = $userDetails['User']['social'];
                    $db_auth_token = $userDetails['User']['auth_token'];
                    $active = $userDetails['User']['active'];


                    if ($active > 1) {


                        $output['code'] = 501;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }



                }
            }

            if ($json_error == "false") {


                return true;


            } else {

                $output['code'] = 202;
                $output['msg'] = $json_error;

                echo json_encode($output);
                die();


            }
        }
    }





    public function index(){


        echo "Congratulations!. You have configured your mobile api correctly";

    }


    public function registerUser()
    {


        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $user['created'] = date('Y-m-d H:i:s', time());
            $user['role'] =  $data['role'];



            if($data['role'] == "rider"){

                $user['active'] = 2;


            }
            if(isset($data['country_id'])){

                $user['country_id'] = $data['country_id'];

            }

            if(isset($data['phone'])){

                $user['phone'] = $data['phone'];
                $phone_exist = $this->User->isphoneNoAlreadyExist($user['phone']);

                if ($phone_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This phone has already been registered";
                    echo json_encode($result);
                    die();
                }

            }


            if (isset($data['image'])) {


                $image = $data['image'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                $user['image'] = $filePath;
            }

            if(isset($data['social']) && !isset($data['dob'])){
                $social_id = $data['social_id'];
                $auth_token = $data['auth_token'];
                $social = $data['social'];
                $user_details = $this->User->isSocialIDAlreadyExist($social_id);

                if(count($user_details) > 0 ){

                    if($social == "facebook"){

                        $verify = Utility::getFacebookUserInfo($auth_token);
                        if($verify){

                            //$this->User->id = $user_details['User']['id'];
                            //$this->User->saveField('auth_token',$auth_token);

                            $output['code'] = 200;
                            $output['msg'] = $user_details;
                            echo json_encode($output);
                            die();

                        }else{

                            $output['code'] = 201;
                            $output['msg'] = "token invalid";
                            echo json_encode($output);
                            die();


                        }

                    }

                    if($social == "google"){

                        $verify = Utility::getGoogleUserInfo($auth_token);

                        if($verify){

                            // $this->User->id = $user_details['User']['id'];
                            // $this->User->saveField('auth_token',$auth_token);

                            $output['code'] = 200;
                            $output['msg'] = $user_details;
                            echo json_encode($output);
                            die();

                        }else{

                            $output['code'] = 201;
                            $output['msg'] = "token invalid";
                            echo json_encode($output);
                            die();


                        }

                    }


                    $output['code'] = 200;
                    $output['msg'] = $user_details;
                    echo json_encode($output);
                    die();

                }else{


                    $output['code'] = 201;
                    $output['msg'] = "open registration screen";
                    echo json_encode($output);
                    die();

                }
            }


            if(isset($data['social']) && isset($data['dob'])){
                $social = $data['social'];
                $auth_token = $data['auth_token'];
                $user['social_id'] = $data['social_id'];
                $user['social'] = $social;
                if(isset($data['dob'])){

                    $user['dob'] = $data['dob'];
                }

                if(isset($data['username'])){

                    $user['username'] = $data['username'];
                }

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if(isset($data['first_name'])){

                    $user['first_name'] = $data['first_name'];
                    $user['last_name'] = $data['last_name'];
                }
                $user['email'] = $data['email'];
                $username_count = $this->User->isUsernameAlreadyExist($data['username']);
                if($username_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }




                if($social == "facebook") {

                    $verify = Utility::getFacebookUserInfo($auth_token);
                    if (!$verify) {


                        $output['code'] = 201;
                        $output['msg'] = "invalid Facebook token";
                        echo json_encode($output);
                        die();

                    }
                }

                if($social == "google") {

                    $verify = Utility::getGoogleUserInfo($auth_token);
                    $verify = true;
                    if (!$verify) {


                        $output['code'] = 201;
                        $output['msg'] = "invalid Google token";
                        echo json_encode($output);
                        die();

                    }
                }

                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();

            }

            if(!isset($data['social']) && isset($data['email'])){

                $session_token = Utility::generateSessionToken();
                if(isset($data['dob'])){

                    $user['dob'] = $data['dob'];
                }

                if(isset($data['username'])){

                    $user['username'] = $data['username'];
                }

                $user['auth_token'] = $session_token;

                $user['password'] = $data['password'];
                $user['email'] = $data['email'];

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if(isset($data['first_name'])){

                    $user['first_name'] = $data['first_name'];
                    $user['last_name'] = $data['last_name'];
                }



                $email_count = $this->User->isEmailAlreadyExist($data['email']);



                if($email_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "The account already exist with this email";
                    echo json_encode($output);
                    die();
                }

                $username_count = $this->User->isUsernameAlreadyExist($data['username']);
                if($username_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }
                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();





            }


            if(isset($data['phone']) && !isset($data['dob'])) {
                //login

                $user['phone'] = $data['phone'];


                $phone_exist = $this->User->isphoneNoAlreadyExist($data['phone']);

                if ($phone_exist > 0) {
                    $session_token = Utility::generateSessionToken();
                    $user['auth_token'] = $session_token;
                    $this->User->id = $phone_exist['User']['id'];




                    $this->User->save($user);
                    $userDetails = $this->User->getUserDetailsFromID($phone_exist['User']['id']);


                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);
                    die();
                } else {

                    $output['code'] = 201;
                    $output['msg'] = "open register screen";
                    echo json_encode($output);
                    die();

                }

            }else  if(isset($data['phone']) && isset($data['dob'])){

                //register
                $session_token = Utility::generateSessionToken();
                $user['phone'] = $data['phone'];
                $user['auth_token'] = $session_token;

                $user['username'] = $data['username'];
                $user['dob'] = $data['dob'];

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if(isset($data['first_name'])){

                    $user['first_name'] = $data['first_name'];
                    $user['last_name'] = $data['last_name'];
                }

                $username_count = $this->User->isUsernameAlreadyExist($data['username']);
                if($username_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }

                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);



                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();


            }











        }
    }
    public function withdrawRequest()
    {


        $this->loadModel('WithdrawRequest');
        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $withdraw_data['user_id'] = $data['user_id'];
            $withdraw_data['amount'] = $data['amount'];

            $withdraw_data['created'] = date('Y-m-d H:i:s', time());



            $details = $this->WithdrawRequest->getUserPendingWithdrawRequest($data['user_id']);

            if(count($details) > 0 ){

                $output['code'] = 201;
                $output['msg'] = "You have already requested a payout.";
                echo json_encode($output);
                die();

            }




            $this->WithdrawRequest->save($withdraw_data);

            $id = $this->WithdrawRequest->getInsertID();

            $output = array();
            $details = $this->WithdrawRequest->getDetails($id);
            $this->User->id =  $data['user_id'];
            $this->User->saveField('wallet',0);



            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();





        }
    }

    public function addPayout()
    {



        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $withdraw_data['user_id'] = $data['user_id'];
            $withdraw_data['email'] = $data['email'];




            $details = $this->User->getUserDetailsFromID($data['user_id']);

            if(count($details) > 0 ) {


                $this->User->id = $data['user_id'];
                $this->User->saveField('paypal', $data['email']);
                $details = $this->User->getUserDetailsFromID($data['user_id']);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }


        }
    }




    public function logout()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);




            $user_id = $data['user_id'];
            $user['device_token'] = "";
            $user['online'] = 0;
            $user['auth_token'] = "";


            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {

                $this->User->id = $user_id;
                $this->User->save($user);


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
            }else{


                Message::EMPTYDATA();
                die();


            }

        }
    }

    public function login()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);


            $password = $data['password'];
            $role = $data['role'];







            if (isset($data['email'])) {

                $email = strtolower($data['email']);
                $userData = $this->User->verify($email, $password,$role);


                if(count($userData) < 0){

                    $userData = $this->User->verifyWithUsername($email, $password,$role);

                }
            }


            if (($userData)) {
                $user_id = $userData[0]['User']['id'];
                $session_token = Utility::generateSessionToken();
                $this->User->id = $user_id;
                $this->User->saveField('auth_token',$session_token);
                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);

                //CustomEmail::welcomeStudentEmail($email);
                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);


            } else {
                echo Message::INVALIDDETAILS();
                die();

            }


        }
    }


    public function showCountries(){

        $this->loadModel('Country');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $countries = $this->Country->getAll();





            $output['code'] = 200;

            $output['msg'] = $countries;


            echo json_encode($output);


            die();


        }


    }


    public function showDefaultCurrency(){

        $this->loadModel('Country');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $countries = $this->Country->getDefaultCountry();





            $output['code'] = 200;

            $output['msg'] = $countries;


            echo json_encode($output);


            die();


        }


    }

    public function editProfile()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);




            $user['dob'] = $data['dob'];





            $user_id = $data['user_id'];





            if(isset($data['first_name'])){


                $user['first_name'] = $data['first_name'];
            }
            if(isset($data['last_name'])){


                $user['last_name'] = $data['last_name'];
            }

            if(isset($data['phone'])){


                $user['phone'] = $data['phone'];
            }

            if(isset($data['country_id'])){


                $user['country_id'] = $data['country_id'];
            }

            if(isset($data['email'])){


                if($data['email'] == "rider@gmail.com" || $data['email'] == "user@gmail.com" && APP_STATUS == "demo"){

                    $output['code'] = 201;
                    $output['msg'] = "You cannot change demo account information";
                    echo json_encode($output);
                    die();
                }
                $user['email'] = $data['email'];
                $email_exist = $this->User->editIsEmailAlreadyExist($data['email'], $user_id);



                if($email_exist > 0){

                    $output['code'] = 201;
                    $output['msg'] = "email already exist";
                    echo json_encode($output);
                    die();
                }
            }

            if(isset($data['username'])){


                $user['username'] = $data['username'];
                $username_exist = $this->User->editIsUsernameAlreadyExist($data['username'], $user_id);
                if($username_exist > 0){

                    $output['code'] = 201;
                    $output['msg'] = "username already exist";
                    echo json_encode($output);
                    die();
                }
            }

            $user['dob'] = $data['dob'];



            // $phone = $this->User->editIsphoneNoAlreadyExist($data['phone'], $user_id);


            $this->User->id = $user_id;
            $this->User->save($user);


            $output = array();
            $userDetails = $this->User->getUserDetailsFromID($user_id);


            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


        }
    }
    public function addDeviceData()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user['device_token'] = $data['device_token'];
            $user['ip'] = $data['ip'];
            $user['device'] = $data['device'];
            $user['version'] = $data['version'];



            $user_id = $data['user_id'];


            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {

                $this->User->id = $user_id;
                $this->User->save($user);

                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
            }else{


                Message::EMPTYDATA();
                die();


            }

        }
    }

    public function addUserImage()
    {


        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $userDetails = $this->User->getUserDetailsFromID($user_id);


            if (isset($data['image'])) {


                $image_db = $userDetails['User']['image'];
                if (strlen($image_db) > 5) {
                    @unlink($image_db);

                }

                $image = $data['image'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder($user_id, $image, $folder_url);
                $user['image'] = $filePath;


                $this->User->id = $user_id;
                if (!$this->User->save($user)) {
                    echo Message::DATASAVEERROR();
                    die();
                }


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);


            }else{

                $output['code'] = 201;
                $output['msg'] = "please send the correct image";
                echo json_encode($output);
            }



        }
    }


    public function addSignature()
    {


        $this->loadModel('Order');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $order_id = $data['order_id'];

            $orderDetails = $this->Order->getDetails($order_id);


            if (isset($data['signature'])) {




                $image = $data['signature'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder($order_id, $image, $folder_url);
                $user['signature'] = $filePath;


                $this->Order->id = $order_id;
                if (!$this->Order->save($user)) {
                    echo Message::DATASAVEERROR();
                    die();
                }


                $output = array();
                $orderDetails = $this->Order->getDetails($order_id);


                $output['code'] = 200;
                $output['msg'] = $orderDetails;
                echo json_encode($output);


            }else{

                $output['code'] = 201;
                $output['msg'] = "please send the correct image";
                echo json_encode($output);
            }



        }
    }

    public function showGoodTypes()
    {

        $this->loadModel("GoodType");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $types = $this->GoodType->getAll();


            if(count($types) > 0) {
                $output['code'] = 200;

                $output['msg'] = $types;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }

    public function showDeliveryTypes()
    {

        $this->loadModel("DeliveryType");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $types = $this->DeliveryType->getAll();


            if(count($types) > 0) {
                $output['code'] = 200;

                $output['msg'] = $types;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }



    public function placeOrder(){


        $this->loadModel('Order');
        $this->loadModel('User');
        $this->loadModel("CouponUsed");
        $this->loadModel("OrderTransaction");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $order['user_id'] =  $data['user_id'];
            $order['good_type_id'] =  $data['good_type_id'];
            $order['delivery_type_id'] =  $data['delivery_type_id'];
            $order['coupon_id'] =  $data['coupon_id'];
            $order['cod'] =  $data['cod'];
            $payment_id = $data['payment_id'];
            $order['discount'] =  $data['discount'];
            $order['price'] =  $data['price'];
            $order['total'] =  $data['total'];
            $order['pickup_datetime'] =  $data['pickup_datetime'];
            $order['item_title'] =  $data['item_title'];
            $order['item_description'] =  $data['item_description'];
            $order['sender_name'] =  $data['sender_name'];
            $order['sender_email'] =  $data['sender_email'];
            $order['sender_phone'] =  $data['sender_phone'];
            $order['receiver_name'] =  $data['receiver_name'];
            $order['receiver_email'] =  $data['receiver_email'];
            $order['receiver_phone'] =  $data['receiver_phone'];
            $order['good_type_id'] =  $data['good_type_id'];


            $order['sender_location_lat'] = $data['sender_location_lat'];
            $order['sender_location_long'] = $data['sender_location_long'];
            $order['sender_location_string'] = $data['sender_location_string'];
            $order['sender_address_detail'] =  $data['sender_address_detail'];
            $order['receiver_location_lat'] = $data['receiver_location_lat'];
            $order['receiver_location_long'] = $data['receiver_location_long'];
            $order['receiver_location_string'] = $data['receiver_location_string'];
            $order['receiver_address_detail'] =  $data['receiver_address_detail'];


            $filepath = $this->makeTripMap($data['sender_location_lat'],$data['sender_location_long'],$data['receiver_location_lat'],$data['receiver_location_long'],$data['user_id']);

            if(!$filepath){

                $output['code'] = 201;

                $output['msg'] = "Please fix your google maps key. There are some issues with the permission";


                echo json_encode($output);


                die();
            }

            $order['map'] =  $filepath;
            if(isset($data['package_size_id'])){

                $order['package_size_id'] =  $data['package_size_id'];

            }



            $user_details =  $this->User->getUserDetailsFromID($data['user_id']);


            if(count($user_details) > 0) {

                if(isset($data['id'])){

                    $this->Order->id = $data['id'];
                    $this->Order->save($order);

                    $details =  $this->Order->getDetails($data['id']);

                    $output['code'] = 200;

                    $output['msg'] = $details;


                    echo json_encode($output);


                    die();

                }
                $order['created'] = date('Y-m-d H:i:s', time());


                $this->Order->save($order);
                $id = $this->Order->getInsertID();

                if ($data['coupon_id'] > 0) {
                    $coupon['coupon_id'] = $data['coupon_id'];
                    $coupon['order_id'] = $id;
                    $coupon['created'] = $order['created'];
                    $this->CouponUsed->save($coupon);
                }

                if ($payment_id > 0) {
                    $stripe_charge = $this->deductPayment($payment_id, round($data['total']));
                    $order['stripe_charge'] = $stripe_charge;
                }

                if (isset($data['transaction'])) {


                    $transaction = $data['transaction'];

                    if(count($transaction) > 0){

                        $order_transaction['type'] = $transaction['type'];

                        if($transaction['type'] == "stripe"){

                            $order_transaction['value'] = $order['stripe_charge'];
                        }

                        $order_transaction['value'] = $transaction['value'];

                        $order_transaction['order_id'] = $id;
                        $order_transaction['created'] = $order['created'];

                        $this->OrderTransaction->save($order_transaction);


                    }
                }
                $details =  $this->Order->getDetails($id);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }

        }




    }
    public function showRiderOrderHistory()
    {

        $this->loadModel("RiderOrder");

        $this->loadModel("User");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $starting_point = $data['starting_point'];






            $orders = $this->RiderOrder->getCompletedOrders($user_id,$starting_point);
            $completed_orders_count = $this->RiderOrder->getCountCompletedOrders($user_id);
            $total_earnings = $this->RiderOrder->getRiderEarningsCompletedOrders($user_id);
            $total_orders_amount_count = $total_earnings[0]['total_sum'];

                $user_details =  $this->User->getUserDetailsFromID($user_id);


                $rider_fee_type_per_order = $user_details['User']['rider_fee_type_per_order'];
                $rider_comission = $user_details['User']['rider_comission'];
                if($rider_fee_type_per_order < 1){

                    $earning = $total_orders_amount_count/100*$rider_comission;
                }else{

                    $earning =  $completed_orders_count*$rider_comission;

                }

            $output['code'] = 200;

            $output['orders'] = $orders;

            $output['stats']['completed_orders_count']  = $completed_orders_count;
            $output['stats']['total_earning']  = round($earning);


            echo json_encode($output);

            die();












        }
    }

    public function verifyCoupon()
    {

        $this->loadModel("Coupon");
        $this->loadModel("CouponUsed");
        // $this->loadModel("RestaurantRating");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id       = $data['user_id'];
            $coupon_code   = $data['coupon_code'];


            $coupon_exist  = $this->Coupon->getCouponDetails($coupon_code);


            if(count($coupon_exist) > 0) {



                $coupon_id = $coupon_exist['Coupon']['id'];
                $user_limit = $coupon_exist['Coupon']['limit_users'];
                $count_coupon_used = $this->CouponUsed->countCouponUsed($coupon_id);




                $coupon_user_used = $this->CouponUsed->ifCouponCodeUsedByUser($coupon_id, $user_id);


                if (count($coupon_exist) > 0 && $coupon_user_used == 1) {

                    $output['code'] = 201;


                    $output['msg'] = "invalid coupon code";

                    echo json_encode($output);

                    die();

                } else if (count($coupon_exist)> 0 && $coupon_user_used == 0 && $count_coupon_used < $user_limit) {

                    $coupon = $this->Coupon->getDetails($coupon_id);


                    $output['code'] = 200;


                    $output['msg'] = $coupon;

                    echo json_encode($output);

                    die();


                }else{



                    $output['code'] = 201;


                    $output['msg'] = "invalid coupon code";

                    echo json_encode($output);

                    die();
                }


            }else{


                $output['code'] = 201;


                $output['msg'] = "invalid coupon code";

                echo json_encode($output);

                die();

            }








        }
    }

    public function riderOrderResponse()
    {

        $this->loadModel("RiderOrder");
        $this->loadModel("Order");

        if ($this->request->isPost()) {
            $json     = file_get_contents('php://input');
            $data     = json_decode($json, TRUE);
            $order_id = $data['order_id'];
            $rider_order['rider_response']   = $data['rider_response'];
            $rider_order['rider_response_datetime'] = date('Y-m-d H:i:s', time());

            $rider_details = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);

            if(count($rider_details) > 0) {
                $id = $rider_details['RiderOrder']['id'];


                if($data['rider_response'] == 1){

                    $this->Order->id = $order_id;
                    $this->Order->saveField('status',1);


                    $msg = "Order has been accepted by the rider";

                }else{


                    $msg = "Order has been rejected by the rider";
                }


                $notification['to'] = $rider_details['Order']['User']['device_token'];
                $notification['notification']['title'] = $msg;
                $notification['notification']['body'] = "";
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "";
                $notification['notification']['order_id'] = $order_id;
                $notification['data']['title'] = $msg;
                $notification['data']['body'] = '';
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "";
                $notification['data']['order_id'] = $order_id;
                Utility::sendPushNotificationToMobileDevice(json_encode($notification));



                $this->RiderOrder->id = $id;


                if ($this->RiderOrder->save($rider_order)) {

                    $rider_details = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);

                    $output['code'] = 200;

                    $output['msg'] = $rider_details;


                    echo json_encode($output);


                    die();

                } else {

                    echo Message::DATASAVEERROR();
                    die();
                }
            }else{

                Message::EMPTYDATA();
                die();

            }

        }
    }





    public function updateRiderOrderStatus()
    {


        $this->loadModel("RiderOrder");
        $this->loadModel("Order");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $order_id = $data['order_id'];





            $order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);
            if(count($order_detail) > 0) {
                if (isset($data['on_the_way_to_pickup'])) {


                    $rider_order['on_the_way_to_pickup'] = $data['on_the_way_to_pickup'];

                    $msg = "The rider is on their way to pickup the order";

                    $this->RiderOrder->id = $order_detail['RiderOrder']['id'];
                    $this->RiderOrder->saveField('on_the_way_to_pickup', $data['on_the_way_to_pickup']);
                    /************notification**********/





                }else
                    if (isset($data['pickup_datetime'])) {


                        $rider_order['pickup_datetime'] = $data['pickup_datetime'];

                        $msg = "The order has been collected";

                        $this->RiderOrder->id = $order_detail['RiderOrder']['id'];
                        $this->RiderOrder->saveField('pickup_datetime', $data['pickup_datetime']);
                        /************notification**********/



                    }else
                        if (isset($data['on_the_way_to_dropoff'])) {


                            $rider_order['on_the_way_to_dropoff'] = $data['on_the_way_to_dropoff'];

                            $msg = "The rider is on their way to deliver the order";

                            $this->RiderOrder->id = $order_detail['RiderOrder']['id'];
                            $this->RiderOrder->saveField('on_the_way_to_dropoff', $data['on_the_way_to_dropoff']);
                            /************notification**********/



                        }else
                            if (isset($data['delivered'])) {


                                $rider_order_data['delivered'] = $data['delivered'];

                                //$rider_order['delivered'] = $data['delivered'];

                                $msg = "The rider has delivered the order";

                                $this->RiderOrder->id = $order_detail['RiderOrder']['id'];
                                $this->RiderOrder->save($rider_order_data);
                                /************notification**********/
                                $this->Order->id = $order_id;
                                $this->Order->saveField('status',2);


                                $rider_fee_type_per_order = $order_detail['Rider']['rider_fee_type_per_order'];
                                $rider_comission = $order_detail['Rider']['rider_comission'];
                                if($rider_fee_type_per_order < 1){

                                    $earning = $order_detail['Order']['total']/100*$rider_comission;
                                }else{

                                    $earning =  $rider_comission;

                                }
                                $wallet = $order_detail['Rider']['wallet'];
                                $this->User->id = $order_detail['Rider']['id'];
                                $this->User->saveField('wallet',$wallet + $earning);


                            } else {


                                $msg = "order already completed";

                            }
                /************notification**********/



                $notification['to'] = $order_detail['Order']['User']['device_token'];
                $notification['notification']['title'] = $msg;
                $notification['notification']['body'] = "";
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "";
                $notification['notification']['order_id'] = $order_detail['Order']['id'];
                $notification['notification']['rider_order'] = $order_detail['RiderOrder'];
                $notification['data']['title'] = $msg;
                $notification['data']['body'] = '';
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "";
                $notification['data']['order_id'] = $order_detail['Order']['id'];
                $notification['data']['rider_order'] = $order_detail['RiderOrder'];
                Utility::sendPushNotificationToMobileDevice(json_encode($notification));

                $order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);

                $output['code'] = 200;
                $output['msg'] = $order_detail;

                echo json_encode($output);

                die();


            }else{

                Message::EMPTYDATA();
                die();
            }



        }
    }


    public function updateOrderStatus()
    {


        $this->loadModel("RiderOrder");
        $this->loadModel("Order");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $order_id = $data['order_id'];
            $status = $data['status'];



            $order_details =  $this->Order->getDetails($order_id);
            $created = date('Y-m-d H:i:s', time());


            $rider_order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);

            if(count($order_details) > 0) {


                if($status = 3){

                    $this->Order->id = $order_id;
                    $this->Order->save('status',$status);


                    if(count($rider_order_detail) > 0){
                        $msg = "Order# ".$order_id." has been cancelled by the customer";

                        $rider_order_id = $rider_order_detail['RiderOrder']['id'];

                        $rider_order_response['user_response'] = 2;
                        $rider_order_response['user_response_datetime'] = $created;
                        $this->RiderOrder->id = $rider_order_id;
                        $this->RiderOrder->save($rider_order_response);



                        /************notification**********/



                        $notification['to'] = $rider_order_detail['Rider']['device_token'];
                        $notification['notification']['title'] = $msg;
                        $notification['notification']['body'] = "";
                        $notification['notification']['badge'] = "1";
                        $notification['notification']['sound'] = "default";
                        $notification['notification']['icon'] = "";
                        $notification['notification']['type'] = "";
                        $notification['notification']['order_id'] = $order_id;
                        // $notification['notification']['rider_order'] = $rider_order_detail['RiderOrder'];
                        $notification['data']['title'] = $msg;
                        $notification['data']['body'] = '';
                        $notification['data']['icon'] = "";
                        $notification['data']['badge'] = "1";
                        $notification['data']['sound'] = "default";
                        $notification['data']['type'] = "";
                        $notification['data']['order_id'] = $order_id;
                        //  $notification['data']['rider_order'] = $rider_order_detail['RiderOrder'];
                        $result = Utility::sendPushNotificationToMobileDevice(json_encode($notification));


                    }



                }else{

                    "nothing happens";

                }


                $this->Order->id = $order_id;
                $this->Order->saveField("status",3);
                $order_details =  $this->Order->getDetails($order_id);

                $output['code'] = 200;

                $output['msg'] = $order_details;


                echo json_encode($output);

                die();

            }else{

                Message::EMPTYDATA();
                die();
            }



        }
    }


    public function showRiderOrders()
    {

        $this->loadModel("RiderOrder");

        $this->loadModel("User");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $starting_point = $data['starting_point'];
            $completed_orders_count = $this->RiderOrder->getCountCompletedOrders($user_id);
            if(isset($data['type'])) {
                $type = $data['type'];

                if ($type == "active") {
                    $orders = $this->RiderOrder->getActiveOrders($user_id,$starting_point);

                } else if ($type == "pending") {

                    $orders = $this->RiderOrder->getPendingOrders($user_id,$starting_point);

                } else if ($type == "completed") {
                    $orders = $this->RiderOrder->getCompletedOrders($user_id,$starting_point);
                }
                $output['code'] = 200;
                $output['msg'] = $orders;
                // $output['stats']['completed_orders_count']  = $completed_orders_count;
                // $output['stats']['total_earning']  = 25;
            }else{

                $active_orders = $this->RiderOrder->getActiveOrders($user_id,$starting_point);
                $pending_orders = $this->RiderOrder->getPendingOrders($user_id,$starting_point);
                $completed_orders = $this->RiderOrder->getCompletedOrders($user_id,$starting_point);

                $total_earnings = $this->RiderOrder->getRiderEarningsCompletedOrders($user_id);
                $total_orders_amount_count = $total_earnings[0]['total_sum'];

                $user_details =  $this->User->getUserDetailsFromID($user_id);


                $rider_fee_type_per_order = $user_details['User']['rider_fee_type_per_order'];
                $rider_comission = $user_details['User']['rider_comission'];
                if($rider_fee_type_per_order < 1){

                    $earning = $total_orders_amount_count/100*$rider_comission;
                }else{

                    $earning =  $completed_orders_count*$rider_comission;

                }
                $output['PendingOrders'] = $pending_orders;
                $output['CompletedOrders'] = $completed_orders;
                $output['ActiveOrders']  = $active_orders;
                $output['stats']['completed_orders_count']  = $completed_orders_count;
                $output['stats']['total_earning']  = round($earning);
            }





















            echo json_encode($output);

            die();












        }
    }

    public function showRiderOrderDetails()
    {

        $this->loadModel("RiderOrder");
        $this->loadModel("Vehicle");

        $this->loadModel("OrderTransaction");
        $this->loadModel("OrderNotification");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $order_id = $data['order_id'];
            $user_id = $data['user_id'];





            $rider_order = $this->RiderOrder->getRiderOrderAgainstOrderAndUserID($order_id,$user_id);

           // $order_notification = $this->OrderNotification->getUserUnReadNotifications($user_id,$order_id);

          //  $rider_order['Order']['OrderNotification'] = $order_notification['OrderNotification'];
            if(count($rider_order) > 0) {

                $cart_details = $this->OrderTransaction->getTransactionAgainstOrderID($rider_order['Order']['id']);
                if(count($cart_details) > 0) {
                    $type =  $cart_details['OrderTransaction']['type'];
                    if($type =="paypal"){

                        $cart_details['OrderTransaction']['type'] = "paypal";
                    }if($type =="cod"){


                        $cart_details['OrderTransaction']['type'] = "Cash on delivery";
                    }else{


                        $cart_details['OrderTransaction']['type'] = "Card";
                    }
                    $rider_order['Order']['OrderTransaction'] = $cart_details['OrderTransaction'];
                }else{


                    $rider_order['Order']['OrderTransaction'] = array();

                }

                $user_id = $rider_order['Rider']['id'];
                $vehicle = $this->Vehicle->getUserVehicle($user_id);


                if(count($vehicle) > 0) {

                    $rider_order['Rider']['Vehicle'] = $vehicle['Vehicle'];
                    $rider_order['Rider']['VehicleType'] = $vehicle['VehicleType'];


                    $output['code'] = 200;

                    $output['msg'] = $rider_order;


                    echo json_encode($output);

                    die();
                }else{


                    Message::EmptyDATA();
                    die();

                }
            }else{

                Message::EmptyDATA();
                die();

            }











        }
    }

    public function readNotification()
    {

        $this->loadModel("OrderNotification");
        $this->loadModel("User");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $user_id =  $data['user_id'];
            $order_id =  $data['order_id'];


            $details  = $this->OrderNotification->getUserUnReadNotifications($user_id,$order_id);

            if($details > 0) {

                $this->OrderNotification->readNotification($user_id);


                $output['code'] = 200;
                $output['msg'] = "success";
                echo json_encode($output);


                die();
            }else{


                $output['code'] = 201;
                $output['msg'] = "no notification exist";
                echo json_encode($output);


                die();

            }




        }
    }
    public function showUserOrders()
    {

        $this->loadModel("Order");
        $this->loadModel("RiderOrder");
        $this->loadModel("DriverRating");



        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = $data['user_id'];

            $starting_point = $data['starting_point'];


            if(isset($data['type'])) {
                $type = $data['type'];

                if ($type == "pending") {

                    $orders = $this->Order->getUserOrdersAccordingToStatus($user_id, 0, $starting_point);
                } else if ($type == "active") {
                    $orders = $this->Order->getUserOrdersAccordingToStatus($user_id, 1, $starting_point);


                } else if ($type == "completed") {

                    $orders = $this->Order->getUserOrdersAccordingToStatus($user_id, 2, $starting_point);


                }
            }else {

                $orders = $this->Order->getUserOrders($user_id, $starting_point);

            }

            if (count($orders) > 0) {
                foreach ($orders as $key => $order) {

                    $order_id = $order['Order']['id'];
                    $rider_order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($order_id);

                    if (count($rider_order_detail) > 0) {

                        $orders[$key]['Order']['RiderOrder'] = $rider_order_detail['RiderOrder'];
                        $orders[$key]['Order']['RiderOrder']['Rider'] = $rider_order_detail['Rider'];
                        /* $ratings = $this->DriverRating->getRatingsAgainstOrder($order_id);
                         if (count($ratings) > 0) {
                             $orders[$key]['Order']['RiderOrder']['Rider']['rating'] = $ratings[0]['average'];
                         } else {

                             $orders[$key]['Order']['RiderOrder']['Rider']['rating'] = 0;
                         }*/

                    } else {

                        $orders[$key]['Order']['RiderOrder']['id'] = 0;
                        $orders[$key]['Order']['RiderOrder']['Rider'] ['id'] = 0;
                    }
                }
            }





            if(count($orders) > 0) {
                $output['code'] = 200;

                $output['msg'] = $orders;


                echo json_encode($output);


                die();
            }else{

                Message::EmptyDATA();
                die();
            }
        }
    }



    public function addOrderSession()
    {

        $this->loadModel("OrderSession");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $string = $json;

            $created = date('Y-m-d H:i:s', time());


            if(isset( $data['string'])){

                $string = $data['string'];
            }



            $session['user_id'] = $user_id;
            $session['string']     = $string;
            $session['created']    = $created;


            $details = $this->OrderSession->getAll();
            if(count($details) > 0){

                foreach($details as $detail) {

                    $datetime1 = new DateTime($created);
                    $datetime2 = new DateTime($detail['OrderSession']['created']);
                    $interval = $datetime1->diff($datetime2);
                    $minutes = $interval->format('%i');
                    $id = $detail['OrderSession']['id'];
                    if ($minutes > 60) {

                        $this->OrderSession->delete($id);

                    }
                }

            }


            $this->OrderSession->save($session);
            $id = $this->OrderSession->getInsertID();
            $details =   $this->OrderSession->getDetails($id);

            $output['code'] = 200;

            $output['msg'] = $details;
            echo json_encode($output);

            die();


        }
    }

    public function showOrderSession()
    {

        $this->loadModel("OrderSession");
        $this->loadModel("User");
        $this->loadModel("PaymentCard");
        $this->loadModel("StripeCustomer");
        $this->loadModel("Country");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->OrderSession->getDetails($id);
            if(count($details) > 0) {

                $user_id = $details['OrderSession']['user_id'];

                $userDetail = $this->User->getUserDetailsFromID($user_id);

                $currency = $this->Country->getDefaultCountry();





                $count = $this->PaymentCard->isUserStripeCustIDExist($user_id);

                if ($count > 0) {

                    $cards = $this->PaymentCard->getUserCards($user_id);




                    $j = 0;
                    if(count($cards) > 0) {

                        foreach ($cards as $card) {

                            $response[$j]['Stripe'] = $this->StripeCustomer->getCardDetails($card['PaymentCard']['stripe']);
                            $response[$j]['PaymentCard']['id'] = $card['PaymentCard']['id'];
                            $j++;
                        }


                        $i = 0;
                        foreach ($response as $re) {

                            $stripeCustomer = $re['Stripe'][0]['StripeCustomer']['sources']['data'][0];
                            /* $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['CardDetails']['last4'] = $stripeCustomer['last4'];
                            $stripData[$i]['CardDetails']['name'] = $stripeCustomer['name'];*/
                            $stripData[$i]['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['last4'] = $stripeCustomer['last4'];
                            $stripData[$i]['name'] = $stripeCustomer['name'];
                            $stripData[$i]['exp_month'] = $stripeCustomer['exp_month'];
                            $stripData[$i]['exp_year'] = $stripeCustomer['exp_year'];
                            $stripData[$i]['PaymentCard']['id'] = $re['PaymentCard']['id'];

                            $i++;
                        }

                    }else{

                        $output['code'] = 201;

                        $output['msg'] = "error";
                        echo json_encode($output);


                        die();

                    }

                }else{



                    $output['code'] = 200;

                    $output['msg'] = $userDetail;
                    $output['msg']['OrderSession'] = $details['OrderSession'];
                    $output['msg']['Country'] = $currency['Country'];
                    echo json_encode($output);
                    die();

                }



                $userDetail['User']['Cards'] = $stripData;





                $output['code'] = 200;

                $output['msg']['OrderSession'] = $details['OrderSession'];
                $output['msg']['UserDetail'] = $userDetail;
                $output['msg']['Country'] = $currency['Country'];
                echo json_encode($output);


                die();
            }else{

                Message::EmptyDATA();
                die();

            }

        }
    }


    public function addVehicle(){


        $this->loadModel('Vehicle');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user['user_id'] = $data['user_id'];
            $user['vehicle_type_id'] = $data['vehicle_type_id'];
            $user['make'] = $data['make'];
            $user['model'] = $data['model'];
            $user['year'] = $data['year'];
            $user['license_plate'] = $data['license_plate'];
            $user['color'] = $data['color'];


            $vehicle_details = $this->Vehicle->getUserVehicle($data['user_id']);

            if (count($vehicle_details) > 0) {

                if (isset($data['image'])) {




                    $image_db = $vehicle_details['Vehicle']['image'];
                    if (strlen($image_db) > 5) {
                        @unlink($image_db);

                    }

                    $image = $data['image'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder($data['user_id'], $image, $folder_url);
                    $user['image'] = $filePath;

                }

                $user['updated'] = date('Y-m-d H:i:s', time());
                $this->Vehicle->id = $vehicle_details['Vehicle']['id'];
                if (!$this->Vehicle->save($user)) {
                    echo Message::DATASAVEERROR();
                    die();
                }


                $vehicle_details = $this->Vehicle->getDetails($vehicle_details['Vehicle']['id']);


            }else{

                $user['created'] = date('Y-m-d H:i:s', time());

                $this->Vehicle->save($user);
                $id = $this->Vehicle->getInsertID();
                $vehicle_details = $this->Vehicle->getDetails($id);

            }

            $output['code'] = 200;

            $output['msg'] = $vehicle_details;


            echo json_encode($output);


            die();


        }




    }

    public function showVehicle(){

        $this->loadModel('Vehicle');





        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];

            $vehicles = $this->Vehicle->getUserVehicle($user_id);






            if(count($vehicles) > 0) {


                $output['code'] = 200;

                $output['msg'] = $vehicles;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();


            }

        }


    }

    public function showVehicleTypes(){

        $this->loadModel('VehicleType');





        if ($this->request->isPost()) {



            $types = $this->VehicleType->getAll();











            $output['code'] = 200;

            $output['msg'] = $types;


            echo json_encode($output);


            die();


        }


    }

    public function addDocument(){


        $this->loadModel('UserDocument');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $extension = $data['extension'];
            $type = $data['type'];
            $document['user_id'] = $data['user_id'];
            $document['type'] = $data['type'];


            $details = $this->UserDocument->getUserDocumentAgainstType($user_id,$type);

            if (count($details) > 0) {
                $document['status'] = 0;
                if (isset($data['attachment'])) {


                    $image_db = $details['UserDocument']['attachment'];
                    if (strlen($image_db) > 5) {
                        @unlink($image_db);

                    }

                    $image = $data['attachment'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder($user_id, $image, $folder_url, $extension);
                    $document['attachment'] = $filePath;


                    $document['updated'] = date('Y-m-d H:i:s', time());
                    $this->UserDocument->id = $details['UserDocument']['id'];
                    if (!$this->UserDocument->save($document)) {
                        echo Message::DATASAVEERROR();
                        die();
                    }


                    $details = $this->UserDocument->getDetails($details['UserDocument']['id']);

                    $output['code'] = 200;

                    $output['msg'] = $details;


                    echo json_encode($output);


                    die();
                }else{



                    $output['code'] = 201;

                    $output['msg'] = "missing attachment";


                    echo json_encode($output);


                    die();
                }
            }else {


                if (isset($data['attachment'])) {


                    $image = $data['attachment'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder($user_id, $image, $folder_url, $extension);
                    $document['attachment'] = $filePath;


                    $document['created'] = date('Y-m-d H:i:s', time());


                    $this->UserDocument->save($document);
                    $id = $this->UserDocument->getInsertID();
                    $details = $this->UserDocument->getDetails($id);


                    $output['code'] = 200;

                    $output['msg'] = $details;


                    echo json_encode($output);


                    die();
                }else{

                    $output['code'] = 201;

                    $output['msg'] = "missing attachment";


                    echo json_encode($output);


                    die();
                }
            }

        }




    }

    public function showUserDocuments(){

        $this->loadModel('UserDocument');





        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $details = $this->UserDocument->getUserDocument($user_id);



            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }




    public function online()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user['online'] = $data['online'];




            $user_id = $data['user_id'];


            $this->User->id = $user_id;
            $this->User->save($user);


            $output = array();
            $userDetails = $this->User->getUserDetailsFromID($user_id);


            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


        }
    }


    public function updateDriverOnlineStatus(){

        $this->loadModel('User');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $online = $data['online'];
            $created = date('Y-m-d H:i:s', time());



            $driver['id'] = $user_id;
            $driver['online'] = $online;


            $driver_details = $this->User->getUserDetailsFromIDAndRole($user_id,"driver");


            if(count($driver_details) > 0) {
                $driver['created'] = $created;
                $id =  $driver_details['User']['id'];
                $this->User->id = $id;
                $this->User->save($driver);

                $driver_details = $this->User->getUserDetailsFromID($id);
                $output['code'] = 200;

                $output['msg'] = $driver_details;


                echo json_encode($output);


                die();

            }else{

                $driver['updated'] = $created;
                $this->User->save($driver);
                $id = $this->User->getInsertID();
                $driver_details = $this->User->getUserDetailsFromID($id);
                $output['code'] = 200;

                $output['msg'] = $driver_details;


                echo json_encode($output);


                die();


            }

        }


    }




    public function showVehicles(){

        $this->loadModel('Vehicle');
        $this->loadModel('Request');
        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $lat = $data['lat'];
            $long = $data['long'];
            $user_id = $data['user_id'];
            $ride_type_id = $data['ride_type_id'];



            $vehicles = $this->Vehicle->getNearestVehicle($lat,$long,$ride_type_id,DISTANCE_DRIVER_IN_KM);




            if(count($vehicles) > 0) {

                foreach ($vehicles as $key=>$val) {

                    $vehicle_id =  $val['Vehicle']['id'];
                    $date = date('Y-m-d', time());
                    $request_detail = $this->Request->getRequestDetailCurrentDate($vehicle_id,$user_id,$date);
                    if(count($request_detail) > 0){

                        unset($vehicles[$key]);

                    }
                }
                $output['code'] = 200;

                $output['msg'] = $vehicles;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();

            }
        }




    }











    function deductPayment($payment_id,$total)
    {

        $this->loadModel('PaymentCard');
        $this->loadModel('StripeCharge');




        // $expense =  $order_gig_post[0]['OrderGigPost']['extra_expense_seller'];
        $this->PaymentCard->id = $payment_id;
        $stripe_cust_id  = $this->PaymentCard->field('stripe');


        if (strlen($stripe_cust_id) > 1) {



            $a = array(
                'customer' => $stripe_cust_id,
                'currency' => STRIPE_CURRENCY,

                'amount' => $total * 100
            );



            $result = $this->StripeCharge->save($a);
            if (!$result) {

                $error          = $this->StripeCharge->getStripeError();
                $output['code'] = 201;

                $output['msg'] = $error;
                return $output;
                die();
            } else {
                return $result['StripeCharge']['id'];
            }


        } else {
            $output['code'] = 201;

            $output['msg'] = "Please add a card first";
            return $output;
            die();


        }

    }


    public function showUserDetail()
    {

        $this->loadModel("User");
        $this->loadModel("PaymentCard");
        $this->loadModel("StripeCustomer");
        $this->loadModel("Currency");




        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];


            $userDetail = $this->User->getUserDetailsFromID($user_id);

          //  $currency = $this->Currency->getCurrency();





            $count = $this->PaymentCard->isUserStripeCustIDExist($user_id);

            if ($count > 0) {

                $cards = $this->PaymentCard->getUserCards($user_id);




                $j = 0;
                if(count($cards) > 0) {

                    foreach ($cards as $card) {

                        $response[$j]['Stripe'] = $this->StripeCustomer->getCardDetails($card['PaymentCard']['stripe']);
                        $response[$j]['PaymentCard']['id'] = $card['PaymentCard']['id'];
                        $j++;
                    }


                    $i = 0;
                    foreach ($response as $re) {

                        $stripeCustomer = $re['Stripe'][0]['StripeCustomer']['sources']['data'][0];
                        /* $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['CardDetails']['last4'] = $stripeCustomer['last4'];
                        $stripData[$i]['CardDetails']['name'] = $stripeCustomer['name'];*/
                        $stripData[$i]['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['last4'] = $stripeCustomer['last4'];
                        $stripData[$i]['name'] = $stripeCustomer['name'];
                        $stripData[$i]['exp_month'] = $stripeCustomer['exp_month'];
                        $stripData[$i]['exp_year'] = $stripeCustomer['exp_year'];
                        $stripData[$i]['PaymentCard']['id'] = $re['PaymentCard']['id'];

                        $i++;
                    }

                }else{

                    $output['code'] = 201;

                    $output['msg'] = "error";
                    echo json_encode($output);


                    die();

                }

            }else{



                $output['code'] = 200;

                $output['msg'] = $userDetail;
                echo json_encode($output);
                die();

            }



            $userDetail['User']['Cards'] = $stripData;



            $output['code'] = 200;

            $output['msg'] = $userDetail;
          //  $output['msg']['Currency'] = $currency['Currency'];
            echo json_encode($output);


            die();
        }
    }
    public function addPaymentCard()
    {

        $this->loadModel('StripeCustomer');
        $this->loadModel('PaymentCard');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $default = $data['default'];




            //$email = $data['email'];
            //$first_name = $data['first_name'];
            //$last_name = $data['last_name'];
            $name      = $data['name'];
            $card      = $data['card'];
            $cvc       = $data['cvc'];
            $exp_month = $data['exp_month'];
            $exp_year  = $data['exp_year'];
            // $address_line_1 = $data['street'];
            //$address_line_2 = $data['city'];
            // $address_zip = $data['zip'];
            //$address_state = $data['state'];
            //$address_country = $data['country'];

            if ($card != null && $cvc != null) {

                $a      = array(

                    // 'email' => $email,
                    'card' => array(
                        //'name' => $first_name . " " . $last_name,
                        'number' => $card,
                        'cvc' => $cvc,
                        'exp_month' => $exp_month,
                        'exp_year' => $exp_year,
                        'name' => $name

                        // 'address_line_1' => $address_line_1,
                        //'address_line_2' => $address_line_2,
                        //'address_zip' => $address_zip,
                        //'address_state' => $address_state,
                        //'address_country' => $address_country
                    )
                );
                $stripe = $this->StripeCustomer->save($a);



                if ($stripe) {





                    $payment['stripe']  = $stripe['StripeCustomer']['id'];
                    $payment['user_id'] = $user_id;
                    $payment['default'] = $default;
                    $result             = $this->PaymentCard->save($payment);
                    $count              = $this->PaymentCard->isUserStripeCustIDExist($user_id);
                    if ($count > 0) {

                        $cards = $this->PaymentCard->getUserCards($user_id);


                        foreach ($cards as $card) {

                            $response[] = $this->StripeCustomer->getCardDetails($card['PaymentCard']['stripe']);

                        }



                        $i = 0;
                        foreach ($response as $re) {

                            $stripeCustomer                        = $re[0]['StripeCustomer']['sources']['data'][0];
                            $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                            $stripData[$i]['CardDetails']['last4'] = $stripeCustomer['last4'];
                            $stripData[$i]['CardDetails']['name']  = $stripeCustomer['name'];

                            $i++;
                        }


                        $output['code'] = 200;
                        $output['msg']  = $stripData;
                        echo json_encode($output);
                        die();
                    } else {
                        Message::EmptyDATA();
                        die();
                    }




                } else {
                    $error['code'] = 400;
                    $error['msg']  = $this->StripeCustomer->getStripeError();
                    echo json_encode($error);
                }
            } else {
                echo Message::ERROR();



            }

        }

    }

    public function showPackageSize(){

        $this->loadModel('PackageSize');





        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $details =  $this->PackageSize->getDetails($data['id']);

            }else {


                $details = $this->PackageSize->getAll();

            }




            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }
        }


    }
    public function showPaymentDetails()
    {



        $this->loadModel('StripeCustomer');
        $this->loadModel('PaymentCard');


        if ($this->request->isPost()) {
            //$json = file_get_contents('php://input');
            $json    = file_get_contents('php://input');
            $data    = json_decode($json, TRUE);
            $user_id = $data['user_id'];
            if ($user_id != null) {

                $count = $this->PaymentCard->isUserStripeCustIDExist($user_id);

                if ($count > 0) {

                    $cards = $this->PaymentCard->getUserCards($user_id);

                    $j = 0;
                    foreach ($cards as $card) {

                        $response[$j]['Stripe']              = $this->StripeCustomer->getCardDetails($card['PaymentCard']['stripe']);
                        $response[$j]['PaymentCard']['id'] = $card['PaymentCard']['id'];
                        $j++;
                    }


                    $i = 0;
                    foreach ($response as $re) {

                        $stripeCustomer                       = $re['Stripe'][0]['StripeCustomer']['sources']['data'][0];

                        $stripData[$i]['brand']               = $stripeCustomer['brand'];
                        $stripData[$i]['brand']               = $stripeCustomer['brand'];
                        $stripData[$i]['last4']               = $stripeCustomer['last4'];
                        $stripData[$i]['name']                = $stripeCustomer['name'];
                        $stripData[$i]['exp_month']           = $stripeCustomer['exp_month'];
                        $stripData[$i]['exp_year']            = $stripeCustomer['exp_year'];
                        $stripData[$i]['PaymentCard']['id'] = $re['PaymentCard']['id'];

                        $i++;
                    }


                    $output['code'] = 200;
                    $output['msg']  = $stripData;
                    echo json_encode($output);
                    die();
                } else {
                    Message::EmptyDATA();
                    die();
                }

            } else {
                echo Message::ERROR();
            }
        }
    }







    public function deletePaymentCard()
    {

        $this->loadModel("PaymentCard");
        $this->loadModel("StripeCustomer");
        // $this->loadModel("RestaurantRating");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id      = $data['id'];
            $user_id = $data['user_id'];
            $this->PaymentCard->query('SET FOREIGN_KEY_CHECKS=0');
            if ($this->PaymentCard->delete($id)) {



                $count = $this->PaymentCard->isUserStripeCustIDExist($user_id);

                if ($count > 0) {

                    $cards = $this->PaymentCard->getUserCards($user_id);


                    foreach ($cards as $card) {

                        $response[] = $this->StripeCustomer->getCardDetails($card['PaymentCard']['stripe']);

                    }



                    $i = 0;
                    foreach ($response as $re) {

                        $stripeCustomer         = $re[0]['StripeCustomer']['sources']['data'][0];
                        /* $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['CardDetails']['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['CardDetails']['last4'] = $stripeCustomer['last4'];
                        $stripData[$i]['CardDetails']['name'] = $stripeCustomer['name'];*/
                        $stripData[$i]['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['brand'] = $stripeCustomer['brand'];
                        $stripData[$i]['last4'] = $stripeCustomer['last4'];
                        $stripData[$i]['name']  = $stripeCustomer['name'];

                        $i++;
                    }


                    $output['code'] = 200;
                    $output['msg']  = $stripData;
                    echo json_encode($output);
                    die();
                } else {
                    Message::EmptyDATA();
                    die();
                }
            } else {

                Message::ALREADYDELETED();
                die();

            }



        }
    }









    public function giveRatingsToDriver(){


        $this->loadModel('DriverRating');
        $this->loadModel('Order');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            //$trip['trip_id'] =
            // =  $data['trip_id'];

            $order_id = $data['order_id'];
            $rating['driver_id'] = $data['driver_id'];
            $rating['order_id'] = $order_id;
            $rating['user_id'] = $data['user_id'];
            $rating['star'] = $data['star'];
            $rating['comment'] = $data['comment'];
            $rating['created']=date('Y-m-d H:i:s', time());

            //  $order_detail = $this->Order->getDetails($order_id);
            $rating_exist = $this->DriverRating->ifRatingExist($order_id);

            if($rating_exist < 1) {

                $this->DriverRating->save($rating);
                $id = $this->DriverRating->getInsertID();
                $rating_details = $this->DriverRating->getDetails($id);
                $output['code'] = 200;

                $output['msg'] = $rating_details;


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "the rating has already been submitted";


                echo json_encode($output);


                die();

            }

        }




    }


    public function giveRatingsToUser(){


        $this->loadModel('UserRating');
        $this->loadModel('Trip');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $request_id = $data['request_id'];

            $rating['driver_id'] = $data['driver_id'];
            $rating['user_id'] = $data['user_id'];
            $rating['star'] = $data['star'];
            $rating['comment'] = $data['comment'];
            $rating['created']=date('Y-m-d H:i:s', time());
            $trip_detail = $this->Trip->getTripAgainstRequest($request_id);
            $rating['trip_id'] = $trip_detail['Trip']['id'];
            $rating_exist = $this->UserRating->ifRatingExist( $trip_detail['Trip']['id']);
            if($rating_exist < 1) {

                $this->UserRating->save($rating);
                $id = $this->UserRating->getInsertID();
                $rating_details = $this->UserRating->getDetails($id);
                $output['code'] = 200;

                $output['msg'] = $rating_details;


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "the rating has already been submitted";


                echo json_encode($output);


                die();
            }

        }




    }



   

    function makeTripMap($pickup_lat,$pickup_long,$destination_lat,$destination_long,$user_id){





        $url = "https://maps.googleapis.com/maps/api/staticmap?path=color:black|weight:5|&size=280x280&key=" . GOOGLE_MAPS_KEY . "&markers=color:green|label:S|" . $pickup_lat . "," . $pickup_long . "&markers=color:red|label:E|" . $destination_lat . "," . $destination_long;

        $folder_url = UPLOADS_FOLDER_URI;

        $file_path = Utility::uploadMapImageintoFolder($user_id, $url, $folder_url);



            return $file_path;


    }

    public function addUserLatLong(){


        $this->loadModel('User');

        //$this->loadModel('RiderOrderLocationHistory');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            //$trip['trip_id'] =

            $loc['lat'] = $data['lat'];
            $loc['long'] = $data['long'];
            $loc['created']=date('Y-m-d H:i:s', time());


            if(isset($data['user_id'])) {

                $user_id =  $data['user_id'];
                $details = $this->User->getUserDetailsFromID($user_id);

                if(count($details) > 0) {
                    $this->User->id = $user_id;
                    $this->User->save($loc);
                    $user_details =  $this->User->getUserDetailsFromID($user_id);

                    $output['code'] = 200;

                    $output['msg'] = $user_details;


                    echo json_encode($output);


                    die();

                }else{

                    Message::EMPTYDATA();
                    die();
                }


            }else  if(isset($data['order_id'])) {


              //  $loc['order_id'] = $data['order_id'];



                //$this->RiderOrderLocationHistory->save($loc);


                $output['code'] = 200;

                $output['msg'] = "success";


                echo json_encode($output);


                die();

            }

        }




    }


    public function addVehicleLatLong(){


        $this->loadModel('Vehicle');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            //$trip['trip_id'] =
            $vehicle_id =  $data['vehicle_id'];

            $user['lat'] = $data['lat'];
            $user['long'] = $data['long'];



            $vehicle_details =  $this->Vehicle->getDetails($vehicle_id);

            if(count($vehicle_details) > 0) {
                $this->Vehicle->id = $vehicle_id;
                $this->Vehicle->save($user);
                $vehicle_details =  $this->Vehicle->getDetails($vehicle_id);

                $output['code'] = 200;

                $output['msg'] = $vehicle_details;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }

        }




    }





    public function showSettings(){

        $this->loadModel('Setting');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $setting_detail = $this->Setting->getAll();


            if(count($setting_detail) > 0){



                $output['code'] = 200;

                $output['msg'] = $setting_detail;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }

        }


    }






    public function showNotifications(){

        $this->loadModel('Notification');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $notifications = $this->Notification->getUserNotifications($user_id);


            if(count($notifications) > 0){



                $output['code'] = 200;

                $output['msg'] = $notifications;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }

        }


    }


    public function showActiveRequest(){

        $this->loadModel('Request');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $trip_detail = $this->Request->getActiveRequest($user_id);


            if(count($trip_detail) > 0){


                $trip_detail['Request']['final_fare'] = 40;
                $output['code'] = 200;

                $output['msg'] = $trip_detail;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }

        }


    }

    public function showRideTypes(){

        $this->loadModel('RideType');
        $this->loadModel('Setting');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $rideTypes = $this->RideType->getAll();
            if (count($rideTypes) > 0) {


                if(isset($data['pickup_lat'])) {
                    $pickup_lat = $data['pickup_lat'];
                    $pickup_long = $data['pickup_long'];
                    $destination_lat = $data['destination_lat'];
                    $destination_long = $data['destination_long'];


                    $setting_details = $this->Setting->getSingleSettingsAgainstType("distance_unit");
                    $distance_unit = $setting_details['Setting']['value'];

                    $distance = Utility::getDurationTimeBetweenTwoDistances($pickup_lat, $pickup_long, $destination_lat, $destination_long);
                    $ride_distance_in_meters = $distance['rows']['0']['elements'][0]['distance']['value'];
                    $ride_duration_in_seconds = $distance['rows']['0']['elements'][0]['duration']['value'];







                    foreach ($rideTypes as $key => $val) {


                        $base_fare = $val['RideType']['base_fare'];
                        $cost_per_minute = $val['RideType']['cost_per_minute'];
                        $cost_per_distance = $val['RideType']['cost_per_distance'];
                        $estimated = Utility::calculateFare($base_fare, $cost_per_minute, $cost_per_distance, $ride_duration_in_seconds, $ride_distance_in_meters, "0", $distance_unit);
                        $rideTypes[$key]['RideType']['estimated_fare'] = $estimated['fare'];
                        $rideTypes[$key]['RideType']['time'] = $estimated['time'];


                    }

                }

                $output['code'] = 200;

                $output['msg'] = $rideTypes;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();
            }



        }
    }


    function forgotPassword()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $result = array();
            $json   = file_get_contents('php://input');

            $data = json_decode($json, TRUE);


            $email     = $data['email'];



            $code     = Utility::randomNumber(4);
            $user_info = $this->User->getUserDetailsAgainstEmail($email);
            if(APP_STATUS == "demo"){

                $output['code'] = 200;
                $output['msg']  = "disabled because of demo";
                echo json_encode($output);
                die();

            }

            if (count($user_info) > 0) {



                $user_id = $user_info['User']['id'];
                $email   = $user_info['User']['email'];
                $first_name   = $user_info['User']['first_name'];
                $last_name   = $user_info['User']['last_name'];
                $full_name   = $first_name. ' '.$last_name;

                $email_data['to'] = $email;
                $email_data['name'] = $full_name;
                $email_data['subject'] = "reset your password";
                $email_data['message'] = "You recently requested to reset your password for your ".APP_NAME." account  with the e-mail address (".$email."). 
Please enter this verification code to reset your password.<br><br>Confirmation code: <b></b>".$code."<b>";
                $response = Utility::sendMail($email_data);


                //  $response['ErrorCode']  = 0;
                if ($response['ErrorCode'] < 1) {

                    $this->User->id = $user_id;

                    $savedField     = $this->User->saveField('token', $code);
                    $result['code'] = 200;
                    $result['msg']  = "An email has been sent to " . $email . ". You should receive it shortly.";
                } else {

                    $result['code'] = 201;
                    $result['msg']  = "Email is not sending. Seems like you have not configured postmark correctly";


                }

            } else {

                $result['code'] = 201;
                $result['msg']  = "Email doesn't exist";
            }



            echo json_encode($result);
            die();
        }


    }

    function resetPassword()
    {

        $this->loadModel('User');

        if ($this->request->isPost()) {


            $result = array();
            $json   = file_get_contents('php://input');

            $data = json_decode($json, TRUE);


            $email     = $data['email'];
            $role     = $data['role'];
            // $user_info = $this->User->findByEmail($email);


            $password     = Lib::getToken(6);
            $user_info = $this->User->findEmail($email,$role);
            if(APP_STATUS == "demo"){

                $output['code'] = 201;
                $output['msg']  = "disabled because of demo";
                echo json_encode($output);
                die();

            }

            if (count($user_info) > 0) {


                $user_id = $user_info[0]['User']['id'];
                $email   = $user_info[0]['User']['email'];
                $first_name   = $user_info[0]['User']['first_name'];
                $last_name   = $user_info[0]['User']['last_name'];
                $full_name   = $first_name. ' '.$last_name;

                $email_data['to'] = $email;
                $email_data['name'] = $full_name;
                $email_data['subject'] = "reset your password";
                $email_data['message'] = "You recently requested to reset your password for your ".APP_NAME." account. Your new password is ".$password;
                $response = Utility::sendMail($email_data);


                //  $response['ErrorCode']  = 0;
                if ($response['ErrorCode'] < 1) {

                    $this->User->id = $user_id;

                    $this->User->saveField('password', $password);
                    $result['code'] = 200;
                    $result['msg']  = "New Password has been sent to your " . $email . ". You should receive it shortly.".$password;
                } else {

                    $result['code'] = 201;
                    $result['msg']  = "invalid email";


                }

            } else {

                $result['code'] = 201;
                $result['msg']  = "Email doesn't exist";
            }



            echo json_encode($result);
            die();
        }


    }

    public function verifyforgotPasswordCode()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);
            $code = $data['code'];
            $email = $data['email'];

            $code_verify = $this->User->verifyToken($code,$email);
            $user_info = $this->User->getUserDetailsFromEmail($email);
            if (!empty($code_verify)) {
                $this->User->id = $user_info['User']['id'];
                $this->User->saveField('token',0);

                $user_info = $this->User->getUserDetailsFromEmail($email);
                $result['code'] = 200;
                $result['msg']  = $user_info;
                echo json_encode($result);
                die();
            } else {
                $result['code'] = 201;
                $result['msg']  = "invalid code";
                echo json_encode($result);
                die();
            }
        }
    }

    public function saveNewPassword()
    {
        $this->loadModel('User');
        if ($this->request->isPost()) {

            $password1                       = $this->request->data("pw1");
            $pw1                             = trim($password1);
            $password2                       = $this->request->data("pw2");
            $email                           = $this->request->data("email");
            $user_info                       = $this->User->findByEmail($email);
            $this->User->id                  = $user_info['User']['id'];
            $this->request->data['password'] = $pw1;
            $this->request->data['token']    = 0;
            if ($this->User->save($this->request->data)) {


                echo "success";
            }
        }
    }

    public function changeEmailAddress()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $email        = $data['email'];


            $email_exist = $this->User->editIsEmailAlreadyExist($email, $user_id);

            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_email = $user_details['User']['email'];

                if ($db_email == $email) {


                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                }

                if ($email_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This email has already been registered";
                    echo json_encode($result);
                    die();
                }


                $code = Utility::randomNumber(4);


                $user_id = $user_details['User']['id'];
                $first_name = $user_details['User']['first_name'];
                $last_name = $user_details['User']['last_name'];
                $full_name = $first_name . ' ' . $last_name;

                $email_data['to'] = $email;
                $email_data['name'] = $full_name;
                $email_data['subject'] = "change your email address";
                $email_data['message'] = "You recently requested to update your email for your " . APP_NAME . " account. 
Please enter this verification code to reset your email.<br><br>Confirmation code: <b></b>" . $code . "<b>";
                $response = Utility::sendMail($email_data);


                //  $response['ErrorCode']  = 0;
                if ($response['code'] == 200) {

                    $this->User->id = $user_id;

                    $savedField = $this->User->saveField('token', $code);
                    $result['code'] = 200;
                    $result['msg'] = "An email has been sent to " . $email . ". You should receive it shortly.";
                } else {

                    $result['code'] = 201;
                    $result['msg'] = $response['msg'];


                }

                echo json_encode($result);
                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }

    }

    public function verifyChangeEmailCode()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);
            $code = $data['code'];
            $email = $data['new_email'];
            $user_id = $data['user_id'];
            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_email = $user_details['User']['email'];
                $code_verify = $this->User->verifyToken($code, $db_email);

                if (!empty($code_verify) && $code > 0) {
                    $email_change['email'] = $email;
                    $email_change['token'] = 0;
                    $this->User->id = $user_id;
                    $this->User->save($email_change);

                    $user_details = $this->User->getUserDetailsFromEmail($email);
                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                } else {
                    $result['code'] = 201;
                    $result['msg'] = "invalid code";
                    echo json_encode($result);
                    die();
                }
            }else{

                $result['code'] = 201;
                $result['msg'] = "invalid code";
                echo json_encode($result);
                die();
            }

        }
    }

    public function changePhoneNo()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $phone        = $data['phone'];


            $phone_exist = $this->User->editIsphoneNoAlreadyExist($phone, $user_id);

            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_phone = $user_details['User']['phone'];

                if ($db_phone == $phone) {


                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                }

                if ($phone_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This phone has already been registered";
                    echo json_encode($result);
                    die();
                }



                $response =  $this->verifyPhoneNo($phone,$user_id,0);


                echo json_encode($response);
                die();
            }

        }

    }

    public function verifyPhoneNo($phone_no = null,$user_id = null,$verify = null)
    {

        $this->loadModel('PhoneNoVerification');
        $this->loadModel('User');



        $json = file_get_contents('php://input');

        $data = json_decode($json, TRUE);


        if (!empty($phone_no)) {
            $phone_no = $phone_no;
            $verify = $verify;

        }else{

            $phone_no =  $data['phone'];
            $verify =  $data['verify'];
            // $code =  $data['code'];

            if(isset($data['user_id'])) {
                $user_id = $data['user_id'];
            }
        }

        $phone_exist = $this->User->isphoneNoAlreadyExist($phone_no);




        if ($phone_exist > 0) {

            $result['code'] = 201;
            $result['msg'] = "This phone has already been registered";
            echo json_encode($result);
            die();
        }
        $code     = Utility::randomNumber(4);

        if(APP_STATUS =="demo"){
            $code     = 1234;
        }


        $created                  = date('Y-m-d H:i:s', time() - 60 * 60 * 4);
        $phone_verify['phone_no'] = $phone_no;
        $phone_verify['code']     = $code;
        $phone_verify['created']  = $created;


        if ($verify == 0) {

            if(APP_STATUS =="demo"){
                $response['sid']= "";
            }else{

                $response = Utility::sendSmsVerificationCurl($phone_no, VERIFICATION_PHONENO_MESSAGE . ' ' . $code);

            }





            if (array_key_exists('code', $response)){


                $output['code'] = 201;
                $output['msg']  = $response['message'];



            }else{



                if (array_key_exists('sid', $response)){



                    $this->PhoneNoVerification->save($phone_verify);


                    $output['code'] = 200;

                    $output['msg']  = "code has been generated and sent to user's phone number";



                }

            }





        } else {
            $code_user = $data['code'];
            if ($this->PhoneNoVerification->verifyCode($phone_no, $code_user) > 0) {

                if (!empty($user_id)) {


                    $this->User->id = $user_id;
                    $this->User->saveField('phone',$phone_no);
                }
                $output['code'] = 200;
                $output['msg']  = "successfully code matched";
                /*$this->PhoneNoVerification->deleteAll(array(
                    'phone_no' => $phone_no
                ), false);*/



            } else {

                $output['code'] = 201;
                $output['msg']  = "invalid code";



            }

        }

        if (!empty($phone)) {


            return $output;
        }else{


            //it means post request from app
            echo json_encode($output);
            die();

        }

    }

    public function changePassword()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);




            $user_id        = $data['user_id'];
            $this->User->id = $user_id;
            $email          = $this->User->field('email');

            if(APP_STATUS == "demo"){

                $output['code'] = 201;
                $output['msg'] = "You cannot change demo account information";
                echo json_encode($output);
                die();

            }
            $old_password   = $data['old_password'];
            $new_password   = $data['new_password'];


            if ($this->User->verifyPassword($email, $old_password)) {

                $this->request->data['password'] = $new_password;
                $this->User->id                  = $user_id;


                if ($this->User->save($this->request->data)) {

                    echo Message::DATASUCCESSFULLYSAVED();

                    die();
                } else {


                    echo Message::DATASAVEERROR();
                    die();


                }

            } else {

                echo Message::INCORRECTPASSWORD();
                die();

            }


        }

    }

    public function changePasswordForgot()
    {
        $this->loadModel('User');
        $this->loadModel('UserInfo');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $email        = $data['email'];

            $new_password   = $data['password'];


            if(APP_STATUS == "demo"){

                $output['code'] = 201;
                $output['msg'] = "You cannot change demo account information";
                echo json_encode($output);
                die();

            }

            $this->request->data['password'] = $new_password;

            $email_details = $this->User->getUserDetailsAgainstEmail($email);


            $user_id = $email_details['User']['id'];
            $this->User->id = $user_id;
            if ($this->User->save($this->request->data)) {

                $user_info = $this->User->getUserDetailsFromID($user_id);
                $result['code'] = 200;
                $result['msg']  = $user_info;
                echo json_encode($result);
                die();
            } else {


                echo Message::DATASAVEERROR();
                die();


            }

        } else {

            echo Message::INCORRECTPASSWORD();
            die();




        }

    }



    public function showLanguages(){

        $this->loadModel('Language');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $languages = $this->Language->getOnlyActiveLanguages();





            $output['code'] = 200;

            $output['msg'] = $languages;


            echo json_encode($output);


            die();


        }


    }
   
    public function sendMessageNotification()
    {
        $this->loadModel("User");

        $this->loadModel("Order");
        $this->loadModel("OrderNotification");
        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $sender_id = $data['sender_id'];
            $receiver_id = $data['receiver_id'];
            $message = $data['message'];
            $title = $data['title'];
            $type = $data['type'];




            $order_notification['sender_id'] = $sender_id;
            $order_notification['receiver_id'] = $receiver_id;

            if(isset($data['order_id'])) {

                $order_id = $data['order_id'];
                $order_notification['order_id'] = $order_id;
            }

            $this->OrderNotification->save($order_notification);

            $receiver_details =  $this->User->getUserDetailsFromID($receiver_id);
            $sender_details =  $this->User->getUserDetailsFromID($sender_id);





            /*********************************START NOTIFICATION******************************/

            $notification['to'] = $receiver_details['User']['device_token'];




            $notification['notification']['title'] = $title;
            $notification['notification']['body'] = $message;
            $notification['notification']['sender'] = $sender_details['User'];
            $notification['notification']['receiver'] = $receiver_details['User'];
            //$notification['notification']['image'] = $sender_details['User']['profile_pic'];
            // $notification['notification']['name'] = $sender_details['User']['username'];
            $notification['notification']['badge'] = "1";
            $notification['notification']['sound'] = "default";
            $notification['notification']['icon'] = "";
            $notification['notification']['type'] = $type;


            $notification['data']['title'] = $title;
            // $notification['data']['name'] = $sender_details['User']['username'];
            $notification['data']['body'] = $message;
            $notification['data']['icon'] = "";
            $notification['data']['badge'] = "1";
            $notification['data']['sound'] = "default";
            $notification['data']['type'] = $type;
            //$notification['data']['sender'] = $sender_details['User'];
           // $notification['data']['receiver'] = $receiver_details['User'];

            if(isset($data['order_id'])) {

                $order_id = $data['order_id'];

                $order_details = $this->Order->getDetails($order_id);


                if(count($order_details) > 0){

                    $notification['notification']['order']['id'] = $order_details['Order']['id'];
                    $notification['notification']['User']['id'] = $order_details['User']['id'];
                    $notification['notification']['User']['first_name'] = $order_details['User']['first_name'];
                    $notification['notification']['User']['last_name'] = $order_details['User']['last_name'];
                    $notification['notification']['User']['image'] = $order_details['User']['image'];

                    $notification['data']['order']['id'] = $order_details['Order']['id'];
                    $notification['data']['User']['id'] = $order_details['User']['id'];
                    $notification['data']['User']['first_name'] = $order_details['User']['first_name'];
                    $notification['data']['User']['last_name'] = $order_details['User']['last_name'];
                    $notification['data']['User']['image'] = $order_details['User']['image'];

                }

            }
            $result = Utility::sendPushNotificationToMobileDevice(json_encode($notification));


            /*********************************END NOTIFICATION******************************/







            $output['code'] = 200;
            $output['msg'] = $result;
            echo json_encode($output);


            die();
        }

    }











    public function searchUsers()
    {

        $this->loadModel("User");
        $this->loadModel("Friend");
        $this->loadModel("RoomMember");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $keyword = $data['keyword'];
            $user_id = $data['user_id'];

            if (strlen($keyword) > 0) {


                $users = $this->User->getSearchResults($keyword, $user_id);


                $output['code'] = 200;

                $output['msg'] = $users;


                echo json_encode($output);


                die();


            }

            Message::EMPTYDATA();
            die();


        }
    }






    public function showSettingsAgainstType()
    {


        $this->loadModel("Setting");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $types = $data['types'];





            if(count($types) > 0) {

                $type = array();
                foreach ($types as $key_fr => $val) {


                    $type[$key_fr] = $val['type'];


                }


                $setting_details = $this->Setting->getSettingsAgainstType($type);



                $output['code'] = 200;

                $output['msg'] = $setting_details;


                echo json_encode($output);


                die();

            }
        }
    }

    public function getCities()
    {


        $this->loadModel("City");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $country_id = $data['country_id'];


            $cities = $this->City->getCitiesAgainstCountry($country_id);



            $output['code'] = 200;

            $output['msg'] = $cities;


            echo json_encode($output);


            die();


        }
    }



    public function showUserDetails()
    {


        $this->loadModel("User");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];



            $userDetails = $this->User->getUserDetailsFromID($user_id);


            $output['code'] = 200;

            $output['msg'] = $userDetails;


            echo json_encode($output);


            die();


        }
    }





    public function showDriverDetails()
    {


        $this->loadModel("User");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];



            $userDetails = $this->User->getDriverDetails($user_id,"driver");


            $output['code'] = 200;

            $output['msg'] = $userDetails;


            echo json_encode($output);


            die();


        }
    }



}







?>