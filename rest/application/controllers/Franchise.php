<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Franchise extends REST_Controller
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

    public function franchiseAdd_post()
    {   
        //this function is to add/update franchise information.
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('franchise_name_req')));
        $this->form_validator->add_rules('franchise_code', array('required' =>$this->lang->line('franchisecode_req')));
        $this->form_validator->add_rules('email',array('required'=>$this->lang->line('franchise_email')));
        $this->form_validator->add_rules('owner_name',array('required'=>$this->lang->line('owner_name_req')));
        $this->form_validator->add_rules('primary_contact',array('required'=>$this->lang->line('franchise_phone_primary')));
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
            'franchise_contacts'=>isset($data['franchise_contacts'])?$data['franchise_contacts']:''
        );
        // print_r($add);exit;
        if(isset($data['franchise_id']) && $data['franchise_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('franchise',$add,array('id'=>$data['franchise_id']));
            $Insert = true;
            if($update > 0){
                $this->User_model->delete_data('franchise_fee',array('franchise_id' => $data['franchise_id']));
                $Insert = $this->createFeeMaster($data);
            }
            if($update > 0 && $Insert){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('franchise_update'),'data' =>'2');
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
            $addData = $this->User_model->insertdata('franchise',$add);
            $Insert = true;
            if($addData){
                $data['franchise_id'] = $addData;
                $Insert = $this->createFeeMaster($data);
            }
            if($addData > 0 && $Insert){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('franchise_create'), 'data' =>$addData);
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
        //This Function will Create franchise fee records.
        if(isset($data['fee_master_id'])){
            // if((explode(',',$data['fee_master_id']))>0)
            // $fee_master_id_exp=explode(',',$data['fee_master_id']);
            $fee_master_id_exp =json_decode($data['fee_master_id']);
            $insert_batch_array = array();
            foreach($fee_master_id_exp as $k=>$v){
                $insert_batch_array[$k] = array(
                    'franchise_id'=>$data['franchise_id'],
                    'fee_master_id'=>$v,
                    'created_by'=>$this->session_user_id,
                    'created_on'=>currentDate()
                );
            }
            if($this->User_model->insertbatch('franchise_fee',$insert_batch_array) > 0)
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
        $result = $this->Franchise_model->listfranchise($data);//echo $this->db->last_query();exit;
        $franchise_list=$result['data'];
        foreach($franchise_list as $f=>$l){ 
            if(!empty($l['franchise_contacts']) && !empty($data['franchise_id'])){
                $franchise_list[$f]['franchise_contacts']=json_decode($l['franchise_contacts']);
            }
            if(!empty($franchise_list[$f]['franchise_contacts'])){
                foreach($franchise_list[$f]['franchise_contacts'] as $k=>$v){
                    $franchise_list[$f]['franchise_contact_list'][$k]['name']=$v->name;
                    $franchise_list[$f]['franchise_contact_list'][$k]['contact_phone']=$v->contact_phone;
                    $franchise_list[$f]['franchise_contact_list'][$k]['contact_title']=$v->contact_title;
                }              
            }    
            if(!empty($data['franchise_id'])){
                $franchise_list[$f]['city']=getObjOnId($l['city'],!empty($l['city'])?true:false);
                $franchise_list[$f]['state']=getObjOnId($l['state'],!empty($l['state'])?true:false);
                $franchise_list[$f]['country']=getObjOnId($l['country'],!empty($l['country'])?true:false);
                $franchise_list[$f]['status']=getStatusObj($l['status']);//Getting Objects for dropdown When One record is needed.
            }
            else{
                $franchise_list[$f]['city']=getObjOnId($l['city'],false);
                $franchise_list[$f]['status']=getStatusText($l['status']);//Getting Lable for List when List is needed.

            } 
            unset($franchise_list[$f]['franchise_contacts']);
            $franchise_fee=$this->User_model->check_record('franchise_fee',array('franchise_id'=>$franchise_list[$f]['franchise_id'],'status'=>1));
           foreach($franchise_fee as $a=>$b){
                $fee_details=$this->User_model->check_record('fee_master',array('id'=>$b['fee_master_id']));
                if(!empty($fee_details) && !empty($data['franchise_id'])){
                    $franchise_list[$f]['fee_details'][$a]['fee_title']=$fee_details[0]['name'];
                    $franchise_list[$f]['fee_details'][$a]['fee_amount']=$fee_details[0]['amount'];
                    $franchise_list[$f]['fee_details'][$a]['discount']=$fee_details[0]['discount'];
                    $term=$this->User_model->check_record_selected('child_name as term','master_child',array('id'=>$fee_details[0]['term']));
                    $franchise_list[$f]['fee_details'][$a]['term']=$term[0]['term'];

                }
                
           }  
        }

        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$franchise_list,'total_records' =>$result['total_records'],'table_headers'=>getTableHeads('franchilse_list')));
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function addSchool_post() 
    {//this function is used to add/update schools information
        $data = $this->input->post();
        // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $phonennodRules   = array(
            'required'=> $this->lang->line('phone_num_req'),
            'min_len-10' => $this->lang->line('phone_num_min_len'),
            'max_len-10' => $this->lang->line('phone_num_max_len'),
        );
        
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('school_name')));
        $this->form_validator->add_rules('email',array('required' =>$this->lang->line('school_email')));
        $this->form_validator->add_rules('phone', $phonennodRules);
        $this->form_validator->add_rules('state',array('required' =>$this->lang->line('state_req')));
        $this->form_validator->add_rules('city',array('required' =>$this->lang->line('city_req')));
        $this->form_validator->add_rules('code',array('required' =>$this->lang->line('school_code_req')));
        $this->form_validator->add_rules('franchise_id',array('required' =>$this->lang->line('franchise_id_req')));
        $validated = $this->form_validator->validate($data);
        // print_r($validated);exit;
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'10');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $add = array(
            'name' => $data['name'],
            'franchise_id'=>$data['franchise_id'],
            'address' =>$data['address'],
            'email'  =>$data['email'],
            'phone' => $data['phone'],
            'school_code'=>$data['code'],
            'contact_person'=>$data['contact_person'],
            'state'=>$data['state'],
            'city'=>$data['city'],
            'pincode'=>$data['pincode']
        );
        if(isset($data['school_id']) && $data['school_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('school_master',$add,array('id'=>$data['school_id']));
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
            $inser_id = $this->Franchise_model->addSchool($add);
            // echo ''.$this->db->last_query(); exit;
            if($inser_id >0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('school_create'), 'data' => $inser_id);
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
        $result = $this->Franchise_model->listSchools($data);//echo $this->db->last_query();exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$result['data'],'total_records' =>$result['total_records'],'table_headers'=>getTableHeads('school_mngmt_list')));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public  function franchiseInfo_get()//this function used to get franchise information
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('franchise_id',array('required' =>$this->lang->line('franchise_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $franchise_info=$this->Franchise_model->getfranchiseInfo($data);//this model used for get the franchise information
        foreach($franchise_info as $k=>$v){
            $franchise_info[$k]['franchise_contacts']=json_decode($v['franchise_contacts']);
            if(!empty($franchise_info[$k]['franchise_contacts'])){
                foreach($franchise_info[$k]['franchise_contacts'] as $k1=>$v1){//this loop for get contacts of the franchise
                    $franchise_info[$k]['franchise_contacts_information'][$k1]['contact_title']=$v1->contact_title;
                    $franchise_info[$k]['franchise_contacts_information'][$k1]['contact_name']=$v1->name;
                    $franchise_info[$k]['franchise_contacts_information'][$k1]['contact_phone']=$v1->contact_phone;
                    $franchise_info[$k]['franchise_contacts_information'][$k1]['contact_eamil']=$v1->contact_eamil;
                } 
            }
            if(!empty($Franchise_info[$k]['fee_master_id'])){
                $feemaster_ids=explode(",",$Franchise_info[$k]['fee_master_id']);
                foreach($feemaster_ids as $k2 =>$v2){//this loop for get fee details of franchise
                    $get_fee_data=$this->Franchise_model->getFeeData(array('fee_master_id'=>$v2));//thsi model used for get fee data of franchise
                    if(!empty($get_fee_data)){
                        $franchise_info[$k]['fee_detalis'][$k2]=$get_fee_data[0];
                        unset($get_fee_data);
                    }

                }
            }
            $franchise_info[$k]['franchise_contacts_information']= !empty($franchise_info[$k]['franchise_contacts_information'])?$franchise_info[$k]['franchise_contacts_information']:array();
            $franchise_info[$k]['fee_detalis']= !empty($franchise_info[$k]['fee_detalis'])?$franchise_info[$k]['fee_detalis']:array();
            
        }
        unset($franchise_info[$k]['franchise_contacts']);
        $no_of_students = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_students FROM student where school_id!=0 and  franchise_id = '.$data['franchise_id']);//get the no of students in the franchise
        $no_of_schools = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_schools FROM school_master where status!=0 and  franchise_id = '.$data['franchise_id']);//get the no of schools in the franchise
        $no_of_trainers=$this->User_model->check_record('user',array('user_role_id'=>3,'franchise_id'=>$data['franchise_id'],'user_status'=>1));//get the nof of trainers in franchise
        $student_invoice_amount=0;
        $student_collected_amount=0;
        $mindtronix_invoice_amount=0;
        $mindtronix_collected_amount=0;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$franchise_info,'no_of_student'=>!empty($no_of_students[0]['no_of_students'])?$no_of_students[0]['no_of_students']:'0','no_of_schools'=>!empty($no_of_schools[0]['no_of_schools'])?$no_of_schools[0]['no_of_schools']:'0','no_of_trainers'=>!empty(count($no_of_trainers))?count($no_of_trainers):'0','student_invoice_amount'=>$student_invoice_amount,'student_collected_amount'=>$student_collected_amount,'mindtronix_invoice_amount'=>$mindtronix_invoice_amount,'mindtronix_collected_amount'=>$mindtronix_collected_amount,'statistics_graph'=>array()));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function schoolInfo_get()//this function used to get school information  for prepopulated data when edit the school
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('school_id',array('required' =>$this->lang->line('school_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $schoolInfo=$this->Franchise_model->getSchoolInfo($data);//this model is get the school information for edit service in schoool
        foreach($schoolInfo as $k=>$v){
            // $schoolInfo[$k]['status']=getStatusObj($v['status']);
            $schoolInfo[$k]['city']=getObjOnId($v['city'],true);
            $schoolInfo[$k]['state']=getObjOnId($v['state'],true);
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$schoolInfo));
        $this->response($result, REST_Controller::HTTP_OK);

    }

}

