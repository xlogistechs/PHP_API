<?php
App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');
App::uses('Security', 'Utility');



class User extends AppModel
{
    public $useTable = 'user';


    public $belongsTo = array(
        'Country' => array(
            'className' => 'Country',
            'foreignKey' => 'country_id',
            

        ),
    );

    public $hasMany = array(
        'UserDocument' => array(
            'className' => 'UserDocument',
            'foreignKey' => 'user_id',


        ),
    );

   

    public function isEmailAlreadyExist($email){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array('email' => $email)
        ));

    }

    public function isfbAlreadyExist($fb_id){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array('fb_id' => $fb_id)
        ));

    }

    public function getDetailsAgainstFBID($fb_id){ /* irfan function*/

        return $this->find('first', array(
            'conditions' => array('fb_id' => $fb_id)
        ));

    }

    public function isSocialIDAlreadyExist($social_id){ /* irfan function*/

        return $this->find('first', array(
            'conditions' => array('social_id' => $social_id)
        ));

    }

    public function verifyToken($code,$email){

        return $this->find('count', array(
            'conditions' => array(

                'email' => $email,
                'token'=>$code

            )
        ));

    }
    public function getUserDetailsAgainstEmail($email){

        return $this->find('first', array(
            'conditions' => array('email' => $email)
        ));

    }

    public function isUsernameAlreadyExist($username){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array('username' => $username)
        ));

    }

    public function isphoneNoAlreadyExist($phone){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array('phone' => $phone)
        ));

    }


    public function editIsEmailAlreadyExist($email,$user_id){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array(
                'User.email' => $email,
                'User.id !='=>$user_id
            )
        ));

    }



    public function editIsUsernameAlreadyExist($username,$user_id){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array(
                'User.username' => $username,
                'User.id !='=>$user_id



                )
        ));

    }

    public function editIsphoneNoAlreadyExist($phone,$user_id){ /* irfan function*/

        return $this->find('count', array(
            'conditions' => array(
                'User.phone' => $phone,
                'User.id !='=>$user_id



            )
        ));

    }


    public function iSUserExist($id){

        return $this->find('count', array(
            'conditions' => array('id' => $id)
        ));

    }


    public function getMultipleUsersData($users){





        return $this->find('all', array(
            'conditions' => array('User.id IN' => $users)
        ));



    }

    public function getUsersCount($role){

        return $this->find('count', array(
            'conditions' => array(

                'User.role' => $role)
        ));

    }

    public function getTotalUsersCount(){

        return $this->find('count');

    }

    public function getUserDetailsFromID($user_id){
        $this->Behaviors->attach('Containable');
        return $this->find('first', array(
            'conditions' => array(
                'User.id' => $user_id
            ),
            'contain'=>array('Country','UserDocument'),
            'recursive' => 0


        ));

    }

    public function getUserDetailsFromIDAndRole($user_id,$role){

        return $this->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.role' => $role
            ),

            'recursive' => 0


        ));

    }

    public function getDriverDetails($user_id,$role){

        $this->Behaviors->attach('Containable');
        return $this->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.role' => $role
            ),
            'contain'=>array('Vehicle.RideType','DriverDocument'),

            'recursive' => 0


        ));

    }

    public function getSearchResults($keyword,$user_id){



        return $this->find('all', array(

            'conditions' => array(
                'User.username Like' => "$keyword%",
                'User.id !=' => $user_id,

            ),






            'recursive' => 0


        ));

    }

    public function verifyPassword($email,$old_password){


        $userData = $this->findByEmail($email, array(
            'id',
            'password',



        ));



        $passwordHash = Security::hash($old_password, 'blowfish', $userData['User']['password']);
       // $salt = Security::hash($old_password, 'sha256', true);

        if ($passwordHash == $userData['User']['password']) {


            return true;

        }else{
            return false;


        }



    }



    function updatepassword($password)
    {
        $passwordBlowfishHasher = new BlowfishPasswordHasher();
        $user['password'] = $passwordBlowfishHasher->hash($password);

        return $user;
    }


    public function getEmailBasedOnUserID($user_id){

        return $this->find('all', array(
            'conditions' => array(
                'User.id' => $user_id

            )
        ));


    }


    public function getNearestRiders($lat,$long,$distance)

    {

        $this->Behaviors->attach('Containable');
        return $this->find('all', array(



            'conditions' => array(


                'User.online'=> 1,
                'User.role'=> "rider"

            ),


            'fields'=>array('( 3959 * ACOS( COS( RADIANS('.$lat.') ) * COS( RADIANS( User.lat ) )
                    * COS( RADIANS(User.long) - RADIANS('.$long.')) + SIN(RADIANS('.$lat.'))
                    * SIN( RADIANS(User.lat)))) AS distance','User.*'),
            'group' => array(
                'distance HAVING distance <'.$distance
            ),
            'order' => 'distance ASC',


            'recursive' => 0

        ));


    }




    public function getAdminDetails(){

        return $this->find('all', array(
            'conditions' => array(
                'User.role' => "admin"

            ),

        ));


    }

    public function verifyCode($email,$code){

        return $this->find('count', array(
            'conditions' => array(
                'User.email' => $email,
                'User.token'=>$code

            ),

        ));


    }
    public function verify($email,$user_password,$role)
    {

        if ($email != "") {
            $userData = $this->find('all', array(
                'conditions' => array(
                    'User.email' => $email,
                    'User.role' => $role

                )
            ));


            /*$userData = $this->findByEmail($email, array(
            'user_id',
           'email',
            'password',
            'salt'
           ));*/
            if (empty($userData)) {


                return false;

            }
        }
        $passwordHash = Security::hash($user_password, 'blowfish', $userData[0]['User']['password']);
        $salt = Security::hash($user_password, 'sha256', true);

        if ($passwordHash == $userData[0]['User']['password'] ) {
            return $userData;
        } else {

            return false;


        }



    }


    public function verifyPhoneNoAndPassword($phone_no,$user_password)
    {


            $userData = $this->find('all', array(
                'conditions' => array(
                    'User.phone_no' => $phone_no

                )
            ));


            /*$userData = $this->findByEmail($email, array(
            'user_id',
           'email',
            'password',
            'salt'
           ));*/
            if (empty($userData)) {


                return false;

            }

        $passwordHash = Security::hash($user_password, 'blowfish', $userData[0]['User']['password']);
        $salt = Security::hash($user_password, 'sha256', true);

        if ($passwordHash == $userData[0]['User']['password'] ) {
            return $userData;
        } else {

            return false;


        }



    }



    public function getUsers($role){

        return $this->find('all', array(

            'conditions' => array(

                'User.role' => $role

            ),
            'order' => array('User.id DESC'),
        ));

    }

    public function getAllUsers(){

        return $this->find('all', array(


            'order' => array('User.id DESC'),
        ));

    }


    public function getUserDetailsFromEmail($email){
        $this->Behaviors->attach('Containable');
        return $this->find('first', array(
            'conditions' => array(

                'User.email' => $email

            ),


        ));


    }





    public function beforeSave($options = array())
    {
        $passwordBlowfishHasher = new BlowfishPasswordHasher();


        if (isset($this->data[$this->alias]['password'])) {
            $password = $this->data[$this->alias]['password'];

            $salt = $password;

            $this->data['User']['password'] = $passwordBlowfishHasher->hash($password);
           
        }
        return true;
    }


}?>