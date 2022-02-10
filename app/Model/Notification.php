<?php


class Notification extends AppModel
{
    public $useTable = 'notification';





    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('Notification.id' => $id)
        ));

    }



    public function getAll()
    {

        return $this->find('all');

    }



    public function getUserNotifications($user_id)
    {

        return $this->find('all', array(
            'conditions' => array(
                'Notification.user_id' => $user_id


            ),

        ));

    }

    










}

?>