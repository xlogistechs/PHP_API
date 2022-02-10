<?php


class OrderNotification extends AppModel
{
    public $useTable = 'order_notification';




    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('OrderNotification.id' => $id)
        ));

    }






    public function getAll()
    {

        return $this->find('all');

    }

    public function getUserUnReadNotifications($user_id,$order_id)
    {

        return $this->find('count', array(
            'conditions' => array(
                'OrderNotification.receiver_id' =>$user_id,
                'OrderNotification.order_id' =>$order_id,
                'OrderNotification.read' =>0)
        ));

    }

    public function readNotification($user_id){

        $this->updateAll(
            array('OrderNotification.read' => 1),
            array('OrderNotification.receiver_id' => $user_id)
        );

    }




}

?>