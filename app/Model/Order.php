<?php


class Order extends AppModel
{
    public $useTable = 'order';


    public $belongsTo = array(

        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',

        ),
        'DeliveryType' => array(
            'className' => 'DeliveryType',
            'foreignKey' => 'delivery_type_id',

        ),

        'GoodType' => array(
            'className' => 'GoodType',
            'foreignKey' => 'good_type_id',

        ),

        'PackageSize' => array(
            'className' => 'PackageSize',
            'foreignKey' => 'package_size_id',

        ),


    );
    public $hasMany = array(

        'OrderNotification' => array(
            'className' => 'OrderNotification',
            'foreignKey' => 'order_id',

        ),
    );

    public $hasOne = array(

        'DriverRating' => array(
            'className' => 'DriverRating',
            'foreignKey' => 'order_id',

        ),

        'RiderOrder' => array(
            'className' => 'RiderOrder',
            'foreignKey' => 'order_id',


        ),
    );

    public function getDetails($id)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('first', array(
            'conditions' => array('Order.id' => $id),
            'contain'=>array('User','DeliveryType','GoodType','PackageSize')
        ));

    }


    public function getUserOrders($user_id,$starting_point=null)
    {
        $this->Behaviors->attach('Containable');

        return $this->find('all', array(
            'conditions' => array('Order.user_id' => $user_id),
            'limit' => 10,
            'offset' => $starting_point*10,
            'contain'=>array('User','DeliveryType','GoodType','PackageSize','DriverRating','OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),
            'order' => 'Order.id DESC'
        ));

    }


    public function getUserOrdersAccordingToStatus($user_id,$status,$starting_point)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(
                'Order.user_id' => $user_id,
                'Order.status' => $status,

                ),
            'limit' => 10,
            'offset' => $starting_point*10,
            'contain'=>array('User','DeliveryType','GoodType','PackageSize','DriverRating','OrderNotification' => array('conditions' => array(
                'OrderNotification.receiver_id' => $user_id,
                'OrderNotification.read' => 0,
            ),

            ), ),
            'order' => 'Order.id DESC'
        ));

    }

    public function getOrdersAccordingToStatus($status)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array('Order.status' => $status),
            'contain'=>array('User','DeliveryType','GoodType','RiderOrder' => array('conditions' => array(
                'RiderOrder.rider_response !=' => 2
            ),

            ), 'RiderOrder.Rider'),
            'order' => 'Order.id DESC'
        ));

    }






    public function getAll()
    {
        $this->Behaviors->attach('Containable');

        return $this->find('all', array(
        'order' => 'Order.id DESC',
            'contain'=>array('User','DeliveryType','GoodType','RiderOrder' => array('conditions' => array(
                'RiderOrder.rider_response !=' => 2
            ),

        ), 'RiderOrder.Rider')));

    }





}

?>