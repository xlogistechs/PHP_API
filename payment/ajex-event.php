<?php
    include('config.php');
    
    
    if(@$_GET['q']=="addNewCard") 
    {   
        $full_name=$_GET['full_name'];
        $car_number=$_GET['car_number'];
        $month=$_GET['month'];
        $year=$_GET['year'];
        $cvv_number=$_GET['cvv_number'];
        
        $url=BASE_URL.'api/addPaymentCard';
        $data =array(
            "user_id" => $user_id,
            "default" => "1",
            "name" => $full_name,
            "card" => $car_number,
            "cvc" => $cvv_number,
            "exp_month" => $month,
            "exp_year" => $year
        );
        
        $user_data=@curl_request($data,$url);
        //$user_data=json_encode($user_data);
        echo $user_data['code'];
        
        
    }
     
     
     
     
     
     
?>