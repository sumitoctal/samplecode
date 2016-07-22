<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor test.
 */

/**
 * Description of InstructorsController
 *
 * @author williamcrown
 */

/* load CakePHP Email component */

App::uses('CakeEmail', 'Network/Email');

class InstructorsController extends AppController {
    public $helpers = array('Html', 'Form');
    public $uses = array('User','Instructor'); 	

	public $components = array('General');    
   
    public function index() {
      $this->redirect(array('action' => 'calendar'));
    }    
    	
    
	public function beforeFilter(){	
	
		parent::beforeFilter();
		
		$this->Security->blackHoleCallback = 'blackhole';		
				
		$this->Auth->allow('signup','signup_thanks','uniqueemail','locationsearchresults','locsearchres','getclientid_byemail','invite_client_hard_way','invite_client_hard_way_ajax','booking_step3','hold_booking_cron','change_price','check_email','upload_profile_video','get_trainingsubtype','privacy_policy','term_condition','refer_friend_after_30_days','instructor_transaction_not_done','instructor_first_session_happen','client_reminder_email','wepay_confirm','signup_facebook','signup_google');
		$this->Auth->deny(array('portal','messages'));
			
		$this->Security->validatePost = false;
		$this->Security->csrfCheck = false;
			
		$this->layout = 'instructor';		
		
		if($this->Auth->user('role_id')==2) 		
		{
            $this->redirect(array('controller'=>'clients','action' => 'agenda'));
		}	
			
	}	

    public function admin_index($char="A") {
	
		$this->layout = 'admin';
		$this->loadModel('User');
		$this->loadModel('Instructor');		
		$this->Instructor->bindModel(array('belongsTo'=>array('User')),false);
		$filters = array();
		
        if(!isset($this->request->params['named']['page'])) {
			$this->Session->delete('AdminSearch');
            $this->Session->delete('Url');
        }
		
        $filters[] = array('User.role_id'=>3);
	
        if (!empty($this->request->data)) {
            $this->Session->delete('AdminSearch');
            $this->Session->delete('Url');
			
            App::uses('Sanitize', 'Utility');
            if (!empty($this->request->data['User']['email'])) {
                $email = Sanitize::escape($this->request->data['User']['email']);
                $this->Session->write('AdminSearch.email', $email);
            }
            if (!empty($this->request->data['User']['first'])) {
                $first_name = Sanitize::escape($this->request->data['User']['first']);
                $this->Session->write('AdminSearch.first', $first_name);
            }
            if (!empty($this->request->data['User']['last'])) {
                $last_name = Sanitize::escape($this->request->data['User']['last']);
                $this->Session->write('AdminSearch.last', $last_name);
            }
           
            if (isset($this->request->data['User']['status']) && $this->request->data['User']['status'] != '') {
                $status = Sanitize::escape($this->request->data['User']['status']);
                $this->Session->write('AdminSearch.status', $status);
                $defaultTab = strtolower(Configure::read('Status.' . $status));
            }
        }
		
		
		$search_flag = 0;
        $search_status = '';
		
		if(empty($this->request->data['User']))
		$filters[] = array("Instructor.last like '".$char."%'");
		
		
        if($this->Session->check('AdminSearch')) {
            $keywords = $this->Session->read('AdminSearch');			
            foreach ($keywords as $key => $values) {
                if ($key == 'status') {
                    $search_status = $values;
                    $filters[] = array('User.' . $key => $values);
                } else if ($key == 'email') {
                    $filters[] = array('User.' . $key => $values);
                }  else if ($key == 'first') {
                    $filters[] = array('Instructor.' . $key . ' LIKE' => "%" . $values . "%");
                } else if ($key == 'last') {
                    $filters[] = array('Instructor.' . $key . ' LIKE' => "%" . $values . "%");
                } else {				
                    $filters[] = array('User.' . $key => $values);
                }
            }

            $search_flag = 1;
        }
		$this->set(compact('search_flag', 'defaultTab','char'));
		
        /* $this->paginate = array(
            'Instructor' => array(
                'limit' => Configure::read('App.AdminPageLimit'),
                'order' => array('Instructor.created' => 'DESC'),
                'conditions' => $filters  
        ));		
        $data = $this->paginate('Instructor'); */
		$joins[]=array(
            'table'=> 'alerts',
            'type' => 'left',
            'alias' => 'Alert',
            'conditions' => array('Alert.to = Instructor.user_id','Alert.type'=>'wepay_confirm')
        );
		$data = $this->Instructor->find('all',array('conditions'=>$filters,'fields'=>array('instructor.*','User.*','alert.*'),'joins'=>$joins));
				
        $this->set(compact('data'));
        $this->set('title_for_layout', __('List Instructors', true));
    }
	
	public function admin_edit($id = null) {
		$this->layout = 'admin';
		$this->set('title_for_layout', __('Edit Instructors', true));
        $this->Instructor->id = $id;        
        if (!$this->Instructor->exists()) 
		{
            throw new NotFoundException(__('Invalid Instructor'));
        }
		
		$this->loadModel('User');		
		$this->loadModel('UserCertification');
		$this->loadModel('UserTraining');
		$this->loadModel('UserPricing');
		
		$this->Instructor->bindModel(array('belongsTo'=>array('User')),false);
		if ($this->request->is('post') || $this->request->is('put')) 
		{
            if (!empty($this->request->data)) 
			{ 				
				App::uses('Sanitize', 'Utility');
                $action = Sanitize::clean($this->request->data['Instructor'], array('escape' => true));
                $this->request->data['Instructor'] = $action;

                $this->Instructor->set($this->request->data['Instructor']);
                $this->Instructor->setValidation('profile');
				
				if($this->request->data['Instructor']['pay']==DEFAULT_PAYMENT_PERC)
				$this->request->data['Instructor']['pay']=null;
				
				if($this->request->data['Instructor']['admin_discount']==DEFAULT_APP_DISCOUNT)
				$this->request->data['Instructor']['admin_discount']=null;
				
                if ($this->Instructor->save($this->request->data, array('validate' => 'only'))) 
				{					
					if($this->Instructor->saveAll($this->request->data))
					{
						$this->Session->setFlash(__('Instructor updated'), 'admin_flash_success');
						$this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
						
					}	
				}else{
						$data= $this->Instructor->find('first', array('conditions' => array('Instructor.id' => $id),'fields'=>array('avatar')));			
						$this->set(compact('data'));
						$this->Session->setFlash(__('Instructor could not be saved. Please correct errors.'), 'admin_flash_error');
				}	
			}
		}
		else 
		{
            $this->request->data = $this->Instructor->read(null, $id);
			$data = $this->Instructor->find('first', array('conditions' => array('Instructor.id' => $id),'fields'=>array('avatar')));		
			$this->set(compact('data'));			
            unset($this->request->data['User']['password']);
        }
		
		
		$instructor = $this->Instructor->findByid($id);
		
		$this->loadModel("Trainingtype");	
       	$this->set('instructor', $instructor);        
        $this->set('user_id', $instructor['Instructor']['user_id']);
				
	}
	
	public function save_imagefromadmin($no=1,$id){
		$path = $_FILES['files']['name'][0];
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$ext = strtolower($ext);
		
		
		if(in_array($ext,array('jpg','png','jpeg','gif')))
		{	
			if(move_uploaded_file($_FILES['files']['tmp_name'][0],'uploads/profile/'.$_FILES['files']['name'][0]))
			{
				$dt['id']=$id;
				$dt['file'.$no]=$_FILES['files']['name'][0];
				$this->Instructor->save($dt);
				echo 'SUCCESS';
			}
		}
		else
		{
			echo 'FAILED';	
		}
		die();		
	
	}
	
	public function add_videofromadmin() {		
		$num = $_POST['num'];
		$url = $_POST['url'];
		$instruct_id = $_POST['id'];
		$id = $this->General->getYouTubeId($url);
		if($id)
		{
			$dt['id']=$instruct_id;
			$dt['video'.$num]=$url;
			$this->Instructor->save($dt);
			echo $id;
		}
		else
		echo 'FAILED';		
		
		die();	
	}
	
	public function admin_view($id = null) {
		$this->set('title_for_layout', __('View Instructors', true));
		$this->layout = 'admin';
		$this->loadModel('UserCertification');	
		$this->loadModel('Certification');
		$this->loadModel('UserTraining');
		$this->loadModel('Trainingtype');
        $this->Instructor->id = $id;
		
        if (!$this->Instructor->exists()) {
            throw new NotFoundException(__('Invalid Instructor'));
        }
		$this->Instructor->Behaviors->attach('Containable');
			
		$this->User->bindModel(array('hasMany'=>array('UserTraining','UserPricing')));
		
		$this->UserTraining->bindModel(array('belongsTo'=>array('Trainingtype')));
		
		$this->Instructor->bindModel(array('belongsTo'=>array('User')));		
		
		$data = $this->Instructor->find('first', 
							  array('conditions' => array('Instructor.id' => $id),
									'contain' => array(
										'User'=>array(											
											'UserTraining'=>array(
												'Trainingtype'=>array('fields'=>'name')
											)
										)
									 )
								   )
								);
        $this->set('instructor', $data);
		
    }
	
	public function admin_instructor_sale_client($instructor_user_id = null) {
		$this->set('title_for_layout', __('Sales History', true));
		$this->layout = 'admin';
		$this->loadModel('Instructor');
		$this->loadModel('Availability');
		$this->loadModel('Trainingtype');
		$this->loadModel('UserPricing');
		$this->loadModel('Tclass');
		$this->loadModel('Order');
		$this->loadModel('Booking');
		$this->loadModel('Message');
		
		$orderfilters = array('Booking.id ASC');
		
		$joins[]=array(
            'table'=> 'instructors',
            'type' => 'INNER',
            'alias' => 'Instructor',
            'conditions' => array('Booking.instructor_id = Instructor.id')
        );
		
		$joins[]=array(
            'table'=> 'clients',
            'type' => 'INNER',
            'alias' => 'Client',
            'conditions' => array('Booking.user_id = Client.user_id')
        );
		
		$joins[]=array(
			'table'=> 'availabilities',
            'type' => 'left',
            'alias' => 'Availability',
            'conditions' => array('Booking.availability_id = Availability.id')
		);
        
        $joins[]=array(
            'table'=> 'trainingtypes',
            'type' => 'left',
            'alias' => 'Trainingtype',
            'conditions' => array('Booking.trainingtype_id = Trainingtype.id')
		);
		
		$joins[]=array(
            'table'=> 'user_pricings',
            'type' => 'left',
            'alias' => 'UserPricing',
            'conditions' => array('Booking.price_id = UserPricing.id')
		);
		
		$joins[]=array(
            'table'=> 'tclasses',
            'type' => 'left',
            'alias' => 'Tclass',
            'conditions' => array('Booking.class_id = Tclass.id')
        );
		$joins[]=array(
            'table'=> 'orders',
            'type' => 'left',
            'alias' => 'Order',
            'conditions' => array('Booking.invite_id = Order.invite_id')
        );
        $bookings = $this->Booking->find('all', 
							array('conditions'=>array(
									'Booking.instructor_user_id'=>$instructor_user_id,
									'Booking.type !='=>'hold',
									'Booking.status'=>1				
									),
									'fields'=>array('Booking.*','Instructor.id','Instructor.first','Instructor.last','Instructor.avatar','Client.id','Client.first','Client.last','Client.avatar','Trainingtype.name','Availability.address','UserPricing.miniutes','UserPricing.days','UserPricing.month_price','UserPricing.price','Tclass.name','Tclass.id','Order.created','Order.payment_mthod','Order.id'),
									'joins'=>$joins,
									'group'=>'program_id',
									'order'=>$orderfilters					
								  )							
								);							
		$this->set(compact('bookings','instructor_user_id'));						
	}
	
    public function admin_delete($id = null) {
		$this->loadModel('User');
		$this->loadModel('Availability');
		$this->loadModel('AvailabilityTraining');
		$this->loadModel('UserCertification');
		$this->loadModel('UserTraining');
		
		$this->User->id = $id;
		if(!$this->User->exists()) 
		{	
            throw new NotFoundException(__('Invalid Instructor'));
        }		
		
		$instructor_data = $this->Instructor->find('first',array('conditions'=>array('Instructor.user_id'=>$id)));			
		if(!empty($instructor_data['Instructor']['avatar'])) 
		{
			if (file_exists( USER_PROFILE_DIR .$instructor_data['Instructor']['avatar'])) 
			{
				@unlink(USER_PROFILE_DIR.$instructor_data['Instructor']['avatar']); 
				
			}
			if (file_exists(USER_PROFILE_THUMB_DIR . $instructor_data['Instructor']['avatar'])) 
			{
				@unlink(USER_PROFILE_THUMB_DIR.$instructor_data['Instructor']['avatar']);	
			}
		}	
		$this->User->bindModel(array('hasMany' => array('Availability' => array('dependent' => true), 'AvailabilityTraining' => array('dependent' => true), 'UserCertification' => array('dependent' => true), 'UserTraining' => array('dependent' => true))), false);
		$this->User->bindModel(array('hasOne' => array('Instructor' => array('dependent' => true))), false);
				
		if ($this->User->deleteAll(array('User.id' => $id), true)) 
		{	
			$this->Session->setFlash(__('Instructor deleted'), 'admin_flash_success');
			$this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
		}	
		
	}
	
    public function admin_change_password($id = null) {
		$this->set('title_for_layout', __('Change Password', true));
		$this->layout = 'admin';
        $this->Instructor->id = $id;
        if (!$this->Instructor->exists()) 
		{
            throw new NotFoundException(__('Invalid Instructor'));
        }
		if ($this->request->is('post') || $this->request->is('put')) 
		{	
            if (!empty($this->request->data)) 
			{
				$this->Instructor->set($this->request->data);
                $this->Instructor->setValidation('admin_change_password');
                if($this->Instructor->validates()) 
				{
					$new_password = $this->request->data['Instructor']['new_password'];
                    $this->request->data['User']['password'] = Security::hash($this->request->data['Instructor']['new_password'], null, true);
					
					$data = $this->Instructor->find('first',array('conditions'=>array('Instructor.id'=>$id),'fields'=>array('user_id')));
					$this->request->data['User']['id'] = $data['Instructor']['user_id'];
					unset($this->request->data['Instructor']);
										
                    if ($this->User->save($this->request->data)) 
					{
                        $this->Session->setFlash(__('Password changed', true), 'admin_flash_success');
						$this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
                    } 
					else 
					{
                        $this->Session->setFlash(__('The Password could not be changed. Please try again.', true), 'admin_flash_error');
                    }
				}

			}
		}
		$this->set(compact('id'));	
	}
	
	public function admin_process() {		
        if (!$this->request->is('post')) 
		{
            throw new MethodNotAllowedException();
        }		
		$this->loadModel('User');
        if (!empty($this->request->data)) 
		{	
            App::uses('Sanitize', 'Utility');
            $action = Sanitize::escape($this->request->data['Instructor']['pageAction']);
            $ids = $this->request->data['Instructor']['id'];
            if (count($this->request->data) == 0 || $this->request->data['Instructor'] == null) 
			{
                $this->Session->setFlash(__('No items selected.'), 'admin_flash_error');
                $this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
            }			
			
			if ($action == "Delete") 
			{					
				foreach ($ids as $client_id) 
				{
					$client_data = $this->Instructor->find('first',array('conditions'=>array('Instructor.id'=>$client_id)));				
					if (!empty($client_data['Instructor']['avatar'])) 
					{
						
						if (file_exists( USER_PROFILE_DIR .$client_data['Instructor']['avatar'])) 
						{
							@unlink(USER_PROFILE_DIR.$client_data['Instructor']['avatar']); 
							
						}
						if (file_exists(USER_PROFILE_THUMB_DIR . $client_data['Instructor']['avatar'])) 
						{
							@unlink(USER_PROFILE_THUMB_DIR.$client_data['Instructor']['avatar']);	
						}
					}
					
					$data = $this->Instructor->find('first',array('conditions'=>array('Instructor.id'=>$client_id),'fields'=>array('user_id')));
					
					$this->User->delete(array('User.id' =>$data['Instructor']['user_id']),false);
					$this->Instructor->delete(array('Instructor.id' => $client_id), false);
					
				}
				$this->Session->setFlash(__('Instructor deleted'), 'admin_flash_success');
				$this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
			}
            if ($action == "Activate") 
			{	
				$client_data = $this->Instructor->find('all',array('conditions'=>array('Instructor.id'=>$ids),'fields'=>array('user_id')));
				
				foreach ($client_data as $user_id) 
				{
					 $userIds[] = $user_id['Instructor']['user_id'];							
				}				
				$this->User->updateAll(array('User.status' => Configure::read('App.Status.active')), array('User.id' =>$userIds));
				$this->Session->setFlash(__('Instructors activated'), 'admin_flash_success');
                $this->redirect(array('controller'=>'instructors','action'=>'admin_index')); 
            }

            if ($action == "Deactivate") 
			{
				$client_data = $this->Instructor->find('all',array('conditions'=>array('Instructor.id'=>$ids),'fields'=>array('user_id')));
				
				foreach($client_data as $user_id) 
				{
					$userIds[] = $user_id['Instructor']['user_id'];							
				}
				$this->User->updateAll(array('User.status' => Configure::read('App.Status.inactive')), array('User.id' => $userIds));
				$this->Session->setFlash(__('Instructors deactivated'), 'admin_flash_success');
				$this->redirect(array('controller'=>'instructors','action'=>'admin_index'));
            }
        } 
		else 
		{
            $this->redirect(array('controller' => 'instructors', 'action' => 'index'));
        }
    }

	public function admin_ins_class_list($ins_user_id=null) {
		
		$this->set('title_for_layout',__('Classes'));
		$this->layout = 'admin';
		
		$this->loadModel("Tclass");        	
		$this->loadModel("Fitnessgoal");        	
		$this->loadModel("Availability");        	
		$this->loadModel("UserPricing");        	
		
		$this->Tclass->bindModel(array('belongsTo'=>array('User'=>array('className'=>'User','foreignKey'=>'user_id')),'hasMany'=>array('Availability'=>array('className'=>'Availability','foreignKey'=>'class_id','conditions' => array('Availability.status' => '1'),'order'=>'Availability.startdate ASC'))
		));
		
		$tclasses = $this->Tclass->find('all', array(
							'conditions' => array('Tclass.user_id' => $ins_user_id),
							'order' => array(
								'Tclass.created' => 'desc'
							),
							'fields' => array(
									'Tclass.*','User.*'
								   )
							));
		
		$goals=$this->Fitnessgoal->getAllList();			
		$this->set('goals', $goals);
				
		foreach($tclasses as $key=>$val){
			$programs = $this->UserPricing->find('all',array('conditions'=>array('UserPricing.class_id'=>$val['Tclass']['id'],'UserPricing.type'=>'program_class','UserPricing.status'=>'1','UserPricing.custom_status'=>'0')));
		
			$per_class = $this->UserPricing->find('first',array('conditions'=>array('UserPricing.class_id'=>$val['Tclass']['id'],'UserPricing.type'=>'class','UserPricing.status'=>'1','UserPricing.custom_status'=>'0'),'order'=>'UserPricing.id DESC'));			
			$tclasses[$key]['UserPricing']['program']=$programs;
			$tclasses[$key]['UserPricing']['single']=$per_class;
			
		}	
		$this->set(compact('tclasses'));		
	}
	
	public function admin_ins_class_view($tclass_id = null, $instructor_user_id = null, $instructor_id = null){
	
		$this->set('title_for_layout',__('Class Profile'));	
		$this->layout = 'admin';
		
		$this->loadModel("Tclass");
		$this->loadModel("TclassTraining");
		$this->loadModel("TclassGoal");
		$this->loadModel("UserPricing");
		$this->loadModel("Instructor");
		$this->loadModel("UserLocation");
		$this->loadModel("Review");
	    
		$joins[]= array(
				'table'=> 'instructors',
				'type' => 'INNER',
				'alias' => 'Instructor',
				'conditions' => array('Tclass.instructor_id=Instructor.id')
			);
		$joins[] = array(
			'table'=> 'user_locations',
			'type' => 'LEFT',
			'alias' => 'UserLocation',
			'conditions' => array('Tclass.user_location_id=UserLocation.id')
		);
		$this->Tclass->bindModel(array('hasMany'=>array('Availability'=>array('className'=>'Availability','foreignKey'=>'class_id','conditions' => array('Availability.status' => '1'),'order'=>'Availability.startdate DESC'))
		));	
		$tclass = $this->Tclass->find('first',array('conditions'=>array('Tclass.id'=>$tclass_id),'fields'=>array('Tclass.*','Instructor.*','UserLocation.*'),'joins'=>$joins));
		
		$price = $this->UserPricing->getClassPriceByUser_id($tclass_id , $instructor_user_id);
		
		$this->set('class_price', $price);
		
		$review_data=$this->Review->find('first',array('conditions'=>array('Review.class_id'=>$tclass_id,'Review.to'=>$tclass['Tclass']['user_id']),'fields'=>array('count(Review.id) as count','avg(Review.star) as total')));
		$this->set('review_data', $review_data);
			
        $this->loadModel("Trainingtype");	
       	$this->set('trainingtypes', $this->Trainingtype->getAll());
		
		$date = time(); 
		$dotw = date('w', $date);
		$start_date = ($dotw == 1) ? $date : strtotime('last Monday', $date);
		$end_date = strtotime('+7 days', $start_date) - 1;
		$this->loadModel("Availability");										
		$this->Availability->unBindModel(array('hasMany' => array('AvailabilityTraining')));
		$repeat_list = $this->Availability->find('all',array('conditions'=>array('Availability.user_id'=> $instructor_user_id,'Availability.repeat'=>1,'Availability.class_id'=>$tclass_id,'Availability.status'=>'1'), 
			'group'=>array('Availability.id'),
			'fields'=>array('Availability.*'),				
		));		
		
		$list=array();	
		foreach((array)$repeat_list as $rlist)
		{
			$repeat_days_arr=explode(',',$rlist['Availability']['repeat_days']);						
			$prev_startdate=date_parse(date('m/d/Y H:i:s',strtotime($rlist['Availability']['starttime'])));				
			for($i = $start_date; $i <= $end_date; $i = strtotime('+1 day', $i))
			{
				$day=date('l',$i);
				if(!in_array($day,$repeat_days_arr))
				continue;
							
				$cancel_repeat=$rlist['Availability']['cancel_repeat'];			
				if($i>=$cancel_repeat && $cancel_repeat > 1)
				continue;
				
				$date_block=explode(',',$rlist['Availability']['date_block']);			
				if(in_array($i,$date_block))
				continue;			
								
				$newdt=date_parse(date('m/d/Y H:i:s',$i));
				
				$new_startdate=mktime($prev_startdate['hour'],$prev_startdate['minute'],0,$newdt['month'],$newdt['day'],$newdt['year']);
				$new_startdate=	$this->General->change_time_toUTC($new_startdate);
				
				$new_enddate =$new_startdate + ($rlist['Availability']['time'] * 60);
				
				$rlist['Availability']['startdate']=$new_startdate;
				$rlist['Availability']['enddate']=$new_enddate;
				
				$list[]=$rlist;				
			}
		}
							
		foreach((array)$list as $key=>$dt)
		{
			$startdate=$dt['Availability']['startdate'];
			$avi_id=$dt['Availability']['id'];		
			$avi_arr[date('d',$startdate)][$startdate]=$avi_id;					
		}
							
		$this->set(compact('tclass','avi_arr','start_date','end_date','list','instructor_user_id'));
	}

	public function admin_ins_training_list($instructor_user_id = null){
		
		$this->set('title_for_layout',__('Personal Trainings'));
		$this->layout = 'admin';
		$this->loadModel('UserTraining');
		$this->loadModel('Trainingtype');
		$this->loadModel('UserPricing');
		
		$trainings = $this->UserTraining->find('all',array('conditions'=>array('UserTraining.user_id'=>$instructor_user_id,'UserTraining.type'=>'main','UserTraining.status'=>1),		
		'joins' => array(
                array(
                    'table' => 'trainingtypes',
                    'alias' => 'Trainingtype',
                    'type' => 'INNER',
                    'conditions' => array(
                        'UserTraining.trainingtype_id = Trainingtype.id'
                    )
                )
            ),
			'fields'=>array('UserTraining.*','Trainingtype.*','(select count(*) from user_pricings where user_pricings.status=1 AND user_pricings.custom_status=0 AND UserTraining.trainingtype_id = user_pricings.trainingtype_id AND UserTraining.user_id = user_pricings.user_id) as pricing'),
			)
		);						
		$this->set(compact('instructor_user_id','trainings'));				
	}

	public function admin_ins_training_view($usertraining_id="",$instructor_user_id=null) {	
		$this->layout = 'admin';
		$this->loadModel("UserTraining");
		$this->loadModel("UserPricing");
		$this->loadModel("Instructor");
		$this->loadModel("Review");
		
		$joins[]= array(
				'table'=> 'instructors',
				'type' => 'INNER',
				'alias' => 'Instructor',
				'conditions' => array('UserTraining.instructor_id=Instructor.id')
		);
		
		$trainings = $this->UserTraining->find('first',array('conditions'=>array('UserTraining.id'=>$usertraining_id),'fields'=>array('UserTraining.*','Instructor.*'),'joins'=>$joins));
	
		$review_data = $this->Review->find('first',array('conditions'=>array('Review.trainingtype_id'=>$trainings['UserTraining']['trainingtype_id'],'Review.to'=>$trainings['UserTraining']['user_id']),'fields'=>array('count(Review.id) as count','avg(Review.star) as total')));
		
		$this->set('title_for_layout',$trainings['UserTraining']['name']);		
		$this->set(compact('trainings','review_data','instructor_user_id'));
		
    }

	public function admin_my_clients($instructor_user_id = null){
		$this->layout = 'admin';
		$this->set('title_for_layout',__('Clients'));
		$this->loadModel('Ifavorite');
		$this->loadModel('Booking');
		$this->loadModel('User');
		$this->loadModel('Client');
		$this->loadModel('UserBlock');
		$this->loadModel('Love');	
				
		$joins[]=array(
			'table'=> 'bookings',
            'type' => 'LEFT',
            'alias' => 'Booking',
            'conditions' => array('Client.id = Booking.client_id')
		);
		
		$client_type = 'training';		
		$bookingIdsArr = array();
		$clientIds  = array();
		
		if (!isset($this->request->params['named']['page'])) {
            $this->Session->delete('AdminSearch');
            $this->Session->delete('Url');
        }
	
		if(!empty($this->request->data)){
			
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Instructor']['client_type'])){
				$client_type = Sanitize::escape($this->request->data['Instructor']['client_type']);
				$this->Session->write('AdminSearch.client_type',$client_type);
			}
			if(!empty($this->request->data['Instructor']['searchword'])){
				$searchword = Sanitize::escape($this->request->data['Instructor']['searchword']);
				$this->Session->write('AdminSearch.searchword',$searchword);
			}
			if(!empty($this->request->data['Instructor']['ordertype'])){
				$ordertype = Sanitize::escape($this->request->data['Instructor']['ordertype']);
				$this->Session->write('AdminSearch.ordertype',$ordertype);
			}			
		}	
		$searchword ='';
		$filters = array();
		if(empty($this->request->data['Instructor']['client_type'])){
			$bookingIdsArr = $this->Booking->find('all',array('conditions'=>array('Booking.instructor_user_id'=>$instructor_user_id,'reject_reason IS NULL','reject_comment IS NULL','Booking.type !='=>'hold','Booking.enddate >'=>time()),'fields'=>array('DISTINCT client_id')));
			foreach((array)$bookingIdsArr as $key2=>$val2)
			{
				$clientIds[] = $val2['Booking']['client_id'];
			}
		}
		$orderfilters = array('Client.id');
		if($this->Session->check('AdminSearch')) {
             $keywords = $this->Session->read('AdminSearch');
			
			 foreach ($keywords as $key => $values) {
				if ($key == 'searchword') {
						$filters[] = array('OR' => array('Client.first LIKE' => "%" . $values . "%", 'Client.last LIKE' => "%" . $values . "%"));
				}
				if ($key == 'client_type') {						
						if($values == 'all_client'){			
							$bookingIdsArr = $this->Booking->find('all',array('conditions'=>array('Booking.instructor_user_id'=>$instructor_user_id,'reject_reason IS NULL','reject_comment IS NULL','Booking.type !='=>'hold'),'fields'=>array('DISTINCT client_id')));
							foreach((array)$bookingIdsArr as $key2=>$val2)
							{
								$clientIds[] = $val2['Booking']['client_id'];
							}
							$favouriteClientIdsArr = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$instructor_user_id),'fields'=>array('DISTINCT client_id')));
							foreach((array)$favouriteClientIdsArr as $keyfav=>$valfav)
							{
								$clientIds[] = $valfav['Love']['client_id'];
							}			
						}		
						else if($values == 'training'){
							$bookingIdsArr = $this->Booking->find('all',array('conditions'=>array('Booking.instructor_user_id'=>$instructor_user_id,'reject_reason IS NULL','reject_comment IS NULL','Booking.type !='=>'hold','Booking.enddate >'=>time()),'fields'=>array('DISTINCT client_id')));
							foreach((array)$bookingIdsArr as $key2=>$val2)
							{
								$clientIds[] = $val2['Booking']['client_id'];
							}
						}		
						else if($values == 'no_longer_training'){
							$bookingIdsArr = $this->Booking->find('all',array('conditions'=>array('Booking.instructor_user_id'=>$instructor_user_id,'Booking.enddate <'=>time(),'Booking.type !='=>'hold','Booking.user_id NOT IN (SELECT DISTINCT user_id FROM bookings AS Booking WHERE Booking.instructor_user_id ='.$this->Auth->user('id').' AND Booking.enddate > '.time().' AND Booking.type != "hold")'),'fields'=>array('DISTINCT client_id')));
							
							foreach((array)$bookingIdsArr as $key2=>$val2)
							{
								$clientIds[] = $val2['Booking']['client_id'];
							}							
						
						}
						else if($values == 'never_trained'){
							$bookingIdsArr = $this->Booking->find('all',array('conditions'=>array('Booking.instructor_user_id'=>$instructor_user_id,'Booking.type !='=>'hold'),'fields'=>array('DISTINCT client_id')));
							foreach($bookingIdsArr as $key2=>$val2){
								$booking_clientIds[] = $val2['Booking']['client_id'];
							}
												
							$favouriteClientIdsArr = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$instructor_user_id),'fields'=>array('DISTINCT client_id')));
							foreach((array)$favouriteClientIdsArr as $keyfav=>$valfav)
							{
								if(!in_array($valfav['Love']['client_id'],(array)$booking_clientIds))
								$clientIds[] = $valfav['Love']['client_id'];
							}											
						}	
					
				}
				if ($key == 'ordertype') {
					if($values == 'atoz'){
						$orderfilters = array('Client.first ASC');
					}	
					if($values == 'recent'){
						$orderfilters = array('Booking.enddate DESC');
					}
				}		
			 
			 }
		}	
		
		$instStr = implode("','",(array)$clientIds);
		$filters[] = array("Client.id IN ('".$instStr."')");
		
		$blockUserArr = $this->UserBlock->find('all',array('fields'=>array('block_user_id')));
		$blockUsers = array();
		foreach((array)$blockUserArr as $key=>$val){
			$blockUsers[]= $val['UserBlock']['block_user_id'];
		}
		$blockUserIds =  implode("','",(array)array_unique($blockUsers));
		
		if(!empty($blockUserIds)){
			$filters[] = array("Client.user_id NOT IN('".$blockUserIds."')"); 
		}	
		
		$clientArr = $this->Client->find('all',array('conditions'=>$filters,'joins'=>$joins,'order'=>$orderfilters,'group'=>array('Client.id')));
		
		$this->set(compact('client_type','clientArr','instructor_user_id'));
		if ($this->request->is('ajax')) {
			$this->layout = false;
			$this->render('/Elements/Admin/Instructor/myclient');
			
        }
	}	
	
	public function admin_message($instructor_user_id=null) {
		
		$this->layout = 'admin';
		$this->set('title_for_layout',__('Messages'));
       	$this->loadModel("Message");
		$this->loadModel("Client");
		
		$messages = $this->Message->query("SELECT * FROM messages Message WHERE not exists( SELECT 1 FROM messages m2 WHERE m2.created > Message.created AND ( (Message.from = m2.from AND Message.to = m2.to) OR (Message.from = m2.to AND Message.to = m2.from))) and(Message.to = ".$instructor_user_id." or Message.from = ".$instructor_user_id.") order by Message.created desc");
		
		$clients = $this->Client->find('list',array('fields'=>array('user_id','name')));		
        $this->set(compact('messages', 'clients','instructor_user_id'));       	
    }
	
    public function admin_messages_chat($rec_id=null,$instructor_user_id=null) {
		$this->set('title_for_layout',__('Messages'));
		$this->layout = 'admin';

		$this->loadModel("Message");
		$this->loadModel("Client");

		$messages = $this->Message->query("SELECT * FROM messages Message WHERE (Message.to = ".$instructor_user_id." AND Message.from = ".$rec_id.") OR (Message.to = ".$rec_id." AND Message.from = ".$instructor_user_id.") order by Message.created ASC");	

		$clients = $this->Client->find('list',array('conditions'=>array('Client.user_id'=>$rec_id),'fields'=>array('user_id','name')));
		$this->set('title_for_layout',$clients[$rec_id]);
		$this->set(compact('messages','clients','instructor_user_id'));
    }
	
	public function setup_wepay_account($user_id=null){		
		$this->loadModel("Instructor");
		$this->loadModel("User");
		$this->loadModel("Alert");
		$instructor=$this->Instructor->findByUser_id($user_id);
		$usdata=$this->User->findById($user_id);
		
		App::import('Vendor', 'Wepay', array('file' => 'Wepay/wepay.php'));
				
		Wepay::useStaging(WEPAY_CLIENT_ID, WEPAY_CLIENT_SECRET);
			
		$wepay = new WePay(WEPAY_ACCESS_TOKEN);
		$response = $wepay->request('/user/register', array(	
			"client_id"=>WEPAY_CLIENT_ID,
			"client_secret"=>WEPAY_CLIENT_SECRET,
			"email"=>$usdata['User']['email'],
			"scope"=>"manage_accounts,view_balance,collect_payments,view_user",
			"first_name"=>$instructor['Instructor']['first'],
			"last_name"=>$instructor['Instructor']['last'],
			"original_ip"=>$_SERVER['REMOTE_ADDR'],
			"original_device"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; 
						 en-US) AppleWebKit/534.13 (KHTML, like Gecko) 
						 Chrome/9.0.597.102 Safari/534.13",
			"tos_acceptance_time"=>1209600,
			"callback_uri"=>SITE_URL."instructors/wepay_confirm"
		));		
			
		$access_token=$response->access_token;
		$wepay_user_id=$response->user_id;
			
		$wepay = new WePay($access_token);
		
		$rbit_id=time();	
		$response_account = $wepay->request('/account/create', array(	
			"name"=>$instructor['Instructor']['first'].' '.$instructor['Instructor']['last'],
			"description"=>"This is just an example WePay account.",
			//"reference_id"=>$user_id,
			"image_uri"=>"https://stage.wepay.com/img/logo.png",
			"country"=>"US",
			"currencies"=>array('USD'),
			"rbits"=>array(
				array(
					"receive_time"  => $rbit_id,       
					"type"         	 => "person",
					"source"=>"user",
					"properties" 	 => array(
						"name" => $instructor['Instructor']['first']." ".$instructor['Instructor']['last'],                                
						"role" => "other_third_party",                                
					),
				),
				array(
					"receive_time"  => $rbit_id,       
					"type"         	 => "email",
					"source"=>"user",
					"properties" 	 => array(
						"email" => $usdata['User']['email'],                                
					)
				),
				/*array(
					"receive_time"  => $rbit_id,       
					"type"         	 => "business_name",
					"source"=>"user",
					"properties" 	 => array(
						"business_name" => $instructor['Instructor']['bname'],                                
						"name_type" => "dba",                                
					)
				), */
				array(
					"receive_time"  => $rbit_id,       
					"type"         	 => "address",
					"source"=>"user",
					"properties" 	 => array(
						//"city" => "my data",                                
						"zip" => $instructor['Instructor']['zip'],
						//"state" => "dba",
						"country" => "US",
						
					)
				),
				array(
					"receive_time"  => $rbit_id,       
					"type"         	 => "phone",
					"source"=>"user",
					"properties" 	 => array(
						"phone"=>$instructor['Instructor']['phone'],
						"phone_type"=>"work"						
					)
				)
			)
		));		
		
		try{
		$response_send = $wepay->request('user/send_confirmation/', array(
				"email_message"=>"Welcome to FitMeNow! We're almost done setting up your account. All that's left is to link your bank account so you can get paid!<br/><br/>
You'll get paid through wepay.com, our payment processor. When your client's credit card is charged, the payment will go to your wepay account. We recommend you configure wepay to automatically transfer payments to your bank account.<br/><br/>
Please click the CONFIRM link below and follow the instructions to confirm your wepay account, set your password, and set up your payment details.<br/><br/>
Once you've completed this step, you're ready to accept payments!<br/><br/>
Feel free to contact us at support@fitmenow.com if you have any questions.<br/><br/><br/>
Customer Support Team<br/><br/>
www.FitMeNow.com<br/>"
			));
		}
		catch (WePayException $e) {
			$error = $e->getMessage();			
		}		
		
		$data=array();
		$data['Alert']['to'] = $user_id;
		$data['Alert']['type'] = 'wepay_confirm';
		$data['Alert']['status'] = 1;		
		$data['Alert']['title'] = 'Important: Verify your WePay account';		
		$this->Alert->save($data['Alert']);
		
		$db_user_id_key='wepay_user_id';
		$db_account_id_key='wepay_account_id';
		$db_access_token_key='wepay_access_token';
		
		if (WEPAY_MODE == 'stage') 
		{
			$db_user_id_key='wepay_user_id_stage';
			$db_account_id_key='wepay_account_id_stage';
			$db_access_token_key='wepay_access_token_stage';
		}

		$this->Instructor->updateAll(array("Instructor.".$db_user_id_key=>"'".$wepay_user_id."'","Instructor.".$db_account_id_key=>"'".$response_account->account_id."'","Instructor.".$db_access_token_key=>"'".$access_token."'"),array("Instructor.user_id"=>$user_id));
		
		return true;
	}
	
	public function wepay_confirm(){
		$this->loadModel("Instructor");
		$this->loadModel("Alert");
		
		$user_id=$_POST['user_id'];
		//$user_id='36509446';
		$instructor=$this->Instructor->findByWepay_user_id($user_id);
				
		App::import('Vendor', 'Wepay', array('file' => 'Wepay/wepay.php'));
				
		Wepay::useStaging(WEPAY_CLIENT_ID, WEPAY_CLIENT_SECRET);
		
		if($instructor['Instructor']['wepay_access_token']!="")
		{	
			$wepay = new WePay($instructor['Instructor']['wepay_access_token']);
			$response = $wepay->request('/user');
			if($response->state=='registered')
			{
				$this->Alert->updateAll(array("Alert.status"=>0),array("Alert.to"=>$instructor['Instructor']['user_id'],"Alert.type"=>"wepay_confirm"));
			}
		}
		die();	
		
	}
	
	public function wepay_email_resend(){
	
		$ins_data=$this->Instructor->findByUser_id($this->Auth->user('id'));
		App::import('Vendor', 'Wepay', array('file' => 'Wepay/wepay.php'));
			
		Wepay::useStaging(WEPAY_CLIENT_ID, WEPAY_CLIENT_SECRET);					
		$db_access_token_key='wepay_access_token';	
		if (WEPAY_MODE == 'stage') 
		{			
		$db_access_token_key='wepay_access_token_stage';
		}
		$wepay = new WePay($ins_data['Instructor'][$db_access_token_key]);	
		try{
			$response_send = $wepay->request('user/send_confirmation/', array(
			"email_message"=>"Welcome to FitMeNow! We're almost done setting up your account. All that's left is to link your bank account so you can get paid!<br/><br/>
You'll get paid through wepay.com, our payment processor. When your client's credit card is charged, the payment will go to your wepay account. We recommend you configure wepay to automatically transfer payments to your bank account.<br/><br/>
Please click the CONFIRM link below and follow the instructions to confirm your wepay account, set your password, and set up your payment details.<br/><br/>
Once you've completed this step, you're ready to accept payments!<br/><br/>
Feel free to contact us at support@fitmenow.com if you have any questions.<br/><br/><br/>
Customer Support Team<br/><br/>
www.FitMeNow.com<br/>"
			));
		}
		catch (WePayException $e) {
			$error = $e->getMessage();			
		}
		
		$this->Session->setFlash(__('Wepay welcome email has been sent to you.'));
		$this->redirect(array('controller'=>'merlins','action' => 'basic_profile'));
	}
	
	public function signup() {	
		$this->set('title_for_layout',__('Instructor Registration'));
		$this->layout = 'signup';	
		$this->loadModel('BetaUser');
		$this->loadModel('Alert');	
		
		if(isset($_GET['bid']))
		{
			$bid = base64_decode($_GET['bid']);			
			$user = $this->BetaUser->find('first', array(
													'conditions' => array(
													   'BetaUser.id' => $bid,
													   'BetaUser.registration' =>0,
													)
											));			
			if(empty($user)) 
			{
				$this->Session->setFlash(__('This registration link has already been used. If you would like to request Beta access, click Join Our Beta below.'));
				$this->redirect(array('controller' => 'users', 'action' => 'login'));
			}			
			$this->Session->write('bid',$bid);
		}
		if ($this->request->is('post')) 
		{	
			$this->request->data['User']['timezone']=$this->Session->read('timezone');			
			$this->User->set($this->request->data);
			$this->User->setValidation('register');			
			$this->Instructor->set($this->request->data);
			$this->Instructor->setValidation('register');			
			$this->request->data['User']['role_id'] = 3;
			$this->request->data['User']['status']  = 1;
			$this->request->data['User']['password'] = Security::hash($this->request->data['User']['org_password'], null, true);					
			if($this->Instructor->validates() && $this->User->validates())
			{			
				$activationKey = substr(md5(uniqid()), 0, 20);
				$this->request->data['User']['activation_key'] = $activationKey;
				$this->User->save($this->request->data);
				$user_id = $this->User->getLastInsertId();							
				$this->Session->write('timezone',$this->request->data['User']['timezone']);				
				$this->Session->write('new_instructor','1');
				$this->request->data['Instructor']['user_id']= $user_id;
				$this->Instructor->save($this->request->data);
				$lastins_id = $this->Instructor->getLastInsertId();				
				$this->Alert->query("update alerts set alerts.to=".$user_id." where invite_id IN(select id from invitations where email='".$this->request->data['User']['email']."' and status=0)");
				
				$this->setup_wepay_account($user_id);				
				if($this->Session->check('bid'))
				{
					$this->request->data['BetaUser']['registration'] = $user_id;
					$this->request->data['BetaUser']['id'] = $this->Session->read('bid');
					$this->BetaUser->save($this->request->data['BetaUser']);
					$this->Session->delete('bid');
					
					$this->User->updateAll(array('User.status' => 1, 'User.activation_key'=>null), array('User.id' => $user_id));					
					$user = $this->User->find('first',array('conditions'=>array('User.id'=> $user_id)));
					$instructor = $this->Instructor->findByUser_id($user_id);
					$this->Instructor->updateAll(array('Instructor.welcome_login'=>'1'),array('Instructor.id'=>$instructor['Instructor']['id']));
					
					$this->Session->write('Auth.User', $user['User']);
					$this->Auth->_loggedIn = true;					
					$this->Session->write('Auth.User.instructor_id',$instructor['Instructor']['id']);
					$this->Session->write('Auth.User.avatar',$instructor['Instructor']['avatar']);
					$this->Session->write('Auth.User.first',$instructor['Instructor']['first']);
					$this->Session->write('Auth.User.last',$instructor['Instructor']['last']);
					$this->Session->write('Auth.User.last_login',date('Y-m-d H:i:s'));
					
					$this->Session->setFlash(__('Your account has been created successfully.'));
					$this->redirect(array('controller'=>'merlins','action' => 'welcome_merlin'));
				}                                
				$url = SITE_URL;
				$Email = new CakeEmail(MAIL_SENDER);
				$Email->template('welcome_instructor')
					->emailFormat('both')
					->to($this->request->data['User']['email'])
					->from( FROM_MAIL , SITE_NAME)
					->subject('Welcome to FitMeNow!')
					->viewVars(array('url'=>$url,'first' =>$this->request->data['Instructor']['first']))    
					->send();			
				$this->Session->setFlash(__('Signup completed.'));				
				$this->redirect(array('controller'=>'users','action' => 'login'));									
 			} 
		}
		
	} 
	
	public function signup_thanks(){	  
		$this->layout = 'login';
		$this->set('title_for_layout',__('WELCOME'));					
	}
	
	public function getclientid_byemail(){
		$this->layout = false;
		$this->loadModel('User');	
		$userdataArr = $this->User->find('first',array('conditions'=>array('User.email'=>$this->request->data['email']),'fields'=>array('id')));
			
		if(!empty($userdataArr)){
			echo $client_id = $userdataArr['User']['id'];
			die;
		}else{
			echo $client_id = '';
			die;
		} 
		
	}	
	
	public function getMsgCount(){	
		$this->loadModel('Message');			
		$this->loadModel('Alert');			
		$res['msg'] = $this->Message->find('count',array('conditions'=>array('Message.to'=>$this->Auth->user('id'),'Message.read'=>0))) ;
		$user_time=strtotime($this->General->change_time_toUsertimezone(time()));
		$alerts = $this->Alert->getAll($this->Auth->user('id'),$this->Auth->user('role_id'),$user_time);
		$res['alert'] = count($alerts);
		$res['alert_cnt']=	$res['alert'] +  $res['msg'];
		echo json_encode($res);		
		die();
	}
	
	public function more(){
		$this->set('title_for_layout',__('More'));	    
	}        

	public function legal(){
		$this->set('title_for_layout',__('Legal'));	    
	}        

	public function help(){
		$this->set('title_for_layout',__('Help Center'));	    
	}        
        
	public function term_condition() {
		$this->set('title_for_layout',__('Terms & Conditions'));	    
		$this->loadModel('Term');
		$this->request->data = $this->Term->find('first',array('conditions'=>array('Term.status'=>'1','Term.type'=>'instructor')));		
		$term_data=nl2br($this->request->data['Term']['content']);
		$this->set('term_data',$term_data);	
	}
	
	public function privacy_policy() {
		$this->set('title_for_layout',__('Privacy Policy'));	    
		$this->loadModel('Policy');
		$this->request->data = $this->Policy->find('first',array('conditions'=>array('Policy.status'=>'1','Policy.type'=>'instructor')));		
		$policy_data=nl2br($this->request->data['Policy']['content']);
		$this->set('policy_data',$policy_data);			
	}
		
	public function faq() {
		$this->set('title_for_layout',__('FAQ'));	    
		
		$this->loadModel('Faq');
		$data = $this->Faq->find('all',array('conditions'=>array('Faq.status'=>'1','Faq.type'=>'instructor')));
		$this->set('data',$data);		
	}	
	
	public function settings($page=null) {		
		
		$this->set('title_for_layout',__('Settings'));	
	    $this->loadModel('Instructor');            
       			
		if ($this->request->is('post') || $this->request->is('put')) 
		{
		
			$usdata['id']=$this->Auth->user('id');				
			$usdata['timezone']=$this->request->data['User']['timezone'];
			$this->User->save($usdata);

			$this->Session->write('timezone',$this->request->data['User']['timezone']);
			
			$this->redirect(array('action' => 'more'));				
		}
		
		$this->request->data = $this->Instructor->findByUser_id($this->Auth->user('id'));	
		$this->request->data['User'] = $this->User->findById($this->Auth->user('id'));	
		$this->request->data['User']=$this->request->data['User']['User'];
		unset($this->request->data['User']['User']);						
		$instructor=$this->Instructor->findByUser_id($this->Auth->user('id'));
        $this->set(compact('instructor','page'));				
		        
    }	
	
	public function fees() {
		$this->set('title_for_layout',__('Fees'));
		$this->loadModel('Fees');
		$this->request->data = $this->Fees->find('first',array('conditions'=>array('Fees.status'=>'1')));
		$fees_data = nl2br(@$this->request->data['Fees']['content']);
		$this->set('fees_data',$fees_data);			
	}

	public function calendar($default_date=null) {	
		$this->loadModel('Alert');
		$this->loadModel('Booking');
		$this->loadModel('BookingOther');
		$this->loadModel('Order');
		$this->loadModel('Client');
		$this->Session->delete('client_type_dropdown');             
		$this->Session->delete('client_id_arr'); 		             
		$this->Session->delete('minimerlin');
		$this->Session->delete('back_action');
		$this->Session->delete('service_name');
		$this->Session->delete('training_ids');
		$this->set('minimerlin',false);
		
		$this->set('title_for_layout',__('My Schedule')); 
        
		$user_time = date('m/d/Y',strtotime($this->General->change_time_toUsertimezone(time())));
		
		$start_date=strtotime($user_time); // so when agax call we can show the current date if we set the previous date
		$this->Session->write('current_date',$start_date);
			
		$date = strtotime($user_time); 
		$dotw = date('w', $date);
		$week_start = ($dotw == 0 /* Monday */) ? $date : strtotime('last Sunday', $date);
		$week_end = strtotime('+7 days', $week_start) - 1;
				
		if($default_date !=null && $default_date!='show_date' && $default_date!='reschedule')
		{	
			$default_date =	strtotime($this->General->change_time_toUsertimezone($default_date));	
			$start_date = strtotime(date('m/d/Y',$default_date));
		}	
				
		$this->set('current_date', $start_date);
		$this->set('week_start', $week_start);
		$this->set('week_end', $week_end);		
		$this->set('user_time', $user_time);
		
		$this->set('default_date', $default_date);
		$mobile_req=$this->ismobile();
		$this->set('mobile_req', $mobile_req);
		
		if($default_date =='show_date')
		{
			$this->set('default_date',null);
		}
		if($default_date =='reschedule')
		{
			$this->set('default_date',null);
		}
		if($default_date !='show_date' && $default_date!='reschedule')
		{			
			$this->Session->delete('duration');
			$this->Session->delete('weekname');
			$this->Session->delete('client_id_arr');
			$this->Session->delete('client_userid_arr');
			$this->Session->delete('date_time');			
			$this->Session->delete('clientsData');
			$this->Session->delete('trainingtype_id');
			$this->Session->delete('trainingtype_name');
			$this->Session->delete('location_id');
			$this->Session->delete('location_name');			
			$this->Session->delete('client_type_dropdown'); 
			$this->Session->delete('type');
			$this->Session->delete('booking_id');
			$this->Session->delete('action');
		}
		$program_idsarr = array();
		$user_time = strtotime($this->General->change_time_toUsertimezone(time()));
		$alerts = $this->Alert->getOnlyfourTypeAlert($this->Auth->user('id'),$this->Auth->user('role_id'),$user_time);
		
		$unpaid_session = $this->General->getAllUnpaidSessionAlerts($this->Auth->user('id'));
		//pr($unpaid_session);
		$this->set(compact('alerts','unpaid_arr','unpaid_session'));		
    }
	
	public function unpaid_resolution($client_id=null){
		$this->set('title_for_layout',__('Unpaid Sessions')); 
		$this->loadModel('Booking');
		$this->loadModel('BookingOther');
		$this->loadModel('Client');
		
		/* $this->Booking->bindModel(array('belongsTo'=>array(
										'Client'=>array('className'=>'Client','foreignKey'=>'client_id'),
										'Order'=>array('className'=>'Order','foreignKey'=>false,'conditions'=>array('Order.program_id = Booking.program_id'))
									)));
		$this->Booking->bindModel(array('hasMany'=>array('BookingOther'=>array('className'=>'BookingOther','foreignKey'=>'booking_id'))));
		$unpaidBookData = $this->Booking->find('first',array('conditions'=>array('Booking.id'=>$booking_id)));	 */	
			
		$unpaidBookData = $this->General->getAllUnpaidSessionAlertDetail($this->Auth->user('id'),$client_id);
		
		$this->set(compact('unpaidBookData','unpaid_arr','client_id','booking_id'));
	}
	
	public function agenda_new_ajax(){
	
		$this->layout = false;		
		$this->Session->delete('book_startdate');
		
		$prev_date = $this->Session->read('current_date');				
		$start_date = $_POST['day'];
		//$start_date='1467849600';	
				
		$user_start_date="";		
		$user_start_date=$this->General->get_user_day_start_date($start_date);			
						
		$end_date = $start_date + (24*60*60) -1;
		$user_end_date = $user_start_date + (24*60*60) -1;
		
		$this->set('current_date', $start_date);
		$this->Session->write('current_date',$start_date);
				
        $this->loadModel("Booking");
        $this->loadModel("Instructor");
        $this->loadModel("Availability");
        $this->loadModel("AvailabilityCancel");
        $this->loadModel("Trainingtype");
        $this->loadModel("Tclass");
        $this->loadModel("UserTraining");
		
		$bookings = array(); 
				
        $joins[]=array(
			'table'=> 'clients',
            'type' => 'LEFT',
            'alias' => 'Client',
            'conditions' => array('Booking.client_id = Client.id')
			);		
		$joins[]=array(
			'table'=> 'inventories',
            'type' => 'LEFT',
            'alias' => 'Inventory',
            'conditions' => array('Booking.inventory_id = Inventory.id')
			);
				
		$fields=array('Booking.id','Booking.user_id','Booking.client_id','Booking.instructor_user_id','Booking.instructor_id','Booking.inventory_id','Booking.type','Booking.program_id','Booking.startdate','Booking.enddate','Booking.repeat_days','Booking.repeat_lastdate','Booking.memo','Booking.user_location_id','count(Booking.startdate) as group_total','(SELECT GROUP_CONCAT(date SEPARATOR \',\') from booking_cancels where booking_id=Booking.id) as booking_cancel','Client.first','Client.last','Inventory.id','Inventory.name');			
		$bookings_repeat_data = $this->Booking->find('all', 
					array('conditions'=>array(
							'Booking.instructor_user_id'=>$this->Auth->user('id'),						
							'Booking.type !='=>'hold',
							'Booking.status ='=>1,
							),
							'fields'=>$fields,	
							'joins'=>$joins,
							'group'=>'Booking.startdate',
							'order'=>'Booking.startdate',				
						)
					);
		/*pr($bookings_repeat_data);die();  */
		
		/* foreach((array)$bookings_repeat_data as $key=>$bk)
		{
			$booking_cancel_arr = array();
			foreach((array)$bk['BookingCancel'] as $key2=>$bk2)
			{
				$booking_cancel_arr[] = $bk2['date'];
			}
			$bookings_repeat_data[$key]['BookingCancelArr']=$booking_cancel_arr;
		} */
		
		foreach((array)$bookings_repeat_data as $bk)
		{			
			$dt2 = date('m/d/Y H:i:s',strtotime($this->General->change_time_toUsertimezone($bk['Booking']['startdate'])));			
			$dt2 = date_parse($dt2);			
			$repeat_days_arr=explode(',',$bk['Booking']['repeat_days']);			
			$cancel_arr=explode(',',$bk[0]['booking_cancel']);
			
			for($i = $start_date; $i <= $end_date; $i = strtotime('+1 day', $i))
			{
				$day=date('l', $i);
				$user_day_start_UTC=$this->General->change_time_toUTC($i);				
				$user_day_end_UTC=$user_day_start_UTC + (24*60*60);	
				
				$first_day=false;
				if($bk['Booking']['startdate']>=$user_day_start_UTC && $bk['Booking']['startdate']<=$user_day_end_UTC)	
				{
					$first_day=true;
				}
				$booking_startdate_user_timezone =  strtotime($this->General->change_time_toUsertimezone($bk['Booking']['startdate']));
				if((in_array($day,$repeat_days_arr) && $i>=$booking_startdate_user_timezone) || $first_day==true)
				{	
					$dt=date('m/d/Y H:i:s',$i);
					$dt=date_parse($dt);
					
					$sdate=mktime($dt2['hour'],$dt2['minute'],0,$dt['month'],$dt['day'],$dt['year']);
					$sdate_UTC=$this->General->change_time_toUTC($sdate);
					if($bk['Booking']['repeat_lastdate']>0 && $sdate_UTC>=$bk['Booking']['repeat_lastdate'])
					{
					continue;
					}					
					if(in_array($sdate_UTC,$cancel_arr))
					{
					continue;
					}
					
					$edate =$sdate + ($bk['Booking']['enddate'] - $bk['Booking']['startdate']);						
					$bk['Booking']['startdate']=$sdate;
					$bk['Booking']['enddate']=$edate;
					$bookings[]=$bk;												
				}
				
			}
		}		
		/* pr($bookings);die();		 */
		$new_array = $bookings;
		$dt_arr=array();
		foreach($new_array as $k=>$r)
		{
			$dt_arr[$k]=$r['Booking']['startdate'];
		}
		
		asort($dt_arr);	
		//pr($dt_arr);
		$results = array();	
		foreach($dt_arr as $k=>$v)
		{			
			$results[$new_array[$k]['Booking']['startdate']][]=$new_array[$k];
		}
		//pr($results);die();	
		//$_POST['selDate']='Jun 1 2016 01:30 PM';		
		if(isset($_POST['selDate']))
		{
			$seldate=strtotime($_POST['selDate']);
			$new_event['type']='new_event';
			$new_event['startdate']=$seldate;
			$new_event['enddate']=$seldate + ($_POST['duration']*60);
			$new_event['inventory_id']=null;
			$results[$seldate][]['Booking']=$new_event;	
		}		
		//pr($results);die();		
		
		$this->set('results', $results);
		
		$instructor=$this->Instructor->findByUserId($this->Auth->user('id'));		
		$this->set('instructor', $instructor);
		
		$this->layout = false;
		$this->autoRender = false;
				
		$this->render('/Instructors/agenda_new_ajax');
	}	
			
	public function stats_add($book_id=null,$book_startdate=null){
		$this->set('title_for_layout', __('Stats', true));
		$this->loadModel("ClientData");
		if($this->request->is('post') || $this->request->is('put')) 
		{			
			if(!empty($this->request->data))
			{
				$stats_data = json_encode($this->request->data['Client']);
				$this->request->data['ClientData']['stats'] = $stats_data;
				$this->request->data['ClientData']['booking_id'] = $book_id;			
				$this->request->data['ClientData']['type'] = 'stats';			
				$this->request->data['ClientData']['to'] = $this->request->data['Client']['to'];			
				$this->request->data['ClientData']['from'] = $this->request->data['Client']['from'];			
				// pr($this->request->data);die;
				$this->ClientData->saveAll($this->request->data['ClientData']);
				$this->redirect(array('controller'=>'trainings','action' =>'session_profile',$book_id,$book_startdate));
			}	
		}
	}
	
	function convertImage($originalImage, $outputImage, $quality){
		// jpg, png, gif or bmp?
		$exploded = explode('.',$originalImage);
		$ext = $exploded[count($exploded) - 1]; 

		if (preg_match('/jpg|jpeg/i',$ext))
			$imageTmp=imagecreatefromjpg($originalImage);
		else if (preg_match('/png/i',$ext))
			$imageTmp=imagecreatefrompng($originalImage);
		else if (preg_match('/gif/i',$ext))
			$imageTmp=imagecreatefromgif($originalImage);
		else if (preg_match('/bmp/i',$ext))
			$imageTmp=imagecreatefrombmp($originalImage);
		else
			return 0;

		// quality is a value from 0 (worst) to 100 (best)
		imagejpeg($imageTmp, $outputImage, $quality);
		imagedestroy($imageTmp);

		return 1;
	}
	
	public function search(){		
		$this->loadModel("Booking");
        $this->loadModel("Availability");
        $this->loadModel("AvailabilityCancel");
        $this->loadModel("Trainingtype");
        $this->loadModel("Tclass");
        $this->loadModel("UserTraining"); 
        $this->loadModel("Client");
        $this->loadModel("ClientData");
        $this->loadModel("Love");
        $this->loadModel("Invitation");
        $this->loadModel("Order");	
        $this->loadModel("Invoice");	
        $this->loadModel("InvoiceItem");	
		
		$this->set('title_for_layout',__('Calender Search'));
		
		$contacts = array();
		$invites = array();
		$bookings = array();
		$filter = array();
		$filter2 = '';		
		$filter3 = '';		
		
		if(($this->request->is('post') || $this->request->is('put')) && (!empty($_POST['search_keyword'])))
		{	
			$filter = array("OR" => array ("Client.first LIKE" =>'%'.$_POST['search_keyword'].'%',"Client.last LIKE" => '%'.$_POST['search_keyword'].'%'));
			$filter2 = array("Trainingtype.name LIKE "=>'%'.$_POST['search_keyword'].'%');
			$filter3 = array("Tclass.name LIKE "=>'%'.$_POST['search_keyword'].'%');
		}		
		
		$joins2[]=array(
			'table'=> 'clients',
            'type' => 'left',
            'alias' => 'Client',
            'conditions' => array('Client.id = Love.client_id')
		);		
		
		$contacts = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$this->Auth->user('id'),'Love.user_id IS NOT NULL',$filter),
									'fields'=>array('Love.*','Client.*','(select sum(days) from user_pricings where user_id='.$this->Auth->user('id').' and client_user_id=Love.user_id) as total_pack','(select sum(used) from user_pricings where user_id='.$this->Auth->user('id').' and client_user_id=Love.user_id) as total_pack_used'),
									'group'=>'Love.user_id',
									'order'=>array('Love.created DESC'),
									'joins'=>$joins2,
									//'limit'=>10	
									)
								); 
		
				
		$contacts2 = array();		
		if(isset($_POST['search_keyword']) && $_POST['search_keyword']!="")
		{
			$joins3[]=array(
				'table'=> 'clients',
				'type' => 'left',
				'alias' => 'Client',
				'conditions' => array('Client.user_id = ClientData.to')
			);	
			$contacts2 = $this->ClientData->find('all',array('conditions'=>array('ClientData.from'=>$this->Auth->user('id'),'ClientData.title like "%'.$_POST['search_keyword'].'%"'),
										'fields'=>array('Client.*','ClientData.*','(select sum(days) from user_pricings where user_id='.$this->Auth->user('id').' and client_user_id=ClientData.to) as total_pack','(select sum(used) from user_pricings where user_id='.$this->Auth->user('id').' and client_user_id=ClientData.to) as total_pack_used'),
										'group'=>'ClientData.to',
										'order'=>array('ClientData.created DESC'),
										'joins'=>$joins3
										)
									); 
			$contacts = array_merge($contacts,$contacts2);			
			$this->set('search_keyword',$_POST['search_keyword']);	
		}		
		 
		
		$this->Invoice->bindModel(array('belongsTo'=>array('Client'=>array('className'=>'Client','foreignKey'=>'client_id'))));		
		$invoices = $this->Invoice->find('all',array('conditions'=>array('Invoice.instructor_user_id'=>$this->Auth->user('id'),'Invoice.status'=>1),'fields'=>array('Invoice.*','Client.id','Client.avatar','Client.first','Client.last','(select count(*) from invoice_items where invoice_number=Invoice.invoice_number) as items')));
		//pr($invoices);
		$joinsb[]=array(
			'table'=> 'clients',
            'type' => 'LEFT',
            'alias' => 'Client',
            'conditions' => array('Booking.client_id = Client.id')
			);		
		$joinsb[]=array(
			'table'=> 'inventories',
            'type' => 'LEFT',
            'alias' => 'Inventory',
            'conditions' => array('Booking.inventory_id = Inventory.id')
			);
		$joinsb[]=array(
			'table'=> 'user_locations',
            'type' => 'LEFT',
            'alias' => 'UserLocation',
            'conditions' => array('Booking.user_location_id= UserLocation.id')
			);		
			
       $bookings_repeat_data = $this->Booking->find('all', 
                array('conditions'=>array(
						'Booking.instructor_user_id'=>$this->Auth->user('id'),						
						'Booking.type !='=>'hold',
						'Booking.status ='=>1,												
						),
						'fields'=>array('Booking.*','count(Booking.startdate) as group_total','Client.user_id','Client.id','Client.first','Client.last','Client.avatar','Inventory.*','UserLocation.*'),	
						'joins'=>$joinsb,
						'group'=>'Booking.startdate',
						'order'=>'Booking.startdate',				
					 )
            );
		$user_time = date('m/d/Y',strtotime($this->General->change_time_toUsertimezone(time())));
		$start_date=strtotime($user_time);						
		$end_date = $start_date + (30*24*60*60) -1;	
			
		foreach((array)$bookings_repeat_data as $bk)
		{			
			$dt2 = date('m/d/Y H:i:s',strtotime($this->General->change_time_toUsertimezone($bk['Booking']['startdate'])));			
			$dt2 = date_parse($dt2);			
			$repeat_days_arr=explode(',',$bk['Booking']['repeat_days']);			
			
			for($i = $start_date; $i <= $end_date; $i = strtotime('+1 day', $i))
			{
				$day=date('l', $i);
				$user_day_start_UTC=$this->General->change_time_toUTC($i);				
				$user_day_end_UTC=$user_day_start_UTC + (24*60*60);	
				
				$first_day=false;
				if($bk['Booking']['startdate']>=$user_day_start_UTC && $bk['Booking']['startdate']<=$user_day_end_UTC)	
				{
					$first_day=true;
				}
				
				if((in_array($day,$repeat_days_arr) && $i>=$bk['Booking']['startdate']) || $first_day==true)
				{	
					$dt=date('m/d/Y H:i:s',$i);
					$dt=date_parse($dt);
					
					$sdate=mktime($dt2['hour'],$dt2['minute'],0,$dt['month'],$dt['day'],$dt['year']);		
					$edate =$sdate + ($bk['Booking']['enddate'] - $bk['Booking']['startdate']);						
					$bk['Booking']['startdate']=$sdate;
					$bk['Booking']['enddate']=$edate;
					$bookings[]=$bk;												
				}
				
			}
		}		
		$new_array = $bookings;
		$dt_arr=array();
		foreach($new_array as $k=>$r)
		{
			$dt_arr[$k]=$r['Booking']['startdate'];
		}		
		asort($dt_arr);					
		$bookings = array();	
		$i=0;
		foreach($dt_arr as $k=>$v)
		{
			$bookings[]=$new_array[$k];
			$i++;
			if($i>=10)
			break;
		}				
		
		$this->set(compact('contacts','invoices','bookings'));
		if(($this->request->is('post') || $this->request->is('put')) && ($_POST['ajax_search']=='yes'))
		{	
			$this->layout = false;
			$this->autoRender = false;			
			$this->render('/Elements/search');
		}		
	}
	
	public function resample($jpgFile, $thumbFile, $width = null, $orientation,$height = null) {
		//Get new dimensions
		list($width_orig, $height_orig) = getimagesize($jpgFile);
		if(!$height)
		$height = (int) (($width / $width_orig) * $height_orig);
		//Resample
		$image_p = imagecreatetruecolor($width, $height);
		$image   = imagecreatefromjpeg($jpgFile);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		//Fix Orientation
		switch($orientation) {
			case 3:
				$image_p = imagerotate($image_p, 180, 0);
				break;
			case 6:
				$image_p = imagerotate($image_p, -90, 0);
				break;
			case 8:
				$image_p = imagerotate($image_p, 90, 0);
				break;
		}		
		imagejpeg($image_p, $thumbFile, 90);
	}
	
	public function group_training_view_now(){	
		$this->set('title_for_layout', __('Now', true));								
	}
	
	public function group_training_view_now_ajax(){			
		$now_UTC=time();
		//$now_UTC=$now_UTC + (14*60*60);
		//$now_UTC='1467420300';
		$now=strtotime($this->General->change_time_toUsertimezone($now_UTC));
		echo date('M d Y, g:ia',$now).'#####';
		$min=date('i',$now);
		if($min%15 !=0 && $_POST['first_view'] !='1')
		{
			echo 'no#####';	
			die();
		}
		$this->loadModel('Booking');
		$joins[]=array(
				'table'=> 'clients',
				'type' => 'INNER',
				'alias' => 'Client',
				'conditions' => array('Booking.client_id = Client.id')
		);	
		$joins[]=array(
				'table'=> 'user_locations',
				'type' => 'left',
				'alias' => 'UserLocation',
				'conditions' => array('Booking.user_location_id= UserLocation.id')
				);
				
		$bookings_repeat_data = $this->Booking->find('all', 
                array('conditions'=>array(
						'Booking.instructor_user_id'=>$this->Auth->user('id'),
						'Booking.type !='=>'hold',
						'Booking.status ='=>1,
						'Booking.class_id ='=>NULL,
						),
						'fields'=>array('Booking.*','(SELECT GROUP_CONCAT(date SEPARATOR \',\') from booking_cancels where booking_id=Booking.id) as booking_cancel','Client.*','UserLocation.*'),	
						'joins'=>$joins,						
						'order'=>'Booking.startdate',				
					 )
            );
		//pr($bookings_repeat_data);die();
		$bookings=array();		
		foreach((array)$bookings_repeat_data as $bk)
		{			
			
			$dt2 = date('m/d/Y H:i:s',strtotime($this->General->change_time_toUsertimezone($bk['Booking']['startdate'])));			
			$dt2 = date_parse($dt2);			
			$repeat_days_arr=explode(',',$bk['Booking']['repeat_days']);			
			$cancel_arr=explode(',',$bk[0]['booking_cancel']);
			
			$day=date('l', $now);	
 			$first_day=false;
			if($bk['Booking']['startdate']<=$now && $bk['Booking']['enddate']>=$now)	
			{
				$first_day=true;
			}
			$booking_startdate_user_timezone =  strtotime($this->General->change_time_toUsertimezone($bk['Booking']['startdate']));
			if((in_array($day,$repeat_days_arr) && $now >= $booking_startdate_user_timezone) || $first_day==true)
			{	
				$dt=date('m/d/Y H:i:s',$now);
				$dt=date_parse($dt);
				
				$sdate=mktime($dt2['hour'],$dt2['minute'],0,$dt['month'],$dt['day'],$dt['year']);
				$sdate_UTC=$this->General->change_time_toUTC($sdate);					
				if($bk['Booking']['repeat_lastdate']>0 && $sdate_UTC>=$bk['Booking']['repeat_lastdate'])
				{
					continue;
				}					
				if(in_array($sdate_UTC,$cancel_arr))
				{
					continue;
				}
				
				$edate =$sdate + ($bk['Booking']['enddate'] - $bk['Booking']['startdate']);						
				//$bk['Booking']['startdate']=date('M d Y, g:ia',$sdate);
				$bk['Booking']['startdate']=$sdate;
				$bk['Booking']['enddate']=$edate;				
				if($sdate <= $now && $edate>$now)
				{
					$bookings[]=$bk;
				}																
			}						
		} 
		
		echo count($bookings).'#####';
		$this->layout=false;
		$this->set(compact('bookings','now'));
	}
	
	public function contacts($srh_txt=null){			
		$this->set('title_for_layout', __('Contacts', true));
		$this->loadModel('Love');
		$this->loadModel('Client');
		$this->loadModel('Booking');
		$this->loadModel('BookingOther');
		$this->loadModel('Client');
		$this->loadModel('Invitation');
		$this->loadModel('Order');
		$filter = array();
		
		$joins[] = array(
			'table'=> 'clients',
            'type' => 'INNER',
            'alias' => 'Client',
            'conditions' => array('Client.id = Love.client_id')			
		);		
		$joins[] = array(
			'table'=> 'bookings',
            'type' => 'LEFT',
            'alias' => 'Booking',
            'conditions' => array('Booking.client_id = Love.client_id'),
			'order'=>array('Booking.id DESC'),
			'Limit'	=>1
		);	
								
		$contacts = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$this->Auth->user('id'),'Love.user_id IS NOT NULL'),'fields'=>array('Love.*','Client.*','if(Client.first!="",Client.first,Client.last) as name','Booking.*'),'group'=>'Love.user_id','joins'=>$joins,'order'=>array('name ASC')));
	
		$unpaid_session = $this->General->getAllUnpaidSessionUser($this->Auth->user('id'));
	
		$this->set(compact('contacts','unpaid_session','srh_txt'));						
	}	
	
	public function agenda($srh_txt=null) {
		$this->set('title_for_layout',__('Agenda'));		
		$this->set(compact('srh_txt'));				
	}
	
	public function agenda_more() {		
		$page = $_POST['page'];
		$current_date = date('m/d/Y',strtotime($this->General->change_time_toUsertimezone(time())));
		$current_date1 = date('m/d/Y',strtotime($current_date.'-1 day'));
		$start_month= ($page * 1)-1 ;
		$end_month= $page * 1;
		$start_date = strtotime($current_date.' +'.$start_month.' Months');
		$end_date = strtotime($current_date1.' +'.$end_month.' Months');
		
		$results = $this->General->get_my_schedule_new($start_date,$end_date);
		$this->set(compact('current_date','start_date','end_date','results','srh_txt'));
		$this->layout=false;	
	}
	
	public function contact_add($action=null,$booking_id=null,$booking_startdate=null){	
		
		$this->set('title_for_layout',__('Client Profile'));
		$this->loadModel('User');
		$this->loadModel('Client');
		$this->loadModel('Love');
		$this->User->bindModel(array('hasOne' =>array('Client')));
		if($this->request->is('post') || $this->request->is('put'))
		{			
			//$this->User->set($this->request->data);
			//$this->User->setValidation('add_email');
			
			//$this->Client->set($this->request->data);
			//$this->Client->setValidation('add_contact');			
			
			$this->request->data['User']['password']= Security::hash('123456', null, true);
			$this->request->data['User']['role_id']= 2;
			$this->request->data['User']['status']= 1;
			$this->request->data['User']['timezone']= $this->Session->read('timezone');
			
			$clientData = $this->User->find('first',array('conditions'=>array('User.email'=>$this->request->data['User']['email'])));
			
			if(isset($clientData) && !empty($clientData) && $this->request->data['User']['email']!="")
			{			
				if($clientData['User']['role_id']!=2)
				{
					$this->request->data['Client']['user_id'] = $clientData['User']['id'];
					$this->Client->save($this->request->data);
					$client_id = $this->Client->getLastInsertId();									
					$this->General->do_love_current_user($clientData['User']['id'],$clientData['Client']['id']);
				}
				else
				{
				$this->General->do_love_current_user($clientData['User']['id'],$clientData['Client']['id']);				$client_id = $clientData['Client']['id'];
				}
			}else{				
				if($this->Client->validates() && $this->User->validates()) 
				{
					$this->User->save($this->request->data);
					$user_id = $this->User->getLastInsertId();
					
					$this->request->data['Client']['user_id'] = $user_id;
					
					$this->Client->save($this->request->data);
					$client_id = $this->Client->getLastInsertId();
									
					$this->General->do_love_current_user($user_id,$client_id);		 	 	 	
					/* $url = SITE_URL; $Email = new CakeEmail(MAIL_SENDER);
					$Email->template('welcome_emailto_client')
						->emailFormat('html')
						->to($this->request->data['User']['email'])
						->from( FROM_MAIL , SITE_NAME)
						->subject('Welcome to FitMeNow!')
						->viewVars(array('url'=>$url,'first' =>$this->request->data['Client']['first'],'password'=>'123456','username'=>$this->request->data['User']['email']))    
						->send(); */
				}
				else
				{
				echo 'error';die('1922');
				}
			}
			$this->Session->setFlash(__('Client has been added successfully.'));	
			if($action=='quick_sale')
			{
				$this->redirect(array('controller'=>'inventories','action'=>$action));	
			}	
			else if($action=='reservation_client')	
			{	
				$this->Session->write('client_id_arr',$client_id);
				$this->redirect(array('controller'=>'reservations','action'=>$action));
			}
			else if($action=='personal_client_list')	
			{	
				$this->Session->write('client_id_arr',$client_id);
				$this->Session->write('client_type_dropdown','all_connection');
				$this->redirect(array('controller'=>'instructors','action'=>$action, $booking_id,$booking_startdate));
			}	
			else
			{
				$this->redirect(array('controller'=>'instructors','action'=>$action, $booking_id));
			}
		}
		$this->set(compact('booking_id','action','booking_startdate'));		
	}

	public function save_profile_image(){	
	
		$path = $_FILES['files']['name'][0];
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$ext = strtolower($ext);
		
		if(in_array($ext,array('jpg','png','jpeg','gif')))
		{
			$newfile['name']=$_FILES['files']['name'][0];
			$newfile['type']=$_FILES['files']['type'][0];
			$newfile['tmp_name']=$_FILES['files']['tmp_name'][0];
			$newfile['error']=$_FILES['files']['error'][0];
			$newfile['size']=$_FILES['files']['size'][0];
					
			list($width, $height) = getimagesize($newfile['tmp_name']);
			
			$img_name = $this->General->imgAvtrUpload($newfile, $newfile);
			
			$newImg = pathinfo($img_name);
			
			if($newImg['extension']=='png')
			{	
				$image = $this->convertImage(USER_PROFILE_DIR.$img_name,USER_PROFILE_DIR.$newImg['filename'].'.jpeg',100);				
				$exif = exif_read_data(USER_PROFILE_DIR.$newImg['filename'].'.jpeg');				
				$img_name = $newImg['filename'].'.jpeg';							
			}
			else
			{
				$exif = exif_read_data(USER_PROFILE_DIR.$img_name);			
			}		
			
			if(isset($exif['Orientation']) && !empty($exif['Orientation']))
			{
				$this->resample(USER_PROFILE_DIR.$img_name,USER_PROFILE_DIR.$img_name,$width,$exif['Orientation']);
				list($width, $height) = getimagesize(USER_PROFILE_DIR.$img_name);	
			}
			
			$this->Session->write('Auth.User.avatar',$img_name);	
			echo 'SUCCESS##'.$img_name.'##'.$width.'##'.$height;
			
		}
		else
		{
			echo 'FAILED';	
		}
		die();		
	}
	
	public function getUnpaidSession(){	
		$this->loadModel('Booking');
		$this->loadModel('Order');
		
		$unpaid_session = array();
		$this->Booking->bindModel(array('belongsTo'=>array('Client'=>array('className'=>'Client','foreignKey'=>'client_id'))));
		$this->Booking->bindModel(array('hasMany'=>array('BookingOther'=>array('className'=>'BookingOther','foreignKey'=>'booking_id')),
										'hasOne'=>array('Order'=>array('className'=>'Order','foreignKey'=>'program_id'))));
		$orders = $this->Order->query('select * from orders');
		foreach($orders as $key=>$val)
		{
			$program_idsarr[] = explode(",",$val['orders']['program_id']);
		} 		
		$program_ids = array_map('current', $program_idsarr);
		$program_id = implode("','",$program_ids);
		
		$unpaid_session = $this->Booking->find('count',array('conditions'=>array('Booking.instructor_user_id'=>$this->Auth->user('id'),'Booking.startdate <='=>time(),'Booking.program_id NOT'=>array($program_id)),'fields'=>array('Booking.id','Booking.user_id','Booking.client_id','Booking.instructor_user_id','Booking.instructor_id','Booking.inventory_id','Booking.program_id','Booking.startdate','Booking.enddate','Booking.id','Booking.id','Order.id','Order.program_id','Order.total','Order.status','Client.id','Client.user_id','Client.first','Client.last')));
		echo $unpaid_session;
		die();
	}
		
	public function stats_add2($client_user_id=null){
		$this->set('title_for_layout', __('Stats', true));
		$this->loadModel("ClientData");
		if($this->request->is('post') || $this->request->is('put')) 
		{			
			$stats_data = json_encode($this->request->data['Client']);
			$this->request->data['ClientData']['stats'] = $stats_data;						
			$this->request->data['ClientData']['type'] = 'stats';			
			$this->request->data['ClientData']['to'] = $this->request->data['Client']['to'];			
			$this->request->data['ClientData']['from'] = $this->Auth->user('id');			
		
			$this->ClientData->saveAll($this->request->data['ClientData']);
			$this->redirect(array('controller'=>'trainings','action' =>'personal_training_calendar_new',$client_user_id));
		}
	}
	
	public function contact_edit($client_user_id=null,$booking_id=null,$startdate=null) {
		$this->loadModel('User');
		$this->loadModel('Client');
		$this->Client->bindModel(array('belongsTo' => array('User')), false);				
        if($this->request->is('post') || $this->request->is('put')) 
		{	
			$this->request->data['Client']['first']=ucfirst($this->request->data['Client']['first']);
			$this->request->data['Client']['last']=ucfirst($this->request->data['Client']['last']);
			$this->request->data['User']['id']=$client_user_id;
			
			$this->Client->set($this->request->data);
			$this->User->set($this->request->data);
			//$this->Client->setValidation('edit_contact');									
			//$this->Client->User->setValidation('add_email');				
			//if($this->Client->saveAll($this->request->data, array('validate' => 'only'))) 
			if($this->Client->saveAll($this->request->data)) 
			{		
				if($this->Client->saveAll($this->request->data))
				{				
					$this->Session->setFlash(__('Information has been updated.'));
					if(!empty($client_user_id) && empty($booking_id))
					$this->redirect(array('controller'=>'trainings','action' => 'personal_training_calendar_new',$client_user_id));
					else
					$this->redirect(array('controller'=>'trainings','action' => 'session_profile',$booking_id,$startdate));
				}
			}else{
				$data = $this->Client->find('first',array('conditions'=>array('Client.user_id'=>$client_user_id)));
				$this->set('title_for_layout', __($data['Client']['first'].' '.$data['Client']['last'], true));	
				$this->Session->setFlash(__('Please correct error listed below.'));
			}	
		}
		else
		{
			$this->request->data = $this->Client->find('first',array('conditions'=>array('Client.user_id'=>$client_user_id)));
			$this->set('title_for_layout', __($this->request->data['Client']['first'].' '.$this->request->data['Client']['last'], true));				
		}
		$this->set(compact('client_user_id','booking_id','startdate'));
    }

	public function reschedule_session($booking_id=null){
		$this->loadModel('Booking');
		$this->loadModel('Client');
		$this->Session->write('booking_id',$booking_id);
				
		$bookingData = $this->Booking->find('first',array('conditions'=>array("Booking.id"=>$booking_id)));
	
		$this->Session->write('client_id_arr',$bookingData['Booking']['client_id']);
		$this->Session->write('client_userid_arr',$bookingData['Booking']['user_id']);
		
		$clientsData = $this->Client->find('all',array('conditions'=>array("Client.id"=>$bookingData['Booking']['client_id'])));
		$this->Session->write('clientsData',$clientsData);
		$this->Session->write('type','reschedule');
		$this->redirect(array('controller'=>'instructors','action'=>'calendar','reschedule'));
	}

	public function change_repeat_session_date($booking_id=null){
		$this->loadModel('Booking');
		$Bookingdata = $this->Booking->find('first',array('conditions'=>array('Booking.id'=>$booking_id)));
		$minute = $Bookingdata['Booking']['enddate'] - $Bookingdata['Booking']['startdate'];
		
		$date_time = $this->request->data['Booking']['date_time'];
		$date_time = date('M d Y g:i A',strtotime($date_time.' +1 hours'));
		
		$duration = $minute/60;
		if(($this->request->is('post') || $this->request->is('put')))
		{
			$startdate = $this->General->change_time_toUTC(strtotime($date_time));	
			$enddate = $startdate + ($duration * 60);				
			
			$this->Booking->updateAll(array('Booking.startdate'=>$startdate,'Booking.enddate'=>$enddate),array('Booking.id' =>$booking_id));
			$this->Session->setFlash(__('Session changed.')); 
			$this->redirect(array('controller'=>'instructors','action'=>'unpaid_resolution',$booking_id));
		}	
	}
	
	public function profile($page=null) {		
		
		$this->set('title_for_layout',__('Profile'));	
	    $this->loadModel('Instructor');            
       			
		if ($this->request->is('post') || $this->request->is('put')) {
		
			$this->Instructor->setValidation('profile');
			$this->Instructor->set($this->request->data);						
			if($this->Instructor->validates()){	
				$this->Instructor->save($this->request->data);				
				$this->redirect(array('action' => 'more'));			
			}		
		}
		else
		{
			$this->request->data = $this->Instructor->findByUser_id($this->Auth->user('id'));	
			$this->request->data['User'] = $this->User->findById($this->Auth->user('id'));	
			$this->request->data['User']=$this->request->data['User']['User'];					
			unset($this->request->data['User']['User']);
		}				
		$instructor=$this->Instructor->findByUser_id($this->Auth->user('id'));
        $this->set(compact('instructor','page'));			        
    }

	public function set_service_name()
	{
		$this->layout = false;
		$this->autoRender = false;
		$this->Session->write('service_name',$_POST['service']);
		$this->Session->write('training_ids',$_POST['training_ids']);
		die;
	}	
}