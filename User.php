<?php
App::uses('AppModel', 'Model');
App::uses('CakeSession', 'Model/Datasource');
/**
 * User Model
 *
 * @property Role $Role
 */
class User extends AppModel {
/**
 * Validation rules
 *
 * @var array
 */
	public $displayField = 'username';
	
	public $name = 'User'; 
	
	public $actsAs = array(
        'Multivalidatable'
    );
	
	public function beforeSave($options = array()) {
	
		if(!empty($this->data['User']['email']))
		{
			$this->data['User']['username']=$this->data['User']['email'];
		}
		return true;	
    }
	
	public $validationSets = array(
		'admin' => array(
            'name' => array(
                'notEmpty' => array(
                    'rule' => 'notEmpty',
                    'message' => 'Name is required.'
                )
            ),
            'email' => array(
                'notEmpty' => array(
                    'rule' => 'notEmpty',
                    'message' => 'Email is required.'
                ),
                'isUnique' => array(
                    'rule' => 'isUnique',
                    'message' => 'Email already exists.'
                ),
                'email' => array(
                    'rule' => 'email',
                    'message' => 'Invalid Email.'
                )
            ),
			'password2' => array(
                'notEmpty' => array(
                    'rule' => 'notEmpty',
                    'message' => 'Password is required.'
                ),
				'minLength' => array(
                    'rule' => array('minLength', 6),
                    'message' => 'Passwords must be at least 6 characters long.'
                )
            )
        ),
		'admin_change_password' => array(
            'new_password' => array(
                'minLength' => array(
                    'rule' => array('minLength', 8),
                    'message' => 'Passwords must be at least 8 characters long.'
                ),
                'notEmpty' => array(
                    'rule' => 'notEmpty',
                    'message' => 'New password is required.'
                ),
            ),
            'confirm_password' => array(
                'notEmpty' => array(
                    'rule' => 'notEmpty',
                    'message' => 'Confirm password is required.',
                    'last' => true
                ),
                'identicalFieldValues' => array(
                    'rule' => array('identicalFieldValues', 'new_password'),
                    'message' => 'Confirm password must be same as new password.'
                )
            )
        ),
		'login' => array(
			'email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter your email address'
				)
			),
			'password' => array(
				'notEmpty' => array(
					'rule' => 'notEmpty',
					'message' => 'Please enter your password'
				)
			)
		),		
		'change_password' => array(		
			'old_password' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter your current password'
				),
				'checkOldPassword' => array(
					'rule' => array('checkOldPassword', 'old_password'),
					'message' => 'Current password is invalid'
				)
			),
			'password' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter a password'
				)
			),
			'cpassword' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please confirm the password'
				),
				'identicalFieldValues' => array(
					'rule' => array('identicalFieldValues', 'password' ),
					'message' => 'Passwords do not match.'
				)
			)
		),
		'forget_password' => array(		
			'email' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter an email address'
				),
				'checkvalidemail' => array(
					'rule' => array('checkvalidemail', 'email'),
					'message' => 'The email address you entered is not found'
				)
			)
		),
		'profile' => array(
			'username' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter your username'
				),
				/* 'alphaNumeric' => array(
					'rule' => array('alphaNumeric'),
					'message' => 'Username must be alphnumeric.',
				), */
				'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Username already exists.',
					//'on' => 'create'
				)
			),
			
			'org_password' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter your password'
				)
			),
			'cpassword' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter the confirm password'
				),
				'identicalFieldValues' => array(
					'rule' => array('identicalFieldValues', 'org_password' ),
					'message' => 'Confirm password does not match'
				)
			),
			'role_id' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please select the type of user'
				)
			),
			'email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the email of user'
				),
				'email' => array(
					'rule' => array('email'),
					'message' => 'Email is invalid'
				),
				'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Email already exists.',
					//'on' => 'create'
				)
			),
			
			'first_name' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the first name of user'
				)
				
			),
			'surname' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the last name of user'
				)
			),
			'image' => array(
				'checkImg' => array(
					'rule' => array('checkImg'),
					'message' => 'Please enter the valid image type.'
				),
				'checkSize' => array(
					'rule' => array('checkSize'),
					'message' => 'Invalid image size'
				)
			),
			'status' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please select the status of user',
				)
			),
			
		),
		'register' => array(
			'username' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the username'
				),
				'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Username already exists.',
					
				)
			),
			'cemail' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter email'
				),
				'identicalFieldValues' => array(
					'rule' => array('identicalFieldValues', 'email' ),
					'message' => 'Email addresses do not match'
				)
			),
			'password' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter the password'
				)
			),
			'org_password' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter the password'
				)
			),
			'cpassword' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please enter the confirm password'
				),
				'identicalFieldValues' => array(
					'rule' => array('identicalFieldValues', 'org_password' ),
					'message' => 'Passwords do not match'
				)
			),
			'role_id' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please select the type of user'
				)
			),
			'email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the email'
				),
				'email' => array(
					'rule' => array('email'),
					'message' => 'Email is invalid'
				) ,
				'checkEmailExist' => array(
					'rule' => array('checkEmailExist', 'email'),
					'message' => 'Email already exists'
				)
				/* 'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Email already exists.',
					//'on' => 'create'
				) */
			),
			'first_name' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the first name of user'
				)
			),
			'last_name' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the last name of user'
				),
			)
			
			
		),
		'profile_edit' => array(
			'username' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the username'
				),
				'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Username already exists.',
					//'on' => 'create'
				)
			),
			'role_id' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please select the type of user'
				)
			),
			'email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the email of user'
				),
				'email' => array(
					'rule' => array('email'),
					'message' => 'Email is invalid'
				),
				'isUnique' => array(
					'rule' => array('isUnique'),
					'message' => 'Email already exists.',
					//'on' => 'create'
				)
			),
			'first_name' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the first name of user'
				)
			),
			'surname' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter the surname of user'
				),
			),
		),
		'feedback'=>array(
			'user_name' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter name'
				),
			),
			'user_email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter email'
				),
				'email' => array(
					'rule' => array('email'),
					'message' => 'Email is invalid'
				)
			),
			'subject' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter subject'
				)
			),
			'comment' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter comment'
				),
			)
		
		),
		'add_email' => array(
			'email' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'Please enter email'
				),
				'unique' => array(
					'rule'    => 'isUnique',
					'message' => 'This email has already been taken.'
				)	
			)
		)
			
	);
	
	function check_string($field = array()) {
        $user = $field['username'];
        $value = substr($user, 0, 1);

        if (preg_match('/[A-Za-z]$/', $value) == true) {
            return true;
        } else {
            return false;
        }
        return true;
    }
	
	function checkImg($field = array()){
		if($field['image']['name'] != ''){
			return $this->checkExt($field['image']['name'], array('jpg', 'jpeg', 'png', 'gif'));
		}
			return true;
	}
	
	function checkSize($field = array()){
		if($field['image']['name'] != ''){
			
			if((($field['image']['size'])/1024) > PROFILE_IMG_SIZE)
				return false;
		}
			return true;
	}
	
	function checkUsername($field = array()){
		$userId  = CakeSession::read('Auth.User.id');
		$options = array(
			'conditions'=>array(
				'User.username' => $field['username'],
				'User.id != '   => $userId
			)
		);
		$count = $this->find('count', $options);
		if($count > 0)
			return false;
		return true;
	}
	
	function checkEmail($field = array()){
		$userId  = CakeSession::read('Auth.User.id');
		$options = array(
			'conditions'=>array(
				'User.email' => $field['email'],
				'User.id != '   => $userId
			)
		);
		$count = $this->find('count', $options);
		if($count > 0)
			return false;
		return true;
	}
	
	function checkOldPassword( $field = array(), $password = null ){
		$userId = CakeSession::read('Auth.User.id');
		$options = array(
			'conditions'=>array(
				'User.password' => Security::hash($field['old_password'], null, true),
				'User.id' => $userId
			)
		);
        
		$count	=	$this->find('count', $options);
		if($count == 1)
			return true;
	
		return false;
	}
	
	function checkvalidemail( $field = array(), $password = null ){
		
		$options = array(
			'conditions'=>array(
				'User.email' => $field['email'],
				
			)
		);
        
		$count	=	$this->find('count', $options);
		if($count == 1)
			return true;
		
		return false;
	}
	
	function identicalFieldValues($field=array(), $compare_field=null){

	
		foreach( $field as $key => $value ){
            $v1 = $value;
            $v2 = $this->data[$this->name][$compare_field ];
            if($v1 !== $v2) {
                return false;
            } else {
                continue;
            }
        }
        return true;
    }
	
	function forget_password($username){
		
		$options = array(
				'conditions'=>array(
				'OR'=>array(
					'User.username' => $username,
					'User.email' => $username,
					)),
				'fields'=>array(
					'User.id','User.username','User.email',
					)
			);
		$row = $this->find('first', $options);
		
		
		if(count($row)>=1)
		{
			$pass=$this->pass_random();
			
			$this->updateAll(array('User.password'=>"'".Security::hash($pass, null, true)."'"), array('User.id'=>$row['User']['id']));

			$arr['pass']=$pass;	
			$arr['id']=$row['User']['id'];
			$arr['username']=$row['User']['username'];	
			$arr['email']=$row['User']['email'];			
		
			return $arr;
		}
	
		return false;
	}
	
	function pass_random($length = 6)
	{      
		$chars = 'bcdfghjklmnprstvwxzaeiou0123456789';
		$result="";
		for ($p = 0; $p < $length; $p++)
		{
			$result .= ($p%2) ? $chars[mt_rand(19, 23)] : $chars[mt_rand(0, 18)];
		}

		return $result;
	}
	
	function getuser_details($username){	
		$options = array(
			'conditions'=>array(
			'OR'=>array(
				'User.username' => $username,
				'User.email' => $username,
				)),
			
		);
		$row = $this->find('first', $options);	
		return $row;	
	}
	
	function getOwnerName(){
		$uData = $this->find('list',array('conditions'=>array('User.status=1','User.role_id=7'),'fields'=>array('User.id','User.first_name')));
		$userArray = array(''=>'--Select Option--');
		foreach($uData as $key => $uDataVal)
		{
			$userArray[$key]=$uDataVal;
		}
		return $userArray;
	}
	
	function getUserListByRole($role_id=8){
	
		$usr=$this->find('all',array('conditions'=>array('User.status'=>1,'User.role_id'=>$role_id),'fields'=>array('User.first_name','User.surname','User.id')));
		foreach($usr as $ur)
		{
		$user_list[$ur['User']['id']]=$ur['User']['first_name'].' '.$ur['User']['surname'];
		}
		return $user_list;
	
	}
	
	/* function getAllUserList()
	{
	
		$usr=$this->find('all',array('conditions'=>array(),'fields'=>array('User.first_name','User.surname','User.id')));
		foreach($usr as $ur)
		{
		$user_list[$ur['User']['id']]=$ur['User']['first_name'].' '.$ur['User']['surname'];
		}
		return $user_list;		
	} */
	
	function getAllUserList()
	{
	
		$user_list=$this->find('all',array('conditions'=>array(),
		'joins' => array(
                array(
                    'table' => 'instructors',
                    'alias' => 'Instructor',
                    'type' => 'left',
                    'conditions' => array(
                        'User.id = Instructor.user_id'
                    )
                ),
				 array(
                    'table' => 'clients',
                    'alias' => 'Client',
                    'type' => 'left',
                    'conditions' => array(
                        'User.id = Client.user_id'
                    )
                )
            ),'fields'=>array('User.id','User.role_id','User.email','Instructor.first','Instructor.last','Client.first','Client.last')));
		
		return $user_list;		
	}
	
	function checkEmailExist($field = array())
	{
		$email=$field['email'];		
		$cnt=$this->find('count',array('conditions'=>array('email'=>$email,'status'=>1)));
		if($cnt>=1)
		return false;
		else
		return true;
	
	}
	
}
