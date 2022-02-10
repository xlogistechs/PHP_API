<?php

App::uses('Lib', 'Utility');
App::uses('Firebase', 'Lib');
App::uses('Postmark', 'Utility');
App::uses('Message', 'Utility');
App::uses('Variables', 'Utility');
App::uses('PushNotification', 'Utility');
App::uses('CustomEmail', 'Utility');




class ConfigController extends AppController
{

    public $layout = false;






   

    public function config(){


        $this->autoRender = true;
    }




}
?>