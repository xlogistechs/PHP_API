<?php



class OrderTransaction extends AppModel
{
    public $useTable = 'order_transaction';

    public $belongsTo = array(

        'Order' => array(
            'className' => 'Order',
            'foreignKey' => 'order_id',


        ),

    );

    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'OrderTransaction.id' => $id





            )
        ));
    }


    public function getTransactionAgainstOrderID($order_id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'OrderTransaction.order_id' => $order_id





            )
        ));

    }







}