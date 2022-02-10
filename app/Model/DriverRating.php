<?php


class DriverRating extends AppModel
{
    public $useTable = 'driver_rating';

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),

        'Driver' => array(
            'className' => 'User',
            'foreignKey' => 'driver_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),

        'Order' => array(
            'className' => 'Order',
            'foreignKey' => 'order_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),
    );
    
    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('DriverRating.id' => $id)
        ));

    }

    public function ifRatingExist($order_id)
    {

        return $this->find('count', array(
            'conditions' => array('DriverRating.order_id' => $order_id)
        ));

    }

    public function getAllRatings($driver_id)
    {

        return $this->find('all', array(
            'conditions' => array('DriverRating.driver_id' => $driver_id)
        ));

    }

    public function getRatingsAgainstOrder($order_id)
    {

        return $this->find('first', array(
            'conditions' => array('DriverRating.order_id' => $order_id)
        ));

    }



    public function getAvgRatings($rider_user_id)
    {
        return $this->find('first', array(
            'conditions' => array(
                'DriverRating.driver_id' => $rider_user_id,


            ),

            'fields'    => array(
                'AVG( DriverRating.star ) AS average',
                'COUNT(DriverRating.id) AS total_ratings'


            ),
            'group' => 'DriverRating.driver_id'
        ));


    }



}

?>