<?php


class DeliveryType extends AppModel
{
    public $useTable = 'delivery_type';




    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('DeliveryType.id' => $id)
        ));

    }






    public function getAll()
    {

        return $this->find('all');

    }





}

?>