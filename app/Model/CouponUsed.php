<?php


class CouponUsed extends AppModel
{

    public $belongsTo = array(


        'Order' => array(
            'className' => 'Order',
            'foreignKey' => 'order_id',



        ),
    );
    public $useTable = 'coupon_used';


    public function ifCouponCodeUsedByUser($coupon_id,$user_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'CouponUsed.coupon_id' => $coupon_id,
                'CouponUsed.user_id' => $user_id,



            )
        ));
    }
    public function countCouponUsed($coupon_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'CouponUsed.coupon_id' => $coupon_id,



            )
        ));
    }
    /*
       public function getRestaurantCoupon($id)
       {
           return $this->find('all', array(
               'conditions' => array(

                   'RestaurantCoupon.id' => $id






               )
           ));
       }*/

}
?>