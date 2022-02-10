<?php


class Driver extends AppModel
{
    public $useTable = 'driver';

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),
    );


    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('Driver.id' => $id)
        ));

    }

    public function getDetailsAgainstUserID($user_id)
    {

        return $this->find('first', array(
            'conditions' => array('Driver.user_id' => $user_id)
        ));

    }


    public function getAll()
    {

        return $this->find('all');

    }


    public function getNearestDriver($lat,$long,$distance)

    {

        $this->Behaviors->attach('Containable');
        return $this->find('all', array(



            'conditions' => array(


                'Driver.online'=> 1

            ),

            'contain'=>array('User'),
            'fields'=>array('( 3959 * ACOS( COS( RADIANS('.$lat.') ) * COS( RADIANS( Driver.lat ) )
                    * COS( RADIANS(Driver.long) - RADIANS('.$long.')) + SIN(RADIANS('.$lat.'))
                    * SIN( RADIANS(Driver.lat)))) AS distance','Driver.*','User.*'),
            'group' => array(
                'distance HAVING distance <'.$distance
            ),
            'order' => 'distance ASC',


            'recursive' => 0

        ));


    }






}

?>