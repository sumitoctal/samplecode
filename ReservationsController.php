<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClientsController
 *
 * @author williamcrown
 */
#App::import('Controller', 'Reservations');

/* load CakePHP Email component */
App::uses('CakeEmail', 'Network/Email');
class ReservationsController extends AppController {
    
	public $helpers = array('Html', 'Form');    
    public $scaffold = 'admin'; 
    public $components = array('General','Upload');
    
  

	public function beforeFilter(){
		
		parent::beforeFilter();
		$this->Security->blackHoleCallback = 'blackhole';	
		
		$this->Auth->allow();
		$this->Auth->deny();
		
		$this->Security->validatePost=false;
		$this->Security->csrfCheck=false;
		
		$this->layout = 'instructor';
		
	}

	public function reservation_client(){	
		$this->set('title_for_layout',__('Create Reservation'));
		$this->loadModel('Client');
		$this->loadModel('Love');
		$this->loadModel('Client');
		$this->loadModel('Booking');
		$this->loadModel('Invitation');
		
		if($this->request->is('post') || $this->request->is('put')) 
		{	
			if(isset($this->request->data['Invite']['date_time']))
			{
				$date_time=$this->request->data['Invite']['date_time'];
				$date_time=date('M d Y g:i A',strtotime($date_time));
								
				$this->Session->write('date_time',$date_time);
				$this->Session->write('duration',$this->request->data['Invite']['duration']);
				$this->Session->write('weekname',$this->request->data['Invite']['weekname']);
				
				if($this->Session->check('client_userid_arr'))
				{
					$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
				}
				
			}	
		}
		$this->set('date_time',$this->Session->read('date_time'));
		$this->set('duration',$this->Session->read('duration'));
		$this->set('weekname',$this->Session->read('weekname'));	
		
		$filter = array();
		$bookingIdsArr = array();
		$clientIds = array();
		
	
		$joins[]=array(
			'table'=> 'bookings',
			'type' => 'LEFT',
			'alias' => 'Booking',
			'conditions' => array('Client.id = Booking.client_id')
		);
		$orderfilters = array('Client.id DESC');
		
		if(isset($this->request->data['client_type']) && $this->request->data['client_type'] == 'all_connection')
		{
			if(isset($_POST['client_userid_arr']) && count($_POST['client_userid_arr']>=1))
			{
				$client_id_arr_str=implode(',',$_POST['client_id_arr']);
				$client_userid_arr_str=implode(',',$_POST['client_userid_arr']);
				
				$this->Session->write('client_id_arr',$client_id_arr_str);
				$this->Session->write('client_userid_arr',$client_userid_arr_str);
			}	
			
			//Get all clients			
			$favouriteClientIdsArr = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$this->Auth->user('id')),'fields'=>array('DISTINCT client_id')));
			foreach((array)$favouriteClientIdsArr as $keyfav=>$valfav)
			{
				$clientIds[] = $valfav['Love']['client_id'];
			}
			//Get all clients			

		}else{
			
			if(isset($_POST['client_userid_arr']) && count($_POST['client_userid_arr']>=1))
			{	
				$client_id_arr_str=implode(',',$_POST['client_id_arr']);
				$client_userid_arr_str=implode(',',$_POST['client_userid_arr']);
				
				$this->Session->write('client_id_arr',$client_id_arr_str);
				$this->Session->write('client_userid_arr',$client_userid_arr_str);
			}
			
			//Get current clients
			$bookingIdsArr = $this->Booking->find('all',array('conditions'=>
						array('Booking.instructor_user_id'=>$this->Auth->user('id'),
								'reject_reason IS NULL',
								'reject_comment IS NULL',
								'Booking.type !='=>'hold',
								'OR'=>array('DATE_FORMAT(Booking.startdate,"%Y-%m-%d")'=>'DATE_SUB(CURDATE(), INTERVAL 30 DAY)','Booking.repeat_days IS NOT NULL','Booking.startdate >'=>time())),
								'fields'=>array('DISTINCT client_id'))				
								);
			
			foreach((array)$bookingIdsArr as $key2=>$val2)
			{
				$clientIds[] = $val2['Booking']['client_id'];
			}	
			
			$favouriteClientIdsArr = $this->Love->find('all',array('conditions'=>array('Love.instructor_user_id'=>$this->Auth->user('id'),'DATE_FORMAT(Love.created,"%Y-%m-%d") = DATE_SUB(CURDATE(), INTERVAL 30 DAY)'),'fields'=>array('DISTINCT client_id','created')));
			//pr($favouriteClientIdsArr);die;
			foreach((array)$favouriteClientIdsArr as $keyfav=>$valfav)
			{
				$clientIds[] = $valfav['Love']['client_id'];
			}
			//Get current clients	
		}	
		//pr($clientIds);die;
		
		if(isset($clientIds) && !empty($clientIds))
		{
			$instStr = implode("','",(array)$clientIds);
			$filters[] = array("Client.id IN ('".$instStr."')");
			$clientsData = $this->Client->find('all',array('conditions'=>$filters,'joins'=>$joins,'order'=>$orderfilters,'group'=>array('Client.id')));
			$this->set(compact('clientsData',$clientsData));
		}
		
		
		if($this->request->is('ajax'))
		{
			$this->layout = false;
			$this->autoRender = false;
			$this->render('/Elements/reservation_invite_client_list');				
		}
	}
		
	public function reservation_commit($booking_id = null,$startdate=null){
		$this->set('title_for_layout',__('Create Reservation'));
		
		$this->loadModel('Client');
		$this->loadModel('Inventory');
		$this->loadModel('UserLocation');
		$this->loadModel('Booking');
		$this->loadModel('BookingOther');
		$trainingtype_id = '';
		$trainingtype_name = '';
		$location_id = '';
		$location_name = '';
		$clientsData = array();	
		
		if($this->request->is('post') || $this->request->is('put')) 
		{
			$this->Session->write('client_type_dropdown',$this->request->data['Invite']['client_type_dropdown']);
			$this->Session->write('client_id_arr',$this->request->data['Invite']['client_id']);
			$this->Session->write('client_userid_arr',$this->request->data['Invite']['client_user_id']);
			$this->Session->write('action','reservation_commit');
			$clientIds = explode(',',$this->request->data['Invite']['client_id']);
			$clientStr = implode("','",(array)$clientIds);
			$filters[] = array("Client.id IN ('".$clientStr."')");
			$clientsData = $this->Client->find('all',array('conditions'=>$filters));
			$this->Session->write('clientsData',$clientsData);
		}
		
		$defaultlocationArr = array();
		$defaultlocation = '';
		$allTrainings = $this->Inventory->find('all',array('conditions'=>array('Inventory.user_id'=>$this->Auth->user('id'),'Inventory.type'=>'training','Inventory.status'=>1)));
		
		$deafultTrainings = $this->Inventory->find('first',array('conditions'=>array('Inventory.user_id'=>$this->Auth->user('id'),'Inventory.type'=>'training','Inventory.status'=>1,'Inventory.default'=>1)));
		
		$userlocation = $this->UserLocation->find('all',array('conditions'=>array('UserLocation.user_id'=>$this->Auth->user('id'))));
		
		$defaultlocationArr = $this->UserLocation->find('first',array('conditions'=>array('UserLocation.user_id'=>$this->Auth->user('id'),'UserLocation.default'=>1,'UserLocation.status'=>1)));
		
		if(!empty($defaultlocationArr))
		$defaultlocation = $defaultlocationArr['UserLocation']['id'];
		
		if(isset($booking_id) && !empty($booking_id))
		{
			$this->Booking->bindModel(array('belongsTo'=>array('Client','UserLocation','Inventory')));
			$bookData = $this->Booking->find('first',array('conditions'=>array('Booking.id'=>$booking_id)));
			
			$bookData2[]['Client'] = $bookData['Client'];
					
			$minute = ($bookData['Booking']['enddate'] - $bookData['Booking']['startdate'])/60;
			$this->Session->write('date_time',date('M d Y',$startdate).' '.date('g:ia',strtotime($this->General->change_time_toUsertimezone($bookData['Booking']['startdate']))));
			
			$this->Session->write('duration',$minute);		
			$this->Session->write('weekname',$bookData['Booking']['repeat_days']);
			$this->Session->write('clientsData',$bookData2);
			$this->Session->write('client_id_arr',$bookData['Client']['id']);
			$this->Session->write('client_userid_arr',$bookData['Client']['user_id']);
			$this->Session->write('booking_id',$booking_id);
			
			$this->Session->write('trainingtype_id',$bookData['Inventory']['id']);
			$this->Session->write('trainingtype_name',$bookData['Inventory']['name']);
			$this->Session->write('location_id',$bookData['Booking']['user_location_id']);
			$this->Session->write('location_name',$bookData['UserLocation']['location_name']);
			
			$this->BookingOther->bindModel(array('belongsTo'=>array('Inventory')));
			$inventoriesData = $this->BookingOther->find('all',array('conditions'=>array('BookingOther.booking_id'=>$booking_id),'fields'=>array('BookingOther.id','BookingOther.inventory_id','Inventory.id','Inventory.name')));
			
			$deafultTrainings['Inventory']['name'] = $bookData['Inventory']['name'];	
			$deafultTrainings['Inventory']['id'] = $bookData['Inventory']['id'];	
			$defaultlocation = $bookData['UserLocation']['id'];	
			
		}
	
		$this->set(compact('allTrainings','userlocation','deafultTrainings','defaultlocation','booking_id','startdate','inventoriesData'));	
		$this->set('date_time',$this->Session->read('date_time'));
		$this->set('duration',$this->Session->read('duration'));
		$this->set('weekname',$this->Session->read('weekname'));	
		$this->set('clientsData',$this->Session->read('clientsData'));	
		$this->set('client_id_arr',$this->Session->read('client_id_arr'));	
		$this->set('client_userid_arr',$this->Session->read('client_userid_arr'));	
		$this->set('trainingtype_id',$this->Session->read('trainingtype_id'));	
		$this->set('trainingtype_name',$this->Session->read('trainingtype_name'));	
		$this->set('location_id',$this->Session->read('location_id'));	
		$this->set('location_name',$this->Session->read('location_name'));					
	}
	
	public function reservation_add(){
		$this->loadModel('Client');
		$this->loadModel('User');
		$this->loadModel('Booking');
		$this->loadModel('UserLocation');		
		$this->loadModel('BookingOther');
		$this->loadModel('Inventory');
		$this->loadModel('Love');	
			
		$userPricingArr = array();
		$client_userid_arr = array();
		$total_price ='';
		$sessionday ='';
		$training_id =  array();
		
		
		$type = '1-on-1 session';
		if(($this->request->is('post') || $this->request->is('put')))
		{
			$training_id = explode(',',$this->request->data['Invite']['trainingtype_id']);				
			$startdate = $this->General->change_time_toUTC(strtotime($this->request->data['Invite']['invite_time']));
			$enddate = $startdate + ($this->request->data['Invite']['duration'] * 60);				
			$client_userid_arr = explode(',', $this->request->data['Invite']['client_userids']);
			$minutebook = $this->request->data['Invite']['duration'];							
			$repeat_schedule = str_replace("_", ",", $this->request->data['Invite']['repeat_day']);
		
			if($this->Session->read('type')=='reschedule')
			{	
				$rescheduleBooking = $this->Booking->find('first',array('conditions'=>array('Booking.id'=>$this->Session->read('booking_id'))));
				$this->Booking->updateAll(array('Booking.startdate'=>$startdate,'Booking.enddate'=>$enddate,'Booking.reshedule_date'=>$rescheduleBooking['Booking']['startdate'],'Booking.reshedule_status'=>1),array('Booking.id' =>$this->Session->read('booking_id')));
			}else{
			
				if($this->Session->read('booking_id')!='')
				{
					$this->Booking->bindModel(array('hasMany'=>array('BookingOther'=>array('exclusive'=>true,'dependent'=>true))),false);
					$this->Booking->deleteAll(array('Booking.id'=>$this->Session->read('booking_id')), true);			
				}
						
				$i=1;
				foreach($client_userid_arr as $key=>$val)
				{
					$this->User->bindModel(array('hasOne'=>array('Client')));
					$clientData = $this->User->find('first',array('conditions'=>array('User.id'=>$val)));
					
					$book_data['Booking']['user_id']= $clientData['User']['id'];
					$book_data['Booking']['client_id']= $clientData['Client']['id'];					
					$book_data['Booking']['user_location_id']= $this->request->data['Invite']['location_id'];
					$book_data['Booking']['instructor_user_id']= $this->Auth->user('id');
					$book_data['Booking']['instructor_id']= $this->Auth->user('instructor_id');						
					$book_data['Booking']['type']= 'reserve';
					$book_data['Booking']['program_id']= time().$i;
					$book_data['Booking']['price_id']= '';				
					$book_data['Booking']['inventory_id']= $training_id[0];
					$book_data['Booking']['startdate'] = $startdate;
					$book_data['Booking']['enddate'] = $enddate;												
					$book_data['Booking']['repeat_days'] = $this->request->data['Invite']['repeat_day'];
					
					$this->Booking->saveAll($book_data['Booking']);	
					$lastbooking_id = $this->Booking->getLastInsertID();
					$data=array();
					foreach((array)$training_id as $key=>$val)
					{	
						
						$trainingData = $this->Inventory->find('first',array('conditions'=>array('Inventory.id'=>$val)));
						
						$data['BookingOther'][$key]['user_id'] = $clientData['User']['id'];
						$data['BookingOther'][$key]['client_id'] = $clientData['Client']['id'];
						$data['BookingOther'][$key]['instructor_user_id'] = $this->Auth->user('id');
						$data['BookingOther'][$key]['instructor_id'] = $this->Auth->user('instructor_id');
						$data['BookingOther'][$key]['booking_id'] = $lastbooking_id;
						$data['BookingOther'][$key]['inventory_id'] = $val;	 							
						$data['BookingOther'][$key]['quantity'] = 1;
						if(isset($trainingData['Inventory']['price']))
						{
							$data['BookingOther'][$key]['price'] = $trainingData['Inventory']['price'];
							$data['BookingOther'][$key]['total'] = $trainingData['Inventory']['price'];
							$data['BookingOther'][$key]['gtotal'] = $trainingData['Inventory']['price'];
						}
						$data['BookingOther'][$key]['status'] = 1;
					}						
					$this->BookingOther->saveAll($data['BookingOther']);
					
					/* $data['Love']['user_id'] = $clientData['User']['id'];
					$data['Love']['client_id'] = $clientData['Client']['id'];
					$data['Love']['instructor_user_id'] = $this->Auth->user('id');
					$data['Love']['instructor_id'] = $this->Auth->user('instructor_id');
					$this->Love->save($data['Love']); */					
					$i++;	
				}
			}
			
			$this->Session->delete('duration');
			$this->Session->delete('weekname');
			$this->Session->delete('client_id_arr');
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
			$this->Session->delete('back_action');
			$this->Session->delete('service_name');
			$this->Session->delete('training_ids');
		
			$this->redirect(array('controller'=>'instructors','action'=>'calendar',$startdate));				
		}
	}
	
	public function reservation_locationadd(){
		$this->loadModel('UserLocation');			
		if($this->request->is('post')){
			if(!empty($this->request->data)){
				
				$this->UserLocation->updateAll(array('UserLocation.default' => 0),array('UserLocation.user_id' => $this->Auth->user('id')));
				
				$this->request->data['UserLocation']['location_name']=$this->request->data['UserLocation']['location_name'];
				$this->request->data['UserLocation']['user_id']= $this->Auth->user('id');
				$this->request->data['UserLocation']['instructor_id']= $this->Auth->user('instructor_id');
				$this->request->data['UserLocation']['address']= $this->request->data['UserLocation']['address'];
				$this->request->data['UserLocation']['city']= $this->request->data['UserLocation']['city'];
				$this->request->data['UserLocation']['zip']= $this->request->data['UserLocation']['zip'];
				$this->request->data['UserLocation']['default']= '1';
				
				$this->UserLocation->save($this->request->data['UserLocation']);
				
				$this->redirect(array('controller'=>'reservations','action' => 'reservation_commit'));
			}
		}
	}

	public function reservation_add_personal_time(){
		$this->loadModel('Booking');		
		if(($this->request->is('post') || $this->request->is('put')))
		{	
			$startdate = $this->General->change_time_toUTC(strtotime($this->request->data['Booking']['date_time'].' +1 hours'));			
			$enddate = $startdate + ($this->request->data['Booking']['duration'] * 60);								
			
			$book_data=array();
			$book_data['Booking']['user_id']= $this->Auth->user('id');
			$book_data['Booking']['client_id']= $this->Auth->user('instructor_id');								
			$book_data['Booking']['instructor_user_id']= $this->Auth->user('id');
			$book_data['Booking']['instructor_id']=$this->Auth->user('instructor_id');						
			$book_data['Booking']['type']='personal_time';
			$book_data['Booking']['program_id']= time();			
			$book_data['Booking']['startdate'] = $startdate;
			$book_data['Booking']['enddate'] = $enddate;												
			$book_data['Booking']['repeat_days'] = $this->request->data['Booking']['weekname'];
			$book_data['Booking']['memo'] = $this->request->data['Booking']['memo'];
			
			$this->Booking->saveAll($book_data['Booking']);			
			
			$this->redirect(array('controller'=>'instructors','action'=>'calendar',$startdate));				
		}
	}
	
	public function reservation_location_makeundefault($id=null){
		$this->loadModel('UserLocation');		
		$this->UserLocation->updateAll(array('UserLocation.default'=>0),array('UserLocation.id'=>$id));
		$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));					
	}
	
	public function reservation_location_delete($id=null){
		$this->loadModel('UserLocation');
	
		if ($this->UserLocation->delete(array('UserLocation.id' => $id), true)) 
		{			
			$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
		}				
	}	
	
	public function reservation_location_edit($id=null){
		$this->loadModel('UserLocation');
		
		if($this->request->is('post') || $this->request->is('put'))
		{
			$data['UserLocation']['id'] = $this->request->data['UserLocationEdit']['userloc_id'];
			$data['UserLocation']['location_name'] = $this->request->data['UserLocationEdit']['location_name'];
			$data['UserLocation']['address'] = $this->request->data['UserLocationEdit']['address'];
			$data['UserLocation']['city'] = $this->request->data['UserLocationEdit']['city'];
			$data['UserLocation']['zip'] = $this->request->data['UserLocationEdit']['zip'];
			$this->UserLocation->save($data['UserLocation']);			
			$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
		}		
	}
		
	public function ajax_fecth_loaction(){
		$this->loadModel('UserLocation');
		if($this->request->is('ajax')) {			
			$this->layout = false;
			$this->autoRender = false;
			$locData = $this->UserLocation->find('first',array('conditions'=>array('UserLocation.id'=>$_POST['location_id'])));
			$response['status'] = true;
			$response['location_name'] = $locData['UserLocation']['location_name'];
			$response['address'] = $locData['UserLocation']['address'];
			$response['city'] = $locData['UserLocation']['city'];			
			$response['zip'] = $locData['UserLocation']['zip'];			
			$this->response->type('application/json; charset=utf-8');
            $this->response->body(json_encode($response));
            return $this->response;						
        }	
	}
	
	public function reservation_location_makedefault($id=null){
		$this->loadModel('UserLocation');		
		if($this->UserLocation->updateAll(array('UserLocation.default'=>1),array('UserLocation.id'=>$id))) 
		{	
			$this->UserLocation->updateAll(array('UserLocation.default' => 0),array('UserLocation.user_id' => $this->Auth->user('id'),'UserLocation.id !='=>$id));
			$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
		}				
	}

	public function reservation_location(){	
		$this->set('title_for_layout',__('Select Location')); 
		$this->loadModel('UserLocation');
		
		if($this->request->is('post') || $this->request->is('put')) 
		{
			$this->Session->write('location_id',$this->request->data['Invite']['location_id']);
			$this->Session->write('location_name',$this->request->data['Invite']['location_name']);
			$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
		}
		
		$userlocation = $this->UserLocation->find('all',array('conditions'=>array('UserLocation.user_id'=>$this->Auth->user('id'))));		
		$this->set(compact('userlocation'));	
	}	
	
	public function reservation_service(){	
		$this->set('title_for_layout',__('Select Services')); 		
		$this->loadModel('Inventory');
		
		if($this->request->is('post') || $this->request->is('put')) 
		{	
			$this->Session->write('trainingtype_id',$this->request->data['Invite']['trainingtype_id']);
			$this->Session->write('trainingtype_name',$this->request->data['Invite']['trainingtype_name']);
			
			$this->redirect(array('controller'=>'reservations','action'=>'reservation_commit'));
		}			
			
		$allTrainings = $this->Inventory->find('all',array('conditions'=>array('Inventory.user_id'=>$this->Auth->user('id'),'Inventory.type'=>'training','Inventory.status'=>1)));
				
		$this->set(compact('allTrainings'));	
	}
	
	public function reservation_client_set_session($user_id=null,$client_id=null){
		$this->loadModel('Client');
		$this->Session->write('client_id_arr',$client_id);
		$this->Session->write('client_userid_arr',$user_id);		
		$clientsData = $this->Client->find('all',array('conditions'=>array("Client.id"=>$client_id)));
		$this->Session->write('clientsData',$clientsData);

		$this->redirect(array('controller'=>'instructors','action'=>'calendar','show_date'));
	}
	
	public function cancel_current_session($booking_id=null,$start_date=null,$action=null){
		$this->loadModel('BookingCancel');
		$start_date_UTC=$this->General->change_time_toUTC($start_date);	
		$data['BookingCancel']['instructor_user_id'] = $this->Auth->user('id');
		$data['BookingCancel']['instructor_id'] = $this->Auth->user('instructor_id');
		$data['BookingCancel']['booking_id'] = $booking_id;
		$data['BookingCancel']['date'] = $start_date_UTC;
		if($this->BookingCancel->saveAll($data['BookingCancel']))
		{	
			$bookingcancel_id = $this->BookingCancel->getLastInsertId();
			$this->Session->setFlash(__('Session cancelled &nbsp;&nbsp;&nbsp;<a href="'.SITE_URL .'reservations/undo_cancel_session/'.$bookingcancel_id.'/'.$booking_id.'/'.$start_date.'">[Undo]</a>'));
			if($action=='unpaid_resolution')
			{
				$this->redirect(array('controller'=>'instructors','action' => 'calendar',$start_date));
			}else{				
				$this->redirect($this->referer());
			}	
		}	
	}
	
	public function undo_cancel_session($id=null,$booking_id=null,$start_date=null,$action=null){
		
		$this->loadModel('BookingCancel');		
		if($this->BookingCancel->delete(array('BookingCancel.id'=>$id), false)) 
		{
			$this->Session->setFlash(__('The cancellation is ...cancelled'));
			if($action=='unpaid_resolution')
			{
				$this->redirect(array('controller'=>'instructors','action' => 'calendar',$start_date));
			}else{	
				$this->redirect($this->referer());
			}	
		}		
	}
	
	public function cancel_all_future_session($booking_id=null,$start_date=null,$action=null){
		
		$this->loadModel('Booking');
		$start_date_UTC=$this->General->change_time_toUTC($start_date);	
		if($this->Booking->updateAll(array('Booking.repeat_lastdate' => $start_date_UTC), array('Booking.id' =>$booking_id)))
		{				
			$this->Session->setFlash(__('All future session cancelled &nbsp;&nbsp;&nbsp;<a href="'.SITE_URL .'reservations/undo_future_session/'.$booking_id.'/'.$start_date.'">[Undo]</a>'));
			if($action=='unpaid_resolution')
			{
				$this->redirect(array('controller'=>'instructors','action' => 'calendar',$start_date));
			}else{	
				$this->redirect(array('controller'=>'trainings','action' =>'session_profile',$booking_id,$start_date));
			}	
		}	
	}
	
	public function undo_future_session($booking_id=null,$start_date=null,$action=null){	
		$this->loadModel('Booking');
		if($this->Booking->updateAll(array('Booking.repeat_lastdate'=>null), array('Booking.id' =>$booking_id)))
		{	
			$this->Session->setFlash(__('The cancellation is ...cancelled')); 
			if($action=='unpaid_resolution')
			{
				$this->redirect(array('controller'=>'instructors','action' => 'calendar',$start_date));
			}else{
				$this->redirect(array('controller'=>'trainings','action' =>'session_profile',$booking_id,$start_date));
			}	
		}
	}
	
	
}// end for contrller class
?>