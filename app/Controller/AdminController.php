<?php

App::uses('Utility', 'Lib');
App::uses('Message', 'Lib');
class AdminController extends AppController
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


                if ($client_api_key != ADMIN_API_KEY) {

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
                return true;
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

    public function login() //changes done by irfan
    {
        $this->loadModel('Admin');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            // $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $email = strtolower($data['email']);
            $password = $data['password'];


            if ($email != null && $password != null) {
                $userData = $this->Admin->loginAllUsers($email, $password);

                if ($userData) {
                    $user_id = $userData[0]['Admin']['id'];

                    // $this->UserInfo->id = $user_id;
                    // $savedField = $this->UserInfo->saveField('device_token', $device_token);

                    $output = array();
                    $userDetails = $this->Admin->getUserDetailsFromID($user_id);

                    //CustomEmail::welcomeStudentEmail($email);
                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);


                } else {
                    echo Message::INVALIDDETAILS();
                    die();

                }


            } else {
                echo Message::ERROR();
                die();
            }
        }
    }







    public function registerRider()
    {


        $this->loadModel('User');
        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $email = $data['email'];
            $password = $data['password'];
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];

            $phone = $data['phone'];

            $city = $data['city'];
            $country = $data['country_id'];





            if ($email != null && $password != null) {


                $user['email'] = $email;
                $user['password'] = $password;

                $user['active'] = 1;
                $user['role'] = "rider";

                $user['first_name'] = $first_name;

                $user['last_name'] = $last_name;
                $user['phone'] = $phone;
                $user['city'] = $city;
                $user['country_id'] = $country;
                $user['rider_comission'] = $data['rider_comission'];
                $user['created'] =  date('Y-m-d H:i:s', time());


                $count = $this->User->isEmailAlreadyExist($email);


                if ($count && $count > 0) {
                    echo Message::DATAALREADYEXIST();
                    die();

                } else {


                    if (!$this->User->save($user)) {
                        echo Message::DATASAVEERROR();
                        die();
                    }


                    $user_id = $this->User->getInsertID();



                    $output = array();
                    $userDetails = $this->User->getUserDetailsFromID($user_id);


                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);


                }
            } else {
                echo Message::ERROR();
            }
        }
    }

    public function showWithdrawRequest()
    {

        $this->loadModel("WithdrawRequest");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            if(isset($data['user_id'])) {
                $user_id = $data['user_id'];


                $requests = $this->WithdrawRequest->getUserPendingWithdrawRequest($user_id);

            }else if(isset($data['id'])) {
                $requests = $this->WithdrawRequest->getDetails($data['id']);

            }else{
                $requests = $this->WithdrawRequest->getAllPendingRequests(0);
            }






            $output['code'] = 200;

            $output['msg'] = $requests;


            echo json_encode($output);


            die();


        }
    }

    public function withdrawRequestApproval()
    {


        $this->loadModel("WithdrawRequest");

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $withdraw_data['status'] = $data['status'];

            $withdraw_data['updated'] = date('Y-m-d H:i:s', time());


            $details = $this->WithdrawRequest->getDetails($id);

            if(count($details) > 0) {

                if($data['status'] == 1){
                    $this->User->id = $details['WithdrawRequest']['user_id'];
                    $user_wallet['wallet'] = 0;
                    $user_wallet['reset_wallet_datetime'] = date('Y-m-d H:i:s', time());
                    $this->User->save($user_wallet);
                


                }

                $this->WithdrawRequest->id = $id;
                $this->WithdrawRequest->save($withdraw_data);


                $output = array();
                $details = $this->WithdrawRequest->getDetails($id);


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


    public function showUsers(){

        $this->loadModel('User');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            if(isset($data['role'])){
                $role =  $data['role'];
                $users = $this->User->getUsers($role);

            } else  if(isset($data['user_id'])) {
                $users = $this->User->getUserDetailsFromID($data['user_id']);
            }else{
                $users = $this->User->getAllUsers();

            }







            $output['code'] = 200;

            $output['msg'] = $users;


            echo json_encode($output);


            die();


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

            //$trip['trip_id'] =
            $order['user_id'] =  $data['user_id'];
            $order['good_type_id'] =  $data['good_type_id'];
            $order['delivery_type_id'] =  $data['delivery_type_id'];
            $order['coupon_id'] =  $data['coupon_id'];
            $order['cod'] =  $data['cod'];
            $order['discount'] =  $data['discount'];
            $order['price'] =  $data['price'];
            $order['total'] =  $data['total'];
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

            $order['receiver_location_lat'] = $data['receiver_location_lat'];
            $order['receiver_location_long'] = $data['receiver_location_long'];
            $order['receiver_location_string'] = $data['receiver_location_string'];





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
    public function showOrders(){

        $this->loadModel('Order');
        $this->loadModel('RiderOrder');





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            //$orders = $this->Order->getAll();
            if(isset($data['status'])) {
                $status = $data['status'];
                $orders = $this->Order->getOrdersAccordingToStatus($status);
            }else if(isset($data['order_id'])){

                $orders = $this->Order->getDetails($data['order_id']);

                $rider_order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($orders['Order']['id']);

                if (count($rider_order_detail) > 0) {

                    $orders['Order']['RiderOrder'] = $rider_order_detail['RiderOrder'];
                    $orders['Order']['RiderOrder']['Rider'] = $rider_order_detail['Rider'];
                }

                $output['code'] = 200;

                $output['msg'] = $orders;


                echo json_encode($output);


                die();
            }else{

                $orders = $this->Order->getAll();
            }

            if(count($orders) > 0 ) {


                foreach ($orders as $key => $order) {
                    $rider_order_detail = $this->RiderOrder->getRiderOrderAgainstOrderID($order['Order']['id']);

                    if (count($rider_order_detail) > 0) {

                        $orders[$key]['Order']['RiderOrder'] = $rider_order_detail['RiderOrder'];
                        $orders[$key]['Order']['RiderOrder']['Rider'] = $rider_order_detail['Rider'];
                    }
                }
            }








            if(count($orders) > 0) {

               
                $output['code'] = 200;

                $output['msg'] = $orders;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();

            }

        }


    }

    public function showUserOrders()
    {

        $this->loadModel("Order");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = $data['user_id'];
            $status = $data['status'];



            //$pending_orders = $this->Order->getUserOrdersAccordingToStatus($user_id, 0);

            //$active_orders = $this->Order->getUserOrdersAccordingToStatus($user_id,1);
            //$completed_orders = $this->Order->getUserOrdersAccordingToStatus($user_id,2);
            $orders = $this->Order->getUserOrdersAccordingToStatus($user_id,$status);
            //$types = $this->Order->getUserOrders($user_id);



            $output['code'] = 200;

            $output['msg'] = $orders;




            echo json_encode($output);


            die();

        }
    }
    public function adminResponseAgainstOrder()
    {


        $this->loadModel('Order');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $order_id = $data['order_id'];
            $status = $data['status'];






            $this->Order->id = $order_id;
            $this->Order->saveField('status',$status);


            $output = array();
            $userDetails = $this->Order->getDetails($order_id);


            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


        }
    }


    public function assignOrderToRider()
    {

        $this->loadModel("RiderOrder");
        $this->loadModel("Vehicle");
        $this->loadModel("Order");
        $this->loadModel("User");


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $rider_user_id = $data['rider_user_id'];

            $order_id = $data['order_id'];
            $created = date('Y-m-d H:i:s', time());





            if(isset($data['id'])){

                $this->RiderOrder->delete($data['id']);

            }
            $rider_details = $this->User->getUserDetailsFromID($rider_user_id);

            if(count($rider_details) > 0){


                $active = $rider_details['User']['active'];

                if($active > 1){

                    $output['code'] = 200;

                    $output['msg'] = "You cannot assign this order to this rider because you have not approved this rider yet";
                    echo json_encode($output);


                    die();
                }
            }

            $rider_order['rider_user_id'] = $rider_user_id;

            $rider_order['order_id'] = $order_id;
            $rider_order['assign_date_time'] = $created;



            $vehicle = $this->Vehicle->getUserVehicle($rider_user_id);



                    if(count($vehicle) < 1) {

                        $output['code'] = 201;

                        $output['msg'] = "You cannot assign this order to this rider because vehicle has not been added in this rider account";
                        echo json_encode($output);


                        die();

                    }


             $rider_order_rejected = $this->RiderOrder->checkIfAnyOrderWhichIsAlreadyAssigned($order_id);

            if ($this->RiderOrder->isDuplicateRecord($rider_user_id, $order_id) <= 0 && $rider_order_rejected < 1) {



                if ($this->RiderOrder->save($rider_order)) {

                    $rider_order_id = $this->RiderOrder->getInsertID();
                    $details = $this->RiderOrder->getDetails($rider_order_id);

                    $msg = "You have received the new order request";
                    $notification['to'] = $details['Rider']['device_token'];
                    $notification['notification']['title'] = $msg;
                    $notification['notification']['body'] = "";
                    $notification['notification']['badge'] = "1";
                    $notification['notification']['sound'] = "default";
                    $notification['notification']['icon'] = "";
                    $notification['notification']['type'] = "";
                    $notification['data']['title'] = $msg;
                    $notification['data']['body'] = '';
                    $notification['data']['icon'] = "";
                    $notification['data']['badge'] = "1";
                    $notification['data']['sound'] = "default";
                    $notification['data']['type'] = "";
                    Utility::sendPushNotificationToMobileDevice(json_encode($notification));

                    $this->Order->id = $order_id;
                    $this->Order->saveField('status',4);



                    $output['code'] = 200;

                    $output['msg'] = $details;
                    echo json_encode($output);


                    die();

                } else {


                    echo Message::DUPLICATEDATE();
                    die();
                }

            }else{

                echo Message::DUPLICATEDATE();
                die();

            }
        }
    }

    public function cancelOrder()
    {

        $this->loadModel("RiderOrder");
        $this->loadModel("Order");

        if ($this->request->isPost()) {
            $json     = file_get_contents('php://input');
            $data     = json_decode($json, TRUE);
            $id = $data['id'];
            $rider_order['admin_response']   = $data['response'];
            $rider_order['admin_response_datetime'] = date('Y-m-d H:i:s', time());

            $rider_details = $this->RiderOrder->getDetails($id);

            if(count($rider_details) > 0) {
                $id = $rider_details['RiderOrder']['id'];




                $this->RiderOrder->id = $id;


                if ($this->RiderOrder->save($rider_order)) {

                    $rider_details = $this->RiderOrder->getDetails($id);

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

    public function showRiderOrders()
    {

        $this->loadModel("RiderOrder");


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $rider_user_id = $data['rider_user_id'];
            $orders = $this->RiderOrder->getAllRiderOrders($rider_user_id);


            $output['code'] = 200;

            $output['msg'] = $orders;
            echo json_encode($output);


            die();
        }
    }


    public function editUser()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user['first_name'] = $data['first_name'];
            $user['last_name'] = $data['last_name'];
            $user['email'] = $data['email'];
            $role = $data['role'];
            $country_id = $data['country_id'];
            $phone = $data['phone'];
            $user['role'] = $role;
            $user['phone'] = $phone;
            $user['country_id'] = $country_id;
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
    public function addUser()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $email = $data['email'];
            $password = $data['password'];
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $role = $data['role'];
            $country_id = $data['country_id'];
            $phone = $data['phone'];


            $created = date('Y-m-d H:i:s', time());


            if ($email != null && $password != null) {


                //$ip  = $data['ip'];

                $user['email'] = $email;
                $user['password'] = $password;
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['role'] = $role;
                $user['phone'] = $phone;
                $user['country_id'] = $country_id;
                $user['created'] = $created;


                $count = $this->User->isEmailAlreadyExist($email);



                if ($count && $count > 0) {
                    echo Message::DATAALREADYEXIST();
                    die();

                } else {


                    if (!$this->User->save($user)) {
                        echo Message::DATASAVEERROR();
                        die();
                    }

                    $user_id = $this->User->getInsertID();


                    $output = array();
                    $userDetails = $this->User->getUserDetailsFromID($user_id);

                    //CustomEmail::welcomeStudentEmail($email);
                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);


                }
            } else {
                echo Message::ERROR();
            }
        }
    }

    public function showUserDetail()
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

    public function verifyDocument()
    {
        $this->loadModel('UserDocument');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $id        = $data['id'];
            $status = $data['status'];




            $this->UserDocument->id = $id;
            if ($this->UserDocument->saveField('status',$status)) {

                $user_info = $this->UserDocument->getDetails($id);
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
    public function editVehicle(){


        $this->loadModel('Vehicle');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $id = $data['id'];
            $user['vehicle_type_id'] = $data['vehicle_type_id'];
            $user['make'] = $data['make'];
            $user['model'] = $data['model'];
            $user['year'] = $data['year'];
            $user['license_plate'] = $data['license_plate'];
            $user['color'] = $data['color'];

            $vehicle_details = $this->Vehicle->getDetails($id);
            //$vehicle_details = $this->Vehicle->getUserVehicle($data['user_id']);

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

    public function changePassword()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $this->User->id = $user_id;



            $new_password   = $data['password'];




            $this->request->data['password'] = $new_password;
            $this->User->id                  = $user_id;


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


    public function changeAdminPassword()
    {
        $this->loadModel('Admin');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];




            $new_password   = $data['password'];




            $this->request->data['password'] = $new_password;
            $this->Admin->id                  = $user_id;


            if ($this->Admin->save($this->request->data)) {

                $user_info = $this->Admin->getUserDetailsFromID($user_id);
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
    public function currentAdminChangePassword()
    {
        $this->loadModel('Admin');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $this->Admin->id = $user_id;
            $email          = $this->Admin->field('email');

            $old_password   = $data['old_password'];
            $new_password   = $data['new_password'];


            if ($this->Admin->verifyPassword($email, $old_password)) {

                $this->request->data['password'] = $new_password;
                $this->Admin->id                  = $user_id;


                if ($this->Admin->save($this->request->data)) {

                    $user_info = $this->Admin->getUserDetailsFromID($user_id);
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

    }


    public function deleteUser(){

        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $details = $this->User->getUserDetailsFromID($user_id);
            if(count($details) > 0 ) {

                $this->User->id = $user_id;
                $this->User->delete();

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function deleteAdmin(){

        $this->loadModel('Admin');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $details = $this->Admin->getUserDetailsFromID($user_id);
            if(count($details) > 0 ) {

                $this->Admin->id = $user_id;
                $this->Admin->delete();

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }
    public function deleteCountry(){

        $this->loadModel('Country');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $country_id = $data['country_id'];

            $details = $this->Country->getDetails($country_id);
            if(count($details) > 0 ) {

                $this->Country->id = $country_id;
                $this->Country->delete();

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function updateUserStatus()
    {

        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $active = $data['active'];
            $user_id = $data['user_id'];


            $user_details =  $this->User->getUserDetailsFromID($user_id);

            if(count($user_details) > 0){

                $this->User->id = $user_id;
                $this->User->saveField('active',$active);


                $user_details =  $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $user_details;
                echo json_encode($output);
                die();

            }else{
                Message::EMPTYDATA();
                die();

            }




        }


    }

    public function approveDocument()
    {

        $this->loadModel('UserDocument');
        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_document_id = $data['user_document_id'];
            $user_id = $data['user_id'];
            $status = $data['status'];

            if($status == 1){

                $msg = "Your document has been approved";

            }else   if($status == 2){


                $msg = "Your document has been rejected";
            }

            $user_details =  $this->User->getUserDetailsFromID($user_id);

            if(count($user_details) > 0){

                $this->UserDocument->id = $user_document_id;
                $this->UserDocument->saveField('status',$status);


                $user_details =  $this->User->getUserDetailsFromID($user_id);


                $notification['to'] = $user_details['User']['device_token'];
                $notification['notification']['title'] = $msg;
                $notification['notification']['body'] = "";
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "";
                $notification['data']['title'] = $msg;
                $notification['data']['body'] = '';
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "";
                Utility::sendPushNotificationToMobileDevice(json_encode($notification));

                $output['code'] = 200;
                $output['msg'] = $user_details;
                echo json_encode($output);
                die();

            }else{
                Message::EMPTYDATA();
                die();

            }




        }


    }



    public function addVehicleType()
    {



        $this->loadModel('VehicleType');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $vehicle_type['name'] =  $data['name'];
            $vehicle_type['description'] =  $data['description'];
            $vehicle_type['per_km_mile_charge'] =  $data['per_km_mile_charge'];



            if(isset($data['id'])){

                $id = $data['id'];
                $details =  $this->VehicleType->getDetails($id);
                if(count($details) > 0) {


                    if (isset($data['image'])) {


                        $image_db = $details['VehicleType']['image'];
                        if (strlen($image_db) > 5) {
                            @unlink($image_db);

                        }

                        $image = $data['image'];
                        $folder_url = UPLOADS_FOLDER_URI;

                        $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                        $vehicle_type['image'] = $filePath;


                    }


                    $this->VehicleType->id = $id;
                    $this->VehicleType->save($vehicle_type);

                    $details = $this->VehicleType->getDetails($id);

                    $output['code'] = 200;
                    $output['msg'] = $details;
                    echo json_encode($output);
                    die();
                }else{

                    Message::EMPTYDATA();
                    die();
                }

            }


              $if_exist = $this->VehicleType->ifExist($vehicle_type);
            if(count($if_exist) < 1) {
                if (isset($data['image'])) {

                    $image = $data['image'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                    $vehicle_type['image'] = $filePath;


                }


                $this->VehicleType->save($vehicle_type);
                $id = $this->VehicleType->getInsertID();
                $details = $this->VehicleType->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }else{
                Message::DUPLICATEDATE();
                die();

            }

        }




    }

    public function showVehicleTypes(){

        $this->loadModel('VehicleType');





        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $details =  $this->VehicleType->getDetails($data['id']);

            }else {


                $details = $this->VehicleType->getAll();

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
    public function deleteVehicleType(){

        $this->loadModel('VehicleType');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->VehicleType->getDetails($id);
            if(count($details) > 0 ) {


                $this->VehicleType->delete($id,true);

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }


    public function addGoodType()
    {



        $this->loadModel('GoodType');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $good_type['name'] =  $data['name'];



            if(isset($data['id'])){

                $id = $data['id'];
                $details =  $this->GoodType->getDetails($id);
                if(count($details) > 0) {




                    $this->GoodType->id = $id;
                    $this->GoodType->save($good_type);

                    $details = $this->GoodType->getDetails($id);

                    $output['code'] = 200;
                    $output['msg'] = $details;
                    echo json_encode($output);
                    die();
                }else{

                    Message::EMPTYDATA();
                    die();
                }

            }


            $if_exist = $this->GoodType->ifExist($good_type);
            if(count($if_exist) < 1) {



                $this->GoodType->save($good_type);
                $id = $this->GoodType->getInsertID();
                $details = $this->GoodType->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }else{
                Message::DUPLICATEDATE();
                die();

            }

        }





    }

    public function showGoodTypes(){

        $this->loadModel('GoodType');





        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $details =  $this->GoodType->getDetails($data['id']);

            }else {


                $details = $this->GoodType->getAll();

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
    public function deleteGoodType(){

        $this->loadModel('GoodType');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->GoodType->getDetails($id);
            if(count($details) > 0 ) {


                $this->GoodType->delete($id,true);

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }


    public function addPackageSize()
    {



        $this->loadModel('PackageSize');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $package['title'] =  $data['title'];
            $package['description'] =  $data['description'];
            $package['price'] =  $data['price'];





            if(isset($data['id'])){

                $id = $data['id'];
                $details =  $this->PackageSize->getDetails($id);

                if(count($details) > 0) {


                    if (isset($data['image'])) {


                        $image_db = $details['PackageSize']['image'];
                        if (strlen($image_db) > 5) {
                            @unlink($image_db);

                        }

                        $image = $data['image'];
                        $folder_url = UPLOADS_FOLDER_URI;

                        $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                        $package['image'] = $filePath;

                    }


                    $this->PackageSize->id = $id;
                    $this->PackageSize->save($package);

                    $details = $this->PackageSize->getDetails($id);

                    $output['code'] = 200;
                    $output['msg'] = $details;
                    echo json_encode($output);
                    die();
                }else{

                    Message::EMPTYDATA();
                    die();
                }

            }


            $if_exist = $this->PackageSize->ifExist($package);
            if(count($if_exist) < 1) {

                if (isset($data['image'])) {



                    $image = $data['image'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                    $package['image'] = $filePath;

                }

                $this->PackageSize->save($package);
                $id = $this->PackageSize->getInsertID();
                $details = $this->PackageSize->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }else{
                Message::DUPLICATEDATE();
                die();

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
    public function deletePackageSize(){

        $this->loadModel('PackageSize');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->PackageSize->getDetails($id);
            if(count($details) > 0 ) {


                $this->PackageSize->delete($id,true);

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function showVehicles(){

        $this->loadModel('Vehicle');





        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $details =  $this->Vehicle->getDetails($data['id']);

            }else {


                $details = $this->Vehicle->getAll();

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

    public function showAllUsers(){

        $this->loadModel('User');




        if ($this->request->isPost()) {




            $users = $this->User->getAll();





            $output['code'] = 200;

            $output['msg'] = $users;


            echo json_encode($output);


            die();


        }


    }


    public function addPaymentMethod(){

        $this->loadModel('PaymentMethod');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $payment_method['status'] = $data['status'];
                $payment_method['key'] = $data['key'];
                $this->PaymentMethod->id = $data['id'];
                $this->PaymentMethod->save($payment_method);

                $details = $this->PaymentMethod->getDetails($data['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }


            $payment_method['status'] = $data['status'];
            $payment_method['key'] = $data['key'];
            $payment_method['name'] = $data['name'];

            $count = $this->PaymentMethod->checkDuplicate($payment_method);

            if($count < 1) {


                $this->PaymentMethod->save($payment_method);
                $id = $this->PaymentMethod->getInsertID();
                $details = $this->PaymentMethod->getDetails($id);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{



                $output['code'] = 201;

                $output['msg'] = "duplicate";


                echo json_encode($output);


                die();
            }


        }

        }


    public function addSettings(){

        $this->loadModel('Setting');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $contact['source'] = $data['source'];
                $contact['type'] = $data['type'];

                if(isset($data['category'])){


                    $contact['category'] = $data['category'];
                }

                $details = $this->Setting->getDetails($data['id']);

                if (isset($data['image'])) {


                    $image_db = $details['Setting']['image'];
                    if (strlen($image_db) > 5) {
                        @unlink($image_db);

                    }

                    $image = $data['image'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                    $contact['image'] = $filePath;



                }



                $this->Setting->id = $data['id'];
                $this->Setting->save($contact);

                $details = $this->Setting->getDetails($data['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }


            $contact['source'] = $data['source'];
            $contact['type'] = $data['type'];

            if (isset($data['image'])) {




                $image = $data['image'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                $contact['image'] = $filePath;



            }





                $this->Setting->save($contact);
                $id = $this->Setting->getInsertID();
                $details = $this->Setting->getDetails($id);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();



        }

    }


    public function activateSettings(){

        $this->loadModel('Setting');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $category = $data['category'];
            $active = $data['active'];
            $type = $data['type'];


            $details = $this->Setting->getSettingsAgainstCategoryAndType($category,$type);
            if(count($details) > 0) {

                //first deactivate all settings
                $this->Setting->updateSettingsAgainstCategory($category, 0);


                //only active single category setting
                $this->Setting->updateSettingsAgainstCategoryAndType($category, $active,$type);

                $details = $this->Setting->getSettingsAgainstCategory($category);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }else{

                $output['code'] = 200;
                $output['msg'] = "no category in the database with this name";
                echo json_encode($output);


                die();


            }
        }




    }


    public function test(){

        $result = Utility::sendSmsVerification("03137370772","hello");

        echo json_encode($result);


        die();
    }

    public function deleteSettings(){

        $this->loadModel('Setting');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->Setting->getDetails($data['id']);
            if(count($details) > 0 ) {

                $this->Setting->id = $id;
                $this->Setting->delete();

                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }


    public function updateDefaultCountry(){

        $this->loadModel('Country');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $country['default'] = $data['default'];
            $country['active'] = $data['active'];
            $this->Country->setDefaultToZero();
            $details = $this->Country->getDetails($data['id']);
            if(count($details) > 0 ) {

                $this->Country->id = $id;
                $this->Country->save($country);

                $details = $this->Country->getDetails($data['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }
    public function updateDefaultCity(){

        $this->loadModel('City');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            //$city['default'] = $data['default'];


            $details = $this->City->getDetails($data['id']);
            if(count($details) > 0 ) {

                $this->City->id = $id;
                $this->City->saveField('default',$data['default']);

                $details = $this->City->getDetails($data['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function updateOrderStatus(){

        $this->loadModel('Order');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $status = $data['status'];



            $details = $this->Order->getDetails($data['id']);
            if(count($details) > 0 ) {

                $this->Order->id = $id;
                $this->Order->saveField('status',$status);

                $details = $this->Order->getDetails($data['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function addAdminUser()
    {


        $this->loadModel('Admin');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $email = $data['email'];
            $password = $data['password'];
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $role = $data['role'];



            $created = date('Y-m-d H:i:s', time());


            if ($email != null && $password != null) {


                //$ip  = $data['ip'];

                $user['email'] = $email;
                $user['password'] = $password;
                $user['first_name'] = $first_name;

                $user['last_name'] = $last_name;
                $user['role'] = $role;
                $user['created'] = $created;


                $count = $this->Admin->isEmailAlreadyExist($email);



                if ($count && $count > 0) {
                    echo Message::DATAALREADYEXIST();
                    die();

                } else {


                    if (!$this->Admin->save($user)) {
                        echo Message::DATASAVEERROR();
                        die();
                    }

                    $user_id = $this->Admin->getInsertID();


                    $output = array();
                    $userDetails = $this->Admin->getUserDetailsFromID($user_id);

                    //CustomEmail::welcomeStudentEmail($email);
                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);


                }
            } else {
                echo Message::ERROR();
            }
        }
    }


    public function editAdminUser()
    {


        $this->loadModel('Admin');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $email = $data['email'];

            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $role = $data['role'];

            $created = date('Y-m-d H:i:s', time());


            $user['email'] = $email;

            $user['first_name'] = $first_name;

            $user['last_name'] = $last_name;
            $user['role'] = $role;
            $user['created'] = $created;


            $user_id = $data['id'];



            $userDetails = $this->Admin->getUserDetailsFromID($user_id);
            if(count($userDetails)) {
                $this->Admin->id = $user_id;
                $this->Admin->save($user);


                $output = array();
                $userDetails = $this->Admin->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }

    public function showAdminUsers(){

        $this->loadModel('Admin');




        if ($this->request->isPost()) {



            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['id'])){

                $details = $this->Admin->getUserDetailsFromID($data['id']);


            }else{


                $details = $this->Admin->getAll();

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

    public function addCoupon()
    {

        $this->loadModel("Coupon");
        //$this->loadModel("Restaurant");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $coupon_code   = $data['coupon_code'];
            $limit_users   = $data['limit_users'];
            $discount      = $data['discount'];
            $expiry_date   = $data['expiry_date'];
            $created = date('Y-m-d H:i:s', time());






            $coupon['coupon_code']   = $coupon_code;
            $coupon['limit_users']   = $limit_users;
            $coupon['discount']      = $discount;
            $coupon['expiry_date']   = $expiry_date;
            $coupon['created']   = $created;




            if(isset($data['id'])){

                $this->Coupon->id = $data['id'];
                $this->Coupon->save($coupon);
                $coupon_detail = $this->Coupon->getDetails($data['id']);


                $output['code'] = 200;

                $output['msg'] = $coupon_detail;
                echo json_encode($output);


                die();

            }else{


                if (count($this->Coupon->isCouponCodeExist($coupon_code)) < 1) {
                    if ($this->Coupon->save($coupon)) {
                        $id = $this->Coupon->getInsertID();
                        $coupon_detail = $this->Coupon->getDetails($id);


                        $output['code'] = 200;

                        $output['msg'] = $coupon_detail;
                        echo json_encode($output);


                        die();
                    } else {

                        echo Message::DATASAVEERROR();
                        die();

                    }
                }else{


                    Message::DUPLICATEDATE();
                    die();
                }




            }

        }
    }
    public function showCoupons()
    {

        $this->loadModel("Coupon");


        if ($this->request->isPost()) {



            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['coupon_id'])){

                $coupons = $this->Coupon->getDetails($data['coupon_id']);


            }else{


                $coupons = $this->Coupon->getAll();

            }

            if(count($coupons) > 0) {

                $output['code'] = 200;

                $output['msg'] = $coupons;
                echo json_encode($output);


                die();
            }else{
                Message::EMPTYDATA();
                die();
            }
        }
    }
    public function deleteCoupon()
    {

        $this->loadModel("Coupon");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $coupon_id = $data['coupon_id'];
            $coupon_detail = $this->Coupon->getDetails($coupon_id);

            if (count($coupon_detail) > 0) {


                $this->Coupon->id = $coupon_id;

                if ($this->Coupon->delete()) {

                    Message::DELETEDSUCCESSFULLY();
                    die();
                } else {

                    echo Message::DATASAVEERROR();
                    die();

                }
            } else {


                Message::EMPTYDATA();
                die();

            }
        }
    }


    public function showCountries(){

        $this->loadModel('Country');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            if(isset($data['country_id'])){
                $countries = $this->Country->getDetails($data['country_id']);

            }else {
                $countries = $this->Country->getAll();

            }



            $output['code'] = 200;

            $output['msg'] = $countries;


            echo json_encode($output);


            die();


        }


    }



    public function addCountry()
    {


        $this->loadModel('Country');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $country['iso'] =  $data['iso'];
            $country['name'] =  $data['name'];
            $country['iso3'] =  $data['iso3'];
            $country['country_code'] =  $data['country_code'];
            $country['currency_code'] =  $data['currency_code'];
            $country['currency_symbol'] =  $data['currency_symbol'];
            $country['active'] =  $data['active'];
            $country['default'] =  $data['default'];

            if(isset( $data['id'])) {
                $country_id = $data['id'];

                $details = $this->Country->getDetails($country_id);
                if (count($details) > 0) {

                    if( $data['default'] > 0){

                        $this->Country->setDefaultToZero();
                    }


                    $this->Country->id = $country_id;
                    $this->Country->save($country);
                    $details = $this->Country->getDetails($country_id);
                    $output['code'] = 200;
                    $output['msg'] = $details;
                    echo json_encode($output);
                    die();

                }else{

                    Message::EMPTYDATA();
                    die();
                }
            }





            $if_exist = $this->Country->checkDuplicate($data['name']);
            if(count($if_exist) < 1) {
                if( $data['default'] > 0){

                    $this->Country->setDefaultToZero();
                }
                $this->Country->save($country);

                $country_id = $this->Country->getInsertID();
                $output = array();
                $details = $this->Country->getDetails($country_id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();
            }else{

                Message::DUPLICATEDATE();
                die();
            }

        }
    }

    public function updateCountryStatus()
    {


        $this->loadModel('Country');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $active = $data['active'];
            $country_id = $data['country_id'];




                $details = $this->Country->getDetails($country_id);
                if (count($details) > 0) {


                    $this->Country->id = $country_id;
                    $this->Country->saveField("active",$active);
                    $details = $this->Country->getDetails($country_id);
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

    public function showCities()
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




    public function showSettingsAgainstCategoryAndType()
    {


        $this->loadModel("Setting");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $type = $data['type'];
            $category = $data['category'];









                $setting_details = $this->Setting->getSettingsAgainstCategoryAndType($category,$type);



                $output['code'] = 200;

                $output['msg'] = $setting_details;


                echo json_encode($output);


                die();

            }

    }






    public function showSubCategoryDetails(){

        $this->loadModel('SubCategory');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $category = $this->SubCategory->getDetails($id);





            $output['code'] = 200;

            $output['msg'] = $category;


            echo json_encode($output);


            die();


        }


    }

    public function showAllPosts()
    {


        $this->loadModel("Post");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            if(isset($data['status'])){

                $status = $data['status'];
                $post = $this->Post->getPostAgainstStatus($status);

            }else {
                $post = $this->Post->getAll();
            }

            $output['code'] = 200;

            $output['msg'] = $post;


            echo json_encode($output);


            die();


        }
    }


    public function showPostDetail()
    {


        $this->loadModel("Post");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $post_id = $data['id'];



                $post_details = $this->Post->getDetails($post_id);



            $output['code'] = 200;

            $output['msg'] = $post_details;


            echo json_encode($output);


            die();


        }
    }
    public function postStatusUpdate()
    {


        $this->loadModel("Post");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $status = $data['status'];
            $id = $data['id'];

            $this->Post->id = $id;
            $this->Post->saveField('status',$status);

            $details = $this->Post->getDetails($id);



            $output['code'] = 200;

            $output['msg'] = $details;


            echo json_encode($output);


            die();


        }
    }


    public function deleteSubCategory(){

        $this->loadModel('SubCategory');
        $this->loadModel('FeaturedCategory');
        $this->loadModel('Form');
        $this->loadModel('Option');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $this->SubCategory->delete($id);

           $forms =  $this->Form->getFormAgainstCategoryID($id);
           foreach ($forms as $form){


               $form_id = $form['Form']['id'];
               $this->Option->deleteAllOptions($form_id);


           }
            $this->Form->deleteAllFields($id);
            $this->FeaturedCategory->deleteFeaturedSubCategory($id);




            $output['code'] = 200;

            $output['msg'] = "deleted";


            echo json_encode($output);


            die();


        }


    }

    public function showSubCategories(){

        $this->loadModel('SubCategory');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $main_cat_id = $data['main_cat_id'];
            $categories = $this->SubCategory->getSubCategories($main_cat_id);





            $output['code'] = 200;

            $output['msg'] = $categories;


            echo json_encode($output);


            die();


        }


    }


    public function showOptions(){

        $this->loadModel('Option');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $form_id = $data['form_id'];
           // $name =  $data['name'];
            $options = $this->Option->getAgainstFormID($form_id);





            $output['code'] = 200;

            $output['msg'] = $options;


            echo json_encode($output);


            die();


        }


    }



    public function addForm()
    {



        $this->loadModel('Form');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $form['sub_category_id'] =  $data['sub_category_id'];
            $form['name'] =  $data['name'];
            $form['required'] =  $data['required'];
            $form['type'] =  $data['type'];
            $form['field_type'] =  $data['field_type'];
            $form['order'] =  $data['order'];


            if(isset($data['id'])){

                $id = $data['id'];

                /*$order_existed = $this->Form->checkIfOrderExistedInOtherForm($data['order'],$id,$data['sub_category_id']);

                if(count($order_existed) > 0){


                    $this->Form->id = $order_existed['Form']['id'];
                    $this->Form->saveField('order',0);

                }*/


                $this->Form->id = $id;
                $this->Form->save($form);

                $details =  $this->Form->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();

            }

          /*  $order_existed = $this->Form->checkIfOrderExisted($data['order'],$data['sub_category_id']);

            if(count($order_existed) > 0){


                $this->Form->id = $order_existed['Form']['id'];
                $this->Form->saveField('order',0);

            }*/


            $this->Form->save($form);
            $id = $this->Form->getInsertID();
            $details =  $this->Form->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();




        }




    }
    public function showForm(){

        $this->loadModel('Form');
        $this->loadModel('Option');



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $sub_category_id = $data['sub_category_id'];

            $form = $this->Form->getFormAgainstCategoryID($sub_category_id);

            foreach ($form as $key => $value) {
                $id = $value['Form']['id'];
                $options =  $this->Option->getAgainstFormID($id);


                $form[$key]['Form']['select'] = $options;

            }



            $output['code'] = 200;

            $output['msg'] = $form;


            echo json_encode($output);


            die();


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



    public function addOption()
    {



        $this->loadModel('Option');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $option['name'] =  $data['name'];
            $option['form_id'] =  $data['form_id'];


            if(isset($data['id'])){

                $id = $data['id'];
                $this->Option->id = $id;
                $this->Option->save($option);

                $details =  $this->Option->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();

            }



            $this->Option->save($option);
            $id = $this->Option->getInsertID();
            $details =  $this->Option->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();




        }




    }



    public function admob(){

        $this->loadModel('Admob');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $admob['publisher_id'] = $data['publisher_id'];
            $admob['application_id'] = $data['application_id'];
            $admob['banner_ad'] = $data['banner_ad'];
            $admob['banner_id']= $data['banner_id'];
            $admob['interstitial_ad']= $data['interstitial_ad'];
            $admob['interstitial_id']= $data['interstitial_id'];
            $admob['interstitial_click'] = $data['interstitial_click'];
            $admob['phone'] = $data['phone'];
            $admob['created'] =  date('Y-m-d H:i:s', time());

            $ifExist =  $this->Admob->ifExist();
            if(count($ifExist) > 0){

                $this->Admob->id = $ifExist['Admob']['id'];
                $this->Admob->save($admob);
                $details = $this->Admob->getDetails($ifExist['Admob']['id']);

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();



            }




            $this->Admob->save($admob);
            $id = $this->Admob->getInsertID();


            $details = $this->Admob-> getDetails($id);

            $output['code'] = 200;

            $output['msg'] = $details;


            echo json_encode($output);


            die();


        }

    }



    public function showAdmob()
    {

        $this->loadModel("Admob");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            // $section_id = $data['section_id'];

            $all = $this->Admob->ifExist();



            $output['code'] = 200;

            $output['msg'] = $all;


            echo json_encode($output);


            die();


        }
    }





    public function showOptionDetails(){

        $this->loadModel('Option');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $category = $this->Option->getDetails($id);





            $output['code'] = 200;

            $output['msg'] = $category;


            echo json_encode($output);


            die();


        }


    }


    public function deleteOption(){

        $this->loadModel('Option');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $this->Option->delete($id);





            $output['code'] = 200;

            $output['msg'] = "deleted";


            echo json_encode($output);


            die();


        }


    }

    public function showFormDetails(){

        $this->loadModel('Form');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $category = $this->Form->getDetails($id);





            $output['code'] = 200;

            $output['msg'] = $category;


            echo json_encode($output);


            die();


        }


    }

    public function deleteForm(){

        $this->loadModel('Form');
        $this->loadModel('Option');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $this->Form->delete($id);

            $this->Option->deleteAllOptions($id);



            $output['code'] = 200;

            $output['msg'] = "deleted";


            echo json_encode($output);


            die();


        }


    }


    public function addFeaturedCategory()
    {



        $this->loadModel('FeaturedCategory');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $cat['main_cat_id'] =  $data['main_cat_id'];
            $cat['sub_cat_id'] =  $data['sub_cat_id'];
            $cat['featured'] =  $data['featured'];
            $cat['created'] = date('Y-m-d H:i:s', time());

            if($data['main_cat_id'] > 0 && $data['sub_cat_id'] > 0){

                $output['code'] = 201;
                $output['msg'] = "Error: You are sending both categories id greater then 0";
                echo json_encode($output);
                die();

            }

            if(isset($data['id'])){

                $id = $data['id'];
                $this->FeaturedCategory->id = $id;
                $this->FeaturedCategory->save($cat);

                $details =  $this->FeaturedCategory->getDetails($id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();

            }

            $count =  $this->FeaturedCategory->checkDuplicate($cat);
            if($count > 0 ){

                echo $count;
                $output['code'] = 201;
                $output['msg'] = "This category has already been featured";
                echo json_encode($output);
                die();

            }

            if($data['featured'] == 0){


                $this->FeaturedCategory->deleteFeaturedCategory($data);
                $output['code'] = 200;
                $output['msg'] = "The category has been unfeatured";
                echo json_encode($output);
                die();
            }





            $this->FeaturedCategory->save($cat);
            $id = $this->FeaturedCategory->getInsertID();
            $details =  $this->FeaturedCategory->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();




        }




    }


    public function showFeaturedCategories(){

        $this->loadModel('FeaturedCategory');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $category = $this->FeaturedCategory->getAll();





            $output['code'] = 200;

            $output['msg'] = $category;


            echo json_encode($output);


            die();


        }


    }


    public function addSection()
    {


        $this->loadModel('Section');
        //$this->loadModel('Post');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $section['name'] = $data['name'];
            $section['order'] = $data['order'];


            if(isset($data['id'])){
                $id =  $data['id'];


              /*  $order_existed = $this->Section->checkIfOrderExistedInOthers($data['order'],$id);

                if(count($order_existed) > 0){


                    $this->Section->id = $order_existed['Section']['id'];
                    $this->Section->saveField('order',0);

                }
*/

                $this->Section->id = $id;
                $this->Section->save($section);
                $output = array();
                $details = $this->Section->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }

            /*$order_existed = $this->Section->checkIfOrderExisted($data['order']);


            if(count($order_existed) > 0){
                $this->Section->setOrderZero($order_existed['Section']['id']);


            }*/




            $this->Section->save($section);
            $id = $this->Section->getInsertID();

            $output = array();
            $details = $this->Section->getDetails($id);


            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);





        }
    }


    public function addSectionPost()
    {


        $this->loadModel('SectionPost');
        //$this->loadModel('Post');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $section['section_id'] = $data['section_id'];
            $section['post_id'] = $data['post_id'];
            $section['order'] = $data['order'];
            $section['created'] = date('Y-m-d H:i:s', time());


            if(isset($data['id'])){
               $id =  $data['id'];


                $order_existed = $this->SectionPost->checkIfOrderExistedInOthers($data['order'],$id,$data['section_id']);

                if(count($order_existed) > 0){



                    $this->SectionPost->updateToZero($order_existed['SectionPost']['id']);

                }


                $this->SectionPost->id = $id;
                $this->SectionPost->save($section);
                $output = array();
                $details = $this->SectionPost->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }


            $order_existed = $this->SectionPost->checkIfOrderExisted($data['order'],$data['section_id']);

            if(count($order_existed) > 0){


                $this->SectionPost->updateToZero($order_existed['SectionPost']['id']);

            }
            $this->SectionPost->save($section);
            $id = $this->SectionPost->getInsertID();

            $output = array();
            $details = $this->SectionPost->getDetails($id);


            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);





        }
    }


    public function deleteSection(){

        $this->loadModel('Section');
        $this->loadModel('SectionPost');





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $this->SectionPost->deleteAllSectionPosts($id);

            $this->Section->id = $id;
            $this->Section->delete();






            $output['code'] = 200;

            $output['msg'] = "deleted";


            echo json_encode($output);


            die();


        }


    }

    public function deleteSectionPost(){


        $this->loadModel('SectionPost');





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];



            $this->SectionPost->id = $id;
            $this->SectionPost->delete();






            $output['code'] = 200;

            $output['msg'] = "deleted";


            echo json_encode($output);


            die();


        }


    }


    public function showSections()
    {

        $this->loadModel("Section");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $sections = $this->Section->getAllSections();


            if(count($sections) > 0) {
                $output['code'] = 200;

                $output['msg'] = $sections;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }

    public function showSectionDetails()
    {

        $this->loadModel("Section");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $details = $this->Section->getDetails($id);




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


    public function showSectionPostDetails()
    {

        $this->loadModel("Section");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $details = $this->SectionPost->getDetails($id);




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


    public function showSectionPosts()
    {

        $this->loadModel("SectionPost");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $section_id = $data['section_id'];
            $sections = $this->SectionPost->getSectionPost($section_id);


            if(count($sections) > 0) {
                $output['code'] = 200;

                $output['msg'] = $sections;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }





}