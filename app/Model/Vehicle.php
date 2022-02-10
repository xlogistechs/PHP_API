<?php


class Vehicle extends AppModel
{
    public $useTable = 'vehicle';

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),

        'VehicleType' => array(
            'className' => 'VehicleType',
            'foreignKey' => 'vehicle_type_id',
            //'fields' => array('User.id','User.email','User.username','User.image','User.device_token')

        ),


    );


    public function getDetails($id)
    {

        return $this->find('first', array(
            'conditions' => array('Vehicle.id' => $id)
        ));

    }

    public function getUserVehicle($user_id)
    {
        $this->Behaviors->attach('Containable');

        return $this->find('first', array(
            'conditions' => array('Vehicle.user_id' => $user_id),
            'contain'=>array('User','VehicleType'),
        ));

    }


    public function getNearestVehicle($lat,$long,$ride_type_id,$distance)

    {

        $this->Behaviors->attach('Containable');
        return $this->find('all', array(



            'conditions' => array(


                'Vehicle.online'=> 1,
                'Vehicle.ride_type_id'=> $ride_type_id

            ),

            //'contain'=>array('User'),
            'fields'=>array('( 3959 * ACOS( COS( RADIANS('.$lat.') ) * COS( RADIANS( Vehicle.lat ) )
                    * COS( RADIANS(Vehicle.long) - RADIANS('.$long.')) + SIN(RADIANS('.$lat.'))
                    * SIN( RADIANS(Vehicle.lat)))) AS distance','Vehicle.*','User.*','Driver.*'),
            'group' => array(
                'distance HAVING distance <'.$distance
            ),
            'order' => 'distance ASC',


            'recursive' => 0

        ));


    }






    public function getAll()
    {

        return $this->find('all');

    }





}

?>