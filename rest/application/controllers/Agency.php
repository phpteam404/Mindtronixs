<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Agency extends REST_Controller
{
    public $user_id = 0 ;
    public $session_user_id=NULL;
    public $session_user_info=NULL;
    public function __construct()
    {//Constructor
        parent::__construct();
        if(isset($_SERVER['HTTP_USER'])){
            $this->user_id = $_SERVER['HTTP_USER'];
        }

        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
        //print_r($this->session_user_info->id_user); exit;       
    }

    public function PostAgency_post()
    {   
        //this function is to add/update agency information.
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('agency_name_req')));
        // $this->form_validator->add_rules('address', array('required'=>$this->lang->line('agency_add_req')));
        $this->form_validator->add_rules('franchise_code', array('required' =>$this->lang->line('franchisecode_req')));
        $this->form_validator->add_rules('email',array('required'=>$this->lang->line('agency_email')));
        $this->form_validator->add_rules('owner_name',array('required'=>$this->lang->line('owner_name_req')));
        $this->form_validator->add_rules('primary_contact',array('required'=>$this->lang->line('agency_phone_primary')));
        $this->form_validator->add_rules('country',array('required'=>$this->lang->line('country_req')));
        $this->form_validator->add_rules('state',array('required'=>$this->lang->line('state_req')));
        $this->form_validator->add_rules('city',array('required'=>$this->lang->line('city_req')));
        $this->form_validator->add_rules('fee_master_id',array('required'=>$this->lang->line('fee_master_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $add = array(
            'name' => !empty($data['name'])?$data['name']:'',
            'franchise_code'  => !empty($data['franchise_code'])?$data['franchise_code']:'',
            'website_address' => !empty($data['website_address'])?$data['website_address']:'',
            'owner_name'=>!empty($data['owner_name'])?$data['owner_name']:'',
            'email' => !empty($data['email'])?$data['email']:'',
            'primary_contact' =>!empty($data['primary_contact'])?$data['primary_contact']:'',
            'pincode' =>!empty($data['pincode'])?$data['pincode']:'',
            'address'=>!empty($data['address'])?$data['address']:'',
            'country'=>!empty($data['country'])?$data['country']:'',
            'state'=>!empty($data['state'])?$data['state']:'',
            'landmark'=>!empty($data['landmark'])?$data['landmark']:'',
            'status'=>isset($data['status'])?$data['status']:1,
            'city'=>isset($data['city'])?$data['city']:'',
            'agency_contacts'=>isset($data['agency_contacts'])?$data['agency_contacts']:''
        );
        // print_r($add);exit;
        if(isset($data['agency_id']) && $data['agency_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('agency',$add,array('id'=>$data['agency_id']));
            $Insert = true;
            if($update > 0){
                $this->User_model->delete_data('agency_fee',array('agency_id' => $data['agency_id']));
                $Insert = $this->createFeeMaster($data);
            }
            if($update > 0 && $Insert){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('agency_update'),'data' =>'2');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'4');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
        else{
            $add['created_on'] = currentDate();
            $add['created_by'] = $this->session_user_id;
            $addData = $this->User_model->insertdata('agency',$add);
            $Insert = true;
            if($addData){
                $data['agency_id'] = $addData;
                $Insert = $this->createFeeMaster($data);
            }
            if($addData > 0 && $Insert){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('agency_create'), 'data' => '1');
                $this->response($result, REST_Controller::HTTP_OK);  
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'3');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
       
        $result = array('status'=>TRUE, 'message' => $this->lang->line('Success'), 'data'=>'5');
        $this->response($result, REST_Controller::HTTP_OK);
    }

    function createFeeMaster($data)
    {
        //This Function will Create Agency fee records.
        if(isset($data['fee_master_id'])){
            // if((explode(',',$data['fee_master_id']))>0)
            // $fee_master_id_exp=explode(',',$data['fee_master_id']);
            $fee_master_id_exp =json_decode($data['fee_master_id']);
            $insert_batch_array = array();
            foreach($fee_master_id_exp as $k=>$v){
                $insert_batch_array[$k] = array(
                    'agency_id'=>$data['agency_id'],
                    'fee_master_id'=>$v,
                    'created_by'=>$this->session_user_id,
                    'created_on'=>currentDate()
                );
            }
            if($this->User_model->insertbatch('agency_fee',$insert_batch_array) > 0)
                return true;
            else
                return false;
        }
        return true;//If condition not Set.
    }

    public function franchiseList_get() 
    {
        //this function is used to get franchise(agencies) list information
        $data = $this->input->get(); 
        //$data = tableOptions($data);
        $result = $this->Agency_model->listfranchise($data);//echo $this->db->last_query();exit;
        $franchise_list=$result['data'];
        foreach($franchise_list as $f=>$l){ 
            if(!empty($l['agency_contacts']) && !empty($data['agency_id'])){
                $franchise_list[$f]['agency_contacts']=json_decode($l['agency_contacts']);
            }
            if(!empty($franchise_list[$f]['agency_contacts'])){
                foreach($franchise_list[$f]['agency_contacts'] as $k=>$v){
                    $franchise_list[$f]['agency_contact_list'][$k]['name']=$v->name;
                    $franchise_list[$f]['agency_contact_list'][$k]['contact_phone']=$v->contact_phone;
                    $franchise_list[$f]['agency_contact_list'][$k]['contact_title']=$v->contact_title;
                }              
            }    
            if(!empty($data['agency_id'])){
                $franchise_list[$f]['city']=getObjOnId($l['city'],true);
                $franchise_list[$f]['state']=getObjOnId($l['state'],true);
                $franchise_list[$f]['country']=getObjOnId($l['country'],true);
                $franchise_list[$f]['status']=getStatusObj($l['status']);//Getting Objects for dropdown When One record is needed.
            }
            else{
                $franchise_list[$f]['city']=getObjOnId($l['city'],false);
                $franchise_list[$f]['status']=getStatusText($l['status']);//Getting Lable for List when List is needed.

            } 
            unset($franchise_list[$f]['agency_contacts']);
            $agency_fee=$this->User_model->check_record('agency_fee',array('agency_id'=>$franchise_list[$f]['agency_id'],'status'=>1));
           foreach($agency_fee as $a=>$b){
                $fee_details=$this->User_model->check_record('fee_master',array('id'=>$b['fee_master_id']));
                if(!empty($fee_details) && !empty($data['agency_id'])){
                    $franchise_list[$f]['fee_details'][$a]['fee_title']=$fee_details[0]['name'];
                    $franchise_list[$f]['fee_details'][$a]['fee_amount']=$fee_details[0]['amount'];
                    $franchise_list[$f]['fee_details'][$a]['discount']=$fee_details[0]['discount'];
                    $term=$this->User_model->check_record_selected('child_name as term','master_child',array('id'=>$fee_details[0]['term']));
                    $franchise_list[$f]['fee_details'][$a]['term']=$term[0]['term'];

                }
                
           }  
        }
        // if(!empty($data['agency_id'])){
        //     $no_of_students = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_students FROM student where school_id!=0 and  agency_id = '.$data['agency_id']);
        //     $no_of_schools = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_schools FROM school_master where status!=0 and  agency_id = '.$data['agency_id']);
        //     $no_of_trainers=$this->User_model->check_record('user',array('user_role_id'=>3,'agency_id'=>$data['agency_id'],'user_status'=>1));
        //     $student_invoice_amount=0;
        //     $student_collected_amount=0;
        //     $mindtronix_invoice_amount=0;
        //     $mindtronix_collected_amount=0;
        //     $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$franchise_list,'total_records' =>$result['total_records'],'no_of_student'=>!empty($no_of_students[0]['no_of_students'])?$no_of_students[0]['no_of_students']:'0','no_of_schools'=>!empty($no_of_schools[0]['no_of_schools'])?$no_of_schools[0]['no_of_schools']:'0','no_of_trainers'=>!empty(count($no_of_trainers))?count($no_of_trainers):'0','student_invoice_amount'=>$student_invoice_amount,'student_collected_amount'=>$student_collected_amount,'mindtronix_invoice_amount'=>$mindtronix_invoice_amount,'mindtronix_collected_amount'=>$mindtronix_collected_amount);
        // }
        // else{
        // }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$franchise_list,'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function addSchool_post() 
    {//this function is used to add/update schools information
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('school_name')));
        $this->form_validator->add_rules('address', array('required'=>$this->lang->line('school_add')));
        $this->form_validator->add_rules('email',array('required' =>$this->lang->line('school_email')));
        $this->form_validator->add_rules('phone',array('required' =>$this->lang->line('school_phone')));
        $this->form_validator->add_rules('agency_id',array('required' =>$this->lang->line('agency_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        $add = array(
            'name' => $data['name'],
            'agency_id'=>$data['agency_id'],
            'address' =>$data['address'],
            'email'  =>$data['email'],
            'phone' => $data['phone'],
            'status' =>isset($data['status'])?$data['status']:1,
        );
        if(isset($data['id']) && $data['id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('school_master',$add,array('id'=>$data['id']));
            //echo ''.$this->db->last_query(); exit;
            if($update>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('school_update'),'data' =>'2');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'4');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
        else{
            $add['created_on'] = currentDate();
            $add['created_by'] = $this->session_user_id;
            $addData = $this->Agency_model->addSchool($add);
            //echo ''.$this->db->last_query(); exit;
            if($addData >0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('school_create'), 'data' => '1');
                $this->response($result, REST_Controller::HTTP_OK);  
            }
            else{
                 $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'3');
                 $this->response($result, REST_Controller::HTTP_OK);
            }
        }
       
        $result = array('status'=>TRUE, 'message' => $this->lang->line('Success'), 'data'=>'5');
        $this->response($result, REST_Controller::HTTP_OK);

    }

    public function schoolsList_get() 
    {
        //this function is used to get schools list information
        $data = $this->input->get();
        // $data = tableOptions($data);
        $result = $this->Agency_model->listSchools($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public  function agencyInfo_get()//this function used to get franchise information
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('agency_id',array('required' =>$this->lang->line('agency_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $agency_info=$this->Agency_model->getAgencyInfo($data);//this model used for get the franchise information
        foreach($agency_info as $k=>$v){
            $agency_info[$k]['agency_contacts']=json_decode($v['agency_contacts']);
            if(!empty($agency_info[$k]['agency_contacts'])){
                foreach($agency_info[$k]['agency_contacts'] as $k1=>$v1){//this loop for get contacts of the franchise
                    $agency_info[$k]['agency_contacts_information'][$k1]['contact_title']=$v1->contact_title;
                    $agency_info[$k]['agency_contacts_information'][$k1]['contact_name']=$v1->name;
                    $agency_info[$k]['agency_contacts_information'][$k1]['contact_phone']=$v1->contact_phone;
                    $agency_info[$k]['agency_contacts_information'][$k1]['contact_eamil']=$v1->contact_eamil;
                } 
            }
            if(!empty($agency_info[$k]['fee_master_id'])){
                $feemaster_ids=explode(",",$agency_info[$k]['fee_master_id']);
                foreach($feemaster_ids as $k2 =>$v2){//this loop for get fee details of franchise
                    $get_fee_data=$this->Agency_model->getFeeData(array('fee_master_id'=>$v2));//thsi model used for get fee data of franchise
                    if(!empty($get_fee_data)){
                        $agency_info[$k]['fee_detalis'][$k2]=$get_fee_data[0];
                        unset($get_fee_data);
                    }

                }
            }
            $agency_info[$k]['agency_contacts_information']= !empty($agency_info[$k]['agency_contacts_information'])?$agency_info[$k]['agency_contacts_information']:array();
            $agency_info[$k]['fee_detalis']= !empty($agency_info[$k]['fee_detalis'])?$agency_info[$k]['fee_detalis']:array();
            
        }
        unset($agency_info[$k]['agency_contacts']);
        $no_of_students = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_students FROM student where school_id!=0 and  agency_id = '.$data['agency_id']);//get the no of students in the franchise
        $no_of_schools = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_schools FROM school_master where status!=0 and  agency_id = '.$data['agency_id']);//get the no of schools in the franchise
        $no_of_trainers=$this->User_model->check_record('user',array('user_role_id'=>3,'agency_id'=>$data['agency_id'],'user_status'=>1));//get the nof of trainers in franchise
        $student_invoice_amount=0;
        $student_collected_amount=0;
        $mindtronix_invoice_amount=0;
        $mindtronix_collected_amount=0;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$agency_info,'no_of_student'=>!empty($no_of_students[0]['no_of_students'])?$no_of_students[0]['no_of_students']:'0','no_of_schools'=>!empty($no_of_schools[0]['no_of_schools'])?$no_of_schools[0]['no_of_schools']:'0','no_of_trainers'=>!empty(count($no_of_trainers))?count($no_of_trainers):'0','student_invoice_amount'=>$student_invoice_amount,'student_collected_amount'=>$student_collected_amount,'mindtronix_invoice_amount'=>$mindtronix_invoice_amount,'mindtronix_collected_amount'=>$mindtronix_collected_amount,'statistics_graph'=>array());
        $this->response($result, REST_Controller::HTTP_OK);
    }

}

