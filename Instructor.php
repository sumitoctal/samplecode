<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Client
 *
 * @author williamcrown
 */
class Instructor extends AppModel {

	public $hasMany = array(
        'UserTraining' => array(
            'className' => 'user_trainings',
            'foreignKey' => 'instructor_id',
        ), 
		/*'UserCertification' => array(
            'className' => 'user_certifications',
            'foreignKey' => 'instructor_id',
        ) */
    );
	
	var $virtualFields = array('name' => "CONCAT(first, ' ',last)");
	
	public $actsAs = array(
        'Multivalidatable'
    );
	
    public $validationSets = array(
		'register'=>array(	
			'first' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter your first name'
					)			
			),
			'last' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter your last name'
					)
			),
			'zip' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter your zip code'
					),
				'postal' => array(
						'rule' => array('postal'),
						'message' => 'Please enter a valid zipcode'
					)
			),
			'phone' => array(
				'phone' => array(
						'rule' => array('phone'),
						'message' => 'Please enter a valid phone number'
					)
			)
		),
		'profile'=>array(       
			'aboutme' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please describe yourself'
					)
			),
			'profile_image' => array(
					'extension' => array(
						'rule' => array('extension',array('jpg','jpeg','png')),
						'allowEmpty'=>true,
						'message' => 'Please upload JPG,PNG File'
						),
			),
			'first' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter your first name'
					)			
			),
			'last' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter your last name'
					)
			),
			'zip' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'Please enter youre zip code'
					),
				'postal' => array(
						'rule' => array('postal'),
						'message' => 'Please enter a valid zipcode'
					)
			),
			'phone' => array(
				'phone' => array(
						'rule' => array('phone'),
						'message' => 'Please enter a valid phone number'
					)
			)
		),
		'admin_change_password' => array(
            'new_password' => array(
                'minLength' => array(
                    'rule' => array('minLength', 6),
                    'message' => 'Passwords must be at least 6 characters long.'
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
		'minimerlin_step4'=>array(       
			'working_start_time' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please select your start time'
				)
			),
			'working_end_time' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message' => 'Please select your end time'
				),
				'checkEndTime' => array(
					'rule' => array('checkEndTime'),
					'message' => 'End time cant be less then start time'
				),
			),
			'working_days' => array(				
				'checkWorkingDays' => array(
					'rule' => array('checkWorkingDays'),
					'message' => 'Please select the days of the week that you work'
				),				
			),
		),
    );    

	public function checkWorkingDays()
	{
		if($this->data['Instructor']['working_days']=="")
			return false;
		else
			return true;
	}
	
	public function checkEndTime()
	{
	
		if(strtotime($this->data['Instructor']['working_start_time']) >= strtotime($this->data['Instructor']['working_end_time']))
			return false;
		else
			return true;
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
}	
?>
