<?php
include('config.php');
?>
<!DOCTYPE html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    
    <title>Payment | Checkout</title>
	<meta name="viewport" content="width=device-width, initial-scale = 1.0, maximum-scale = 1.0, user-scalable=no">
	<link href="assets/css/fonts.css" rel="stylesheet"> 
	<link rel="stylesheet" type="text/css" href="assets/css/style.css?time<?php echo time(); ?>">
	<!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">-->
    <script src="assets/js/jquery-1.12.4.js"></script>
    <script>
	   $(window).load(function() {
	     $('#status').fadeOut();
	     $('#preloader').delay(350).fadeOut('slow');
	     $('body').delay(350).css({'overflow':'visible'});
	   })
	</script>
	
</head>
<body>
    
    
<div id="preloader" align="center">
  <div id="loading">
    <div class="spinner loading"></div>
  </div>
</div>

    
	<div class="mainServicesDetail">
	 
		    <?php
		        
		        //paypal thank you page
		        if(isset($_GET['status']))
		        {
		                
		            if($_GET['status']=="paymentSuccess")
		            {
		                ?>
    		                <br><br>
    		                <div class="container">
            		            <div class="restaurantInfo" style="text-align: center;">
                			        <img src="assets/images/done.png" style="width: 80px;">
                			        <h2 style="margin: 10px 0px;font-weight: 600;font-size: 18px; color:#363B3F;">Thank You</h2>
                			        <p>Your Payment is Successfully Done</p>
                			    </div>
                	        </div>
    		           <?php
		            }
		            else
		            if($_GET['status']=="paymentFaild")
		            {
		                ?>
    		                <br><br>
    		                <div class="container">
            		            <div class="restaurantInfo" style="text-align: center;">
                			        <img src="assets/images/error.png" style="width: 80px;">
                			        <h2 style="margin: 10px 0px;font-weight: 600;font-size: 18px; color:#363B3F;">Error</h2>
                			        <p>Something went wrong in placing the order. Please contact the administrator</p>
                			    </div>
                	        </div>
    		           <?php
		            }
		            
		           die();
		        }
		        else
    		    if(isset($_GET['paymentId']) && isset($_GET['token']) && isset($_GET['PayerID']))
		        {
		                
		                $paymentId=$_GET['paymentId'];
		                
		                $App_Data;
		                //covert base64 to normal json from session App_Data
                        $json_data=base64_decode($App_Data); 
                        
                        //convert normal json into array
                        //$json_app_date=json_decode($json_data, true);
                        // print_r($json_app_date);
                        
                        //modify json_array
                        $transaction_obj=array(
                                                "cod"=>"0",
                                                "payment_id"=>"0",
                                                "transaction"=> array
                            					(
                            					     "value"=>$paymentId,
                                                    "type"=>"paypal"
                            					)
                                            );
                        $newArrayPost=array_replace($json_app_date,$transaction_obj);
                        
                        // post data on service
                        $url=BASE_URL.'api/placeOrder';
                        $data =$newArrayPost;
                        //echo json_encode($data);
                        $result_data=@curl_request($data,$url);
                        
                        if($result_data['code']=="200")
                        {
                           echo "<script>window.location='./?status=paymentSuccess'</script>";
                        }
    		            else
                        {
                            echo "<script>window.location='./?status=paymentFaild'</script>";
                        }
    		           ?>
    		                <!--<br><br>-->
    		                <!--<div class="container">-->
            		        <!--    <div class="restaurantInfo" style="text-align: center;">-->
                			   <!--     <img src="assets/images/done.png" style="width: 80px;">-->
                			   <!--     <h2 style="margin: 10px 0px;font-weight: 600;font-size: 18px; color:#363B3F;">Thank You</h2>-->
                			   <!--     <p>Your Payment is Successfully Done</p>-->
                			   <!-- </div>-->
                	     <!--   </div>-->
    		           <?php
	                  die();
		        }
		        else
		        if(isset($_GET['payment']) || isset($_GET['p']))
		        {   
		            if(@$_GET['payment'] == 'cards')
		            {
		                
		                
		                ?>
		                    <style>
		                        .singleCard
		                        {
		                            padding: 10px 0 10px 10px;
                                    border: solid 1px #eee;
                                    margin: 8px 0 0 0;
                                    border-radius: 4px;
                                    color: #575757;
                                    font-size: 14px;
                                    cursor: pointer;
		                        }
		                        .cards
		                        {
		                            margin: 0 20px 0 20px;
		                        }
		                        
		                        .singleCard img
		                        {
		                            width: 40px;
		                            vertical-align: middle;
		                            margin-right: 10px;
		                        }
		                    </style>
		                    <br><br>
		                    
		                    <div class="container">
                    				<div class="mainSecureCheckout">
                    					<div class="sectionHeading padding20">
                    						<h3>secure checkout</h3>
                    					</div>
                    					<hr>
                    					<div class="paymentMethod">
                    					    <h3 class="padding20">Payment Method</h3>
                    						<div class="itsSafeToPay padding20">
                    						    <i class="fa fa-lock" aria-hidden="true"></i>
                    							<p style="color:#797A7E;font-weight: 300;font-size: 13px;"> 
                    								It's safe to pay. All transactions are protected by SSL encryption. 
                    							</p>
                    						</div>
                    						<div class="pamentTypes" style="margin-top: 10px;">
                    							<form id="formvisa" action="index.php?payment=payCardNow" method="get">
                        							<input type="hidden" name="payment" value="payCardNow">
                        							<div class="cards">
                                    		            <?php
                                    		              //  print_r($user_cards);
                                    		              //  echo $user_cards;
                                    		                if($user_cards_data!="")
                                    		                {
                                    		                    foreach ($user_cards_data as $single_row):
                            		                                ?>
                            		                                    <div class="singleCard" onclick="selectPayment('<?php echo $single_row['PaymentCard']['id']; ?>')">
                            		                                        <input type="radio" id="<?php echo $single_row['PaymentCard']['id']; ?>" name="selectCard" value="<?php echo $single_row['PaymentCard']['id']; ?>" required>
                            		                                        <img src="assets/images/<?php echo $single_row['brand'] ?>.jpg">
                            		                                        **** **** **** <?php echo $single_row['last4'] ?>
                            		                                    </div>
                            		                                <?php
                                        		                endforeach;
                                    		                }
                                    		            ?>
                                    		            <a href="index.php?p=addNewCard" style="text-decoration: none; border: 0px;">
                                        		            <div class="singleCard">
                		                                        + Add New Card
                		                                    </div>
                		                                </a>
                                    		        </div>
                                    		        <button type="submit" class="proceedBtn">
                            					        Continue
                            		                </button>
                            		            </form>
                    						</div>
                    					</div>
                    				    </form>
                    				</div>
                    			
                    		</div>
    		                
    		           <?php 
		                
		            }
		            else
		            if($_GET['payment'] == 'payCardNow')
		            {
		                
		                $selectCard=@$_GET['selectCard'];
		                if(@$_GET['selectCard']=="")
		                {
		                    echo "<script>window.location='index.php?payment=cards&status=error'</script>";
		                }
		                
		                //modify json_array
                        $transaction_obj=array(
                                                "cod"=>"0",
                                                "payment_id"=>$selectCard,
                                                "transaction"=> array
                            					(
                            					    "value"=>"1",
                                                    "type"=>"stripe"
                            					)
                                            );
                        $newArrayPost=array_replace($json_app_date,$transaction_obj);
                        
                        // post data on service
                        $url=BASE_URL.'api/placeOrder';
                        $data =$newArrayPost;
                        // echo json_encode($data);
                        $result_data=@curl_request($data,$url);
                        
                        if($result_data['code']=="200")
                        {
                          echo "<script>window.location='./?status=paymentSuccess'</script>";
                        }
                        else
                        {
                            echo "<script>window.location='./?status=paymentFaild'</script>";
                        }
                        
		            }
		            else
		            if($_GET['p'] == 'addNewCard')
		            {
		                ?>
		                    <style>
		                        .singlefield
		                        {
		                            border: solid 1px #eee;
		                            margin: 8px 0 0 0;
                                    border-radius: 4px;
                                    height: 38px;
                                    padding: 3px;
                                    
		                        }
		                        .formField
		                        {
		                            margin: 0 20px 0 20px;
		                        }
		                        
		                        .singlefield input
		                        {
		                            width: 290px;
                                    height: 35px;
                                    padding-left: 5px;
                                    color: #575757;
                                    font-size: 13px;
                                    font-weight: 400;
                                    border: none;
                                    outline: 0;
		                        }
		                        
		                    </style>
		                    <br><br>
		                    
		                    <div class="container">
                    				<div class="mainSecureCheckout">
                    					<div class="sectionHeading padding20">
                    						<h3>Add New Card</h3>
                    						<img src="assets/images/cards.png" style="width: 180px;margin-top: 10px;">
                    					</div>
                    					<hr>
                    					<div>
                    					    <div style="margin-top: 10px;">
                    							<div class="formField">
                                		            <div class="singlefield">
        		                                        <input type="text" name="full_name" id="full_name" placeholder="Full Name">
        		                                    </div>
        		                                    
        		                                    <div class="singlefield">
        		                                        <input type="number" name="car_number" id="car_number" placeholder="Card Number" maxlength="16">
        		                                    </div>
        		                                    
        		                                    <div class="singlefield" style="padding: 8px 0 0 10px;height: 34px;border:0px;">
        		                                        <label style="font-size: 12px;">Expiration Date</label>
                                                        <select id="month" style="height: 26px;width: 90px;">
                                                            <option value="01">January</option>
                                                            <option value="02">February </option>
                                                            <option value="03">March</option>
                                                            <option value="04">April</option>
                                                            <option value="05">May</option>
                                                            <option value="06">June</option>
                                                            <option value="07">July</option>
                                                            <option value="08">August</option>
                                                            <option value="09">September</option>
                                                            <option value="10">October</option>
                                                            <option value="11">November</option>
                                                            <option value="12">December</option>
                                                        </select>
                                                        <select id="year" style="height: 26px;width: 90px;">
                                                            <option value="16"> 2016</option>
                                                            <option value="17"> 2017</option>
                                                            <option value="18"> 2018</option>
                                                            <option value="19"> 2019</option>
                                                            <option value="20"> 2020</option>
                                                            <option value="21"> 2021</option>
                                                            <option value="22" selected> 2022</option>
                                                            <option value="23"> 2023</option>
                                                            <option value="24"> 2024</option>
                                                            <option value="25"> 2025</option>
                                                            <option value="26"> 2026</option>
                                                            <option value="27"> 2027</option>
                                                            <option value="28"> 2028</option>
                                                            <option value="29"> 2029</option>
                                                            <option value="30"> 2030</option>
                                                        </select>
        		                                    </div>
        		                                    
        		                                    <div class="singlefield">
        		                                        <input type="number" name="cvv_number" id="cvv_number" placeholder="CVV">
        		                                    </div>
                                		        </div>
                                		        <button type="button" name="submit" class="proceedBtn" onclick="addCard()">
                        					        Continue
                        		                </button>
                    					    </div>
                    					</div>
                    				</div>
                    		</div>
    		                
    		           <?php  
		            }
		            else
		            if($_GET['payment'] == 'cod')
		            {
		                
		                //modify json_array
                        $transaction_obj=array(
                                                "cod"=>"1",
                                                "payment_id"=>"0",
                                                "transaction"=> array
                            					(
                            					    "value"=>"1",
                                                    "type"=>"cod"
                            					)
                                            );
                        $newArrayPost=array_replace($json_app_date,$transaction_obj);
                        
                        
                        // post data on service
                        $url=BASE_URL.'api/placeOrder';
                        $data =$newArrayPost;
                        //echo json_encode($data);
                        $result_data=@curl_request($data,$url);
                        
                        // print_r($result_data);
                        // die();
                        if($result_data['code']=="200")
                        {
                           echo "<script>window.location='./?status=paymentSuccess'</script>";
                        }
                        else
                        {
                            echo "<script>window.location='./?status=paymentFaild'</script>";
                        }
                        
		            }
		        }
		        else
		        {
		            ?>
		                    <div class="container">    
                    			<div class="serviceDetails">
                    			    <div class="restaurantInfo" style="text-align: center;">
                    			        <!--<div style="background: url('<?php echo BASE_URL.$restaurantImage; ?>') , grey;margin-left:35%;height: 80px;width: 80px;background-repeat: no-repeat;border-radius: 50%;background-size: cover;background-position: center;"></div>-->
                    			        <h2 style="margin: 10px 0px;font-weight: 500;font-size: 18px; color:#363B3F;"><?php echo APP_NAME; ?></h2>
                    			    </div>
                    			    
                    				<div class="detailOption">
                    				    <hr>
                    				    <p style="color: #6E6F74;font-size: 14px;font-weight: 300;">Services Detail</p>
                    					<div class="options">
                    						<ul>
                    							<li><span>Discount</span></li>
                    						</ul>
                    						<ul class="textRight">
                    							<li><span><?php echo $discount; ?>%</span></li>
                    						</ul>
                    					</div>
                    					<hr>
                    					<div class="total">
                    						<ul>
                    							<li><span>total</span></li>
                    							<li class="dollar textRight "><span><?php echo $currency_symbol.$total; ?></span></li>
                    						</ul>
                    					</div>
                    				</div>
                    			</div>
                    		</div>
                    		<div class="container">
                    				<div class="mainSecureCheckout">
                    					<div class="sectionHeading padding20">
                    						<h3>secure checkout</h3>
                    					</div>
                    					<hr>
                    					<form id="formvisa" action="index.php?action=payNow" method="get">
                    					    <!--<input type="hidden" name="total_amount" value="<?php echo $order_data['price']; ?>">-->
                    					    <div class="paymentMethod">
                    					    <h3 class="padding20">Payment Method</h3>
                    						<div class="itsSafeToPay padding20">
                    						    <i class="fa fa-lock" aria-hidden="true"></i>
                    							<p style="color:#797A7E;font-weight: 300;font-size: 13px;"> 
                    								It's safe to pay. All transactions are protected by SSL encryption. 
                    							</p>
                    						</div>
                    						<div class="pamentTypes" style="margin-top: 10px;">
                    							
                    							<div class="choseOption" onclick="selectPayment('cod');">
                    								<input type="radio" id="cod" name="payment" value="cod" checked>
                    								<img src="assets/images/cod.png">
                    							</div>
                    							
                    							<div class="choseOption" onclick="selectPayment('cards');">
                    								<input type="radio" id="cards" name="payment" value="cards">
                    								<img src="assets/images/cards.png">
                    							</div>
                    							<div class="choseOption" onclick="selectPayment('paypal');">
                    								<input type="radio" id="paypal" name="payment" value="paypal">
                    								<img src="assets/images/paypal.png">
                    							</div>
                    							
                    							<button type="submit" class="proceedBtn" onclick="showLoading()">
                    								Continue
                    		                    </button>
                    						</div>
                    					</div>
                    				    </form>
                    				</div>
                    			
                    		</div>
		            <?php
		        }
		        
		        
		        
		    ?>
		    
		
		
	</div>
	
	<script>
	
	    function showLoading()
	    {
	        $('#preloader').fadeIn();
	    }
	
	    function selectPayment(payment_method)
	    {
	        document.getElementById(payment_method).checked = true;
	    }
	    
	    function addCard()
	    {
	        
	        document.getElementById("preloader").style.display = "block";
            var full_name=document.getElementById("full_name").value;
            var car_number=document.getElementById("car_number").value;
            var month=document.getElementById("month").value;
            var year=document.getElementById("year").value;
            var cvv_number=document.getElementById("cvv_number").value;
            
            if(full_name=="" || car_number=="" || month=="" || year=="" || cvv_number=="")
            {
                document.getElementById("preloader").style.display = "none";
                alert("Information must be filled");
                return false;    
            }
            
            var xmlhttp;
            if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {// code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function () 
            {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
                {
                    //alert(xmlhttp.responseText);
                    //document.getElementById('contentReceived').innerHTML = xmlhttp.responseText;
                    if(xmlhttp.responseText=="200")
                    {
                        document.getElementById("preloader").style.display = "block";
                        window.location='index.php?payment=cards';
                    }
                    else
                    {
                        //alert(xmlhttp.responseText);
                        if(xmlhttp.responseText)
                        {
                            window.location='./?payment=cards';
                            document.getElementById("preloader").style.display = "none";
                        }
                        
                    }
                    
                }
            }
            xmlhttp.open("GET", "ajex-event.php?q=addNewCard&full_name="+full_name+"&car_number="+car_number+"&month="+month+"&year="+year+"&cvv_number="+cvv_number);
            xmlhttp.send();
	    }
	</script>
	
</body>
</html>