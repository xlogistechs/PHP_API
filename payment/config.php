<?php
session_start();
include("../app/Config/constant.php");
// 1. Autoload the SDK Package. This will include all the files and classes to your autoloader
require __DIR__  . '/vendor/autoload.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Razorpay\Api\Api;


$baseURL="http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
//$baseURL=str_replace("payment/","",$baseURL);
$baseURL=explode("payment",$baseURL);
define("BASE_URL", $baseURL[0]);
/***********************************====Paypal=====**************************************/
// https://developer.paypal.com/webapps/developer/applications/myapps
//paypal return URl configration 
define("SET_RETURN_URL", BASE_URL."payment/");
define("SET_CANCEL_URL", BASE_URL."payment/");

$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        PAYPAL_CLIENT_ID,     // ClientID
        PAYPAL_CLIENT_SECRET      // ClientSecret
    )
);

if(!isset($_GET['id']) && !isset($_SESSION['order_session_id']))
{
    echo "access denies";
    die();
}

if(isset($_GET['id']))
{
    $order_session_id=$_SESSION['order_session_id']=$_GET['id'];
}
else
{
    $order_session_id=$_SESSION['order_session_id'];
}

$url=BASE_URL.'api/showOrderSession';
$data =array(
    "id" => $order_session_id  
);

$Order_Data=@curl_request($data,$url);

// print_r($Order_Data);

//dycript serializ json
$Order_Data_json=@json_decode($Order_Data['msg']['OrderSession']['string'],true);

//all user data
$user_data=@$Order_Data['msg']['UserDetail']['User'];
$user_id=@$Order_Data['msg']['User']['id'];

//all user card
$user_cards_data=@$Order_Data['msg']['UserDetail']['User']['Cards'];

//get currency
$currency_data=@$Order_Data['msg']['Country'];

//get order data json to array
$json_app_date=$Order_Data_json;

// print_r($json_app_date);
//set variables
$currency_symbol=@$currency_data['currency_symbol']; 
$total=@$json_app_date['total'];
$discount=@$json_app_date['discount'];
$delivery_fee=@$json_app_date['delivery_fee'];

if (isset($_GET['payment'])) 
{
    //print_r($_POST);
    if ($_GET['payment'] == 'paypal') 
    {
        
        try 
        {
            // login with paypal module
            $payer = new \PayPal\Api\Payer();
            $payer->setPaymentMethod('paypal');
            
            $amount = new \PayPal\Api\Amount();
            $amount->setTotal($total);
            $amount->setCurrency(PAYPAL_CURRENCY);
            
            $transaction = new \PayPal\Api\Transaction();
            $transaction->setAmount($amount);
            
            $redirectUrls = new \PayPal\Api\RedirectUrls();
            $redirectUrls->setReturnUrl(SET_RETURN_URL)
                ->setCancelUrl(SET_CANCEL_URL);
            
            $payment = new \PayPal\Api\Payment();
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions(array($transaction))
                ->setRedirectUrls($redirectUrls);
        
        
            $payment->create($apiContext);
            // login with paypal module
            
            echo "<script>window.location='".$payment->getApprovalLink() ."'</script>";
            
            // echo "<pre>";
            //     echo $payment;
            //     echo "\n\nRedirect user to approval_url: " . $payment->getApprovalLink() . "\n";
            // echo "</pre>";
            
        } 
        catch (\PayPal\Exception\PayPalConnectionException $ex) 
        {
            // This will print the detailed information on the exception.
            //REALLY HELPFUL FOR DEBUGGING
            echo $ex->getData();
        }
    }
}



function curl_request($data,$url)
{
    $headers = [
          "Accept: application/json",
          "Content-Type: application/json",
          "api-key: ".API_KEY." "
      ];

    $data = $data;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $return = curl_exec($ch);
    $json_data = json_decode($return, true);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    return $json_data;
}




    
    
?>