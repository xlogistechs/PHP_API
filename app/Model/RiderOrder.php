<?php

//App::uses('Lib', 'Utility');

class RiderOrder extends AppModel
{

    public $useTable = 'rider_order';

    public $belongsTo = array(
        'Rider' => array(
            'className' => 'User',
            'foreignKey' => 'rider_user_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),

        'Order' => array(
            'className' => 'Order',
            'foreignKey' => 'order_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        )


    );
    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'RiderOrder.id' => $id,



            )
        ));
    }

    public function getRiderOrderAgainstOrderAndUserID($order_id,$user_id)
    {
        $this->Behaviors->attach('Containable');


        return $this->find('first', array(
            'conditions' => array(

                'RiderOrder.order_id' => $order_id,
                'RiderOrder.rider_response !=' => 2,



            ),
            'contain'=>array('Rider','Order.User','Order.OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),






        ));
    }

    public function getRiderOrderAgainstOrderID($order_id)
    {
        $this->Behaviors->attach('Containable');


        return $this->find('first', array(
            'conditions' => array(

                'RiderOrder.order_id' => $order_id,
                'RiderOrder.rider_response !=' => 2,
               // 'RiderOrder.delivered' => "0000-00-00 00:00:00",



            ),
            'contain'=>array('Rider','Order.User'),






        ));
    }

    public function checkIfAnyOrderWhichIsAlreadyAssigned($order_id)
    {



        return $this->find('count', array(
            'conditions' => array(

                'RiderOrder.order_id' => $order_id,
                'RiderOrder.rider_response' => array(0,1),
                'RiderOrder.delivered' => "0000-00-00 00:00:00",



            ),







        ));
    }

    public function isEmptyOnTheWayToPickeupTime($order_id)
    {
        return $this->find('count', array(
            'conditions' => array(


                'RiderOrder.order_id'=> $order_id,
                'RiderOrder.on_the_way_to_pickup'=> "0000-00-00 00:00:00"

            )
        ));
    }
    public function isEmptyPickUpTime($order_id)
    {
        return $this->find('count', array(
            'conditions' => array(


                'RiderTrackOrder.order_id'=> $order_id,
                array('not' => array(
                    'RiderTrackOrder.pickup_time'=> "0000-00-00 00:00:00"

                ))
            )
        ));
    }

    public function isEmptyOnMyWayToUserTime($order_id)
    {
        return $this->find('count', array(
            'conditions' => array(


                'RiderTrackOrder.order_id'=> $order_id,
                array('not' => array(
                    'RiderTrackOrder.on_my_way_to_user_time'=> "0000-00-00 00:00:00"

                ))
            )
        ));
    }
    public function isEmptyDeliveryTime($order_id)
    {
        return $this->find('count', array(
            'conditions' => array(


                'RiderTrackOrder.order_id'=> $order_id,
                array('not' => array(
                    'RiderTrackOrder.delivery_time'=> "0000-00-00 00:00:00"

                ))
            )
        ));
    }


    public function getCompletedOrders($rider_user_id,$starting_point = null)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.rider_response' => 1,
                'RiderOrder.delivered >' => "0000-00-00 00:00:00",



            ),
            'contain'=>array('Rider','Order.User','Order.OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $rider_user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),
            'limit' => 10,
            'offset' => $starting_point*10,
            'order' => 'RiderOrder.id DESC',
        ));
    }

    public function getRiderEarningsCompletedOrders($rider_user_id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.rider_response' => 1,
                'RiderOrder.delivered >' => "0000-00-00 00:00:00",



            ),
            'fields' => array('sum(Order.total) as total_sum')
        ));
    }


    public function getCountCompletedOrders($rider_user_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.rider_response' => 1,
                'RiderOrder.delivered >' => "0000-00-00 00:00:00",



            )
        ));
    }

    public function getPendingOrders($rider_user_id,$starting_point = null)
    {

        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.rider_response' => 0,




            ),
            'contain'=>array('Rider','Order.User','Order.OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $rider_user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),
            'limit' => 10,
            'offset' => $starting_point*10,
            'order' => 'RiderOrder.id DESC',
        ));
    }

    public function getActiveOrders($rider_user_id,$starting_point = null)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.rider_response' => 1,
                'RiderOrder.delivered' => "0000-00-00 00:00:00",
                //'RiderOrder.on_the_way_to_pickup >' => "0000-00-00 00:00:00",




            ),
            'contain'=>array('Rider','Order.User','Order.OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $rider_user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),
            'limit' => 10,
            'offset' => $starting_point*10,
            'order' => 'RiderOrder.id DESC',
        ));
    }
    public function isDuplicateRecord($rider_user_id, $order_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'RiderOrder.rider_user_id' => $rider_user_id,
                'RiderOrder.order_id' => $order_id


            )
        ));
    }








}


?>