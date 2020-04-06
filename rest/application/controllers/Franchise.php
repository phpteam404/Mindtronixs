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
        // print_r(json_encode($data));exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        //  print_r($data);exit;
        // var_dump($data);exit;
        // $this->User_model
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('franchise_name_req')));
        $this->form_validator->add_rules('code', array('required' =>$this->lang->line('franchisecode_req')));
        $this->form_validator->add_rules('email',array('required'=>$this->lang->line('franchise_email')));
        $this->form_validator->add_rules('contact_person',array('required'=>$this->lang->line('owner_name_req')));
        $this->form_validator->add_rules('phone',array('required'=>$this->lang->line('franchise_phone_primary')));
        $this->form_validator->add_rules('country',array('required'=>$this->lang->line('country_req')));
        $this->form_validator->add_rules('state',array('required'=>$this->lang->line('state_req')));
        $this->form_validator->add_rules('city',array('required'=>$this->lang->line('city_req')));

        if(empty($data['franchise_id'])){
            $this->form_validator->add_rules('fee_master_id',array('required'=>$this->lang->line('fee_master_id_req')));
            $this->form_validator->add_rules('franchise_contacts',array('required'=>$this->lang->line('franchise_contacts_req')));

        }
        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // if(!empty($data['franchise_contacts']){
        // }
        // print_r(explode(",",$data['fee_master_id']));exit;

        $add = array(
            'name' => !empty($data['name'])?$data['name']:'',
            'franchise_code'  => !empty($data['code'])?$data['code']:'',
            'website_address' => !empty($data['website_address'])?$data['website_address']:'',
            'owner_name'=>!empty($data['contact_person'])?$data['contact_person']:'',
            'email' => !empty($data['email'])?$data['email']:'',
            'primary_contact' =>!empty($data['phone'])?$data['phone']:'',
            'pincode' =>!empty($data['pincode'])?$data['pincode']:'',
            'address'=>!empty($data['address'])?$data['address']:'',
            'country'=>!empty($data['country'])?$data['country']:'',
            'state'=>!empty($data['state'])?$data['state']:'',
            'landmark'=>!empty($data['landmark'])?$data['landmark']:'',
            'status'=>isset($data['status'])?$data['status']:1,
            'city'=>isset($data['city'])?$data['city']:'',
            // 'franchise_contacts'=>isset($data['franchise_contacts'])?$data['franchise_contacts']:''
        );

        // print_r($add);exit;
        if(isset($data['franchise_id']) && $data['franchise_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();

            $update = $this->User_model->update_data('franchise',$add,array('id'=>$data['franchise_id']));
            $Insert = true;
            // if($update > 0){
            //     //$this->User_model->delete_data('franchise_fee',array('franchise_id' => $data['franchise_id']));
            //     //$Insert = $this->createFeeMaster($data);
            // }
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

            // $data['franchise_contacts']=json_encode($data['franchise_contacts']);
            $add['created_on'] = currentDate();
            $add['created_by'] = $this->session_user_id;
            // $add['franchise_contacts'] = isset($data['franchise_contacts'])?$data['franchise_contacts']:'';
            
            $addData = $this->User_model->insertdata('franchise',$add);
            foreach($data['franchise_contacts'] as $k=>$v){
                $franchise_contacts[$k]['contact_name']=$v['contact_name'];
                $franchise_contacts[$k]['contact_title']=$v['contact_title'];
                $franchise_contacts[$k]['contact_email']=$v['contact_email'];
                $franchise_contacts[$k]['contact_number']=$v['contact_number'];
                $franchise_contacts[$k]['created_by']=$this->session_user_id;
                $franchise_contacts[$k]['franchise_id']=$addData;
                $franchise_contacts[$k]['created_on']=currentDate();
                $franchise_contacts[$k]['status']=1;
            }
            $this->User_model->insertbatch('franchise_contacts',$franchise_contacts);
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
            $fee_master_id_exp=explode(',',$data['fee_master_id']);
            // $fee_master_id_exp =json_decode($data['fee_master_id']);
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
            // if(!empty($l['franchise_contacts']) && !empty($data['franchise_id'])){
            //     $franchise_list[$f]['franchise_contacts']=json_decode($l['franchise_contacts']);
            // }
            // if(!empty($franchise_list[$f]['franchise_contacts'])){
            //     foreach($franchise_list[$f]['franchise_contacts'] as $k=>$v){
            //         $franchise_list[$f]['franchise_contact_list'][$k]['contact_name']=$v->contact_name;
            //         $franchise_list[$f]['franchise_contact_list'][$k]['contact_phone']=$v->contact_phone;
            //         $franchise_list[$f]['franchise_contact_list'][$k]['contact_title']=$v->contact_title;
            //         $franchise_list[$f]['franchise_contact_list'][$k]['contact_email']=$v->contact_email;
            //     }              
            // }   

            if(!empty($data['franchise_id'])){
                // print_r($l);exit;
                $franchise_list[$f]['city']=getObjOnId($l['city'],!empty($l['city'])?true:false);
                $franchise_list[$f]['state']=getObjOnId($l['state'],!empty($l['state'])?true:false);
                $franchise_list[$f]['country']=getObjOnId($l['country'],!empty($l['country'])?true:false);
                $franchise_list[$f]['status']=getStatusObj($l['status']);//Getting Objects for dropdown When One record is needed.
                $frachise_contacts=$this->Franchise_model->getFranchiseContacts(array('franchise_id'=>$data['franchise_id']));
                $franchise_list[$f]['franchise_contact_list']= $frachise_contacts;
            }
            else{
                // print_r($l['city']);exit;
                // $franchise_list[$f]['city']=getObjOnId($l['city'],!empty($l['city'])?true:false);
                $franchise_list[$f]['status']=getStatusText($l['status']);//Getting Lable for List when List is needed.

            } 
            unset($franchise_list[$f]['franchise_contacts']);
            $franchise_fee=$this->User_model->check_record('franchise_fee',array('franchise_id'=>$franchise_list[$f]['franchise_id'],'status'=>1));
           foreach($franchise_fee as $a=>$b){
                $fee_details=$this->User_model->check_record('fee_master',array('id'=>$b['fee_master_id']));
                if(!empty($fee_details) && !empty($data['franchise_id'])){
                    // print_r
                    $franchise_list[$f]['fee_details'][$a]['fee_title']=$fee_details[0]['name'];
                    $franchise_list[$f]['fee_details'][$a]['fee_amount']=$fee_details[0]['amount'];
                    $franchise_list[$f]['fee_details'][$a]['discount']=$fee_details[0]['discount'];
                    $term=$this->User_model->check_record_selected('child_name as term','master_child',array('id'=>$fee_details[0]['term']));
                    $franchise_list[$f]['fee_details'][$a]['term']=$term[0]['term'];

                }
                
           }  
        }
        if(!empty($data['franchise_id'])){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$franchise_list));
        }
        else{
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$franchise_list,'total_records' =>$result['total_records'],'table_headers'=>getTableHeads('franchilse_list')));
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function addSchool_post() 
    {//this function is used to add/update schools information
        $data = $this->input->post();
        //  print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $phonennodRules   = array(
            'required'=> $this->lang->line('phone_num_req'),
            'min_len-10' => $this->lang->line('phone_num_min_len'),
        );
        
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('school_name')));
        $this->form_validator->add_rules('email',array('required' =>$this->lang->line('school_email')));
        $this->form_validator->add_rules('phone', $phonennodRules);
        $this->form_validator->add_rules('state',array('required' =>$this->lang->line('state_req')));
        $this->form_validator->add_rules('city',array('required' =>$this->lang->line('city_req')));
        $this->form_validator->add_rules('code',array('required' =>$this->lang->line('school_code_req')));
        // $this->form_validator->add_rules('franchise_id',array('required' =>$this->lang->line('franchise_id_req')));
        $validated = $this->form_validator->validate($data);
        // print_r($validated);exit;
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'10');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        if($this->session_user_info->user_role_id==2){
            $data['franchise_id']=$this->session_user_info->franchise_id;
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
                $new_password = generatePassword(8);
                $School_admin = array(
                    'user_role_id' => 10,
                    'franchise_id' => $data['franchise_id'],
                    'first_name' => $data['contact_person'],
                    'email' => $data['email'],
                    'phone_no' => $data['phone'],
                    'password' => md5($new_password),
                    'created_by' => $this->session_user_id,
                    'created_on' => currentDate()
                );
                $user_id=$this->User_model->insert_data('user',$School_admin);
                $this->User_model->update_data('school_master',array('user_id'=>$user_id),array('id'=>$inser_id));
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
        if($this->session_user_info->user_role_id==2){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
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
        $franchise_info=$this->Franchise_model->getfranchiseInfo($data);//echo $this->db->last_query();exit;//this model used for get the franchise information
        foreach($franchise_info as $k=>$v){
            $frachise_contacts=$this->Franchise_model->getFranchiseContacts(array('franchise_id'=>$data['franchise_id']));
            if(!empty($frachise_contacts))
            foreach($frachise_contacts as $c=>$d){
                $frachise_contacts[$c]['contact_title']=getObjOnId($d['contact_title'],!empty($d['contact_title'])?true:false);
            }
            $franchise_info[$k]['franchise_contacts_information']= $frachise_contacts;
            $fee_master_ids=$this->User_model->check_record_selected('GROUP_CONCAT(fee_master_id) as fee_master_ids','franchise_fee',array('franchise_id'=>$data['franchise_id'],'status'=>1));//echo $this->db->last_query();exit;
            // print_r($fee_master_ids[0]['fee_master_ids']);exit;
            // if(!empty($fee_master_ids[0]['fee_master_ids'])){
            //     $feemaster_ids=explode(",",$fee_master_ids[0]['fee_master_ids']);
                // print_r($fee_master_ids[0]['fee_master_ids']);exit;
                // foreach($feemaster_ids as $k2 =>$v2){//this loop for get fee details of franchise
                    //     print_r($get_fee_data);exit;
                    //     if(!empty($get_fee_data)){
                        //         unset($get_fee_data);
                        //     }
                        
                        // }
                        $get_fee_data=$this->Franchise_model->getFeeData(array('franchise_id'=>$data['franchise_id']));//echo $this->db->last_query();exit;//thsi model used for get fee data of franchise
                                $franchise_info[$k]['fee_detalis']=$get_fee_data;
                    // }
            $franchise_info[$k]['franchise_contacts_information']= !empty($franchise_info[$k]['franchise_contacts_information'])?$franchise_info[$k]['franchise_contacts_information']:array();
            $franchise_info[$k]['fee_detalis']= !empty($franchise_info[$k]['fee_detalis'])?$franchise_info[$k]['fee_detalis']:array();
            
        }
        // print_r($franchise_info);exit;
        unset($franchise_info[$k]['franchise_contacts']);
        $no_of_students = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_students FROM student where school_id!=0 and status=1 and franchise_id = '.$data['franchise_id']);//echo $this->db->last_query();//get the no of students in the franchise
        $no_of_schools = $this->User_model->custom_query('SELECT count(DISTINCT(id)) as no_of_schools FROM school_master where status=1 and   franchise_id = '.$data['franchise_id']);//echo $this->db->last_query();//get the no of schools in the franchise
        $no_of_trainers=$this->User_model->check_record('user',array('user_role_id'=>3,'franchise_id'=>$data['franchise_id'],'user_status'=>1));//echo $this->db->last_query();exit;//get the nof of trainers in franchise
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
            // print_r($v);exit;
            $schoolInfo[$k]['city']=getObjOnId($v['city'],!empty($v['city'])?true:false);
            $schoolInfo[$k]['state']=getObjOnId($v['state'],!empty($v['state'])?true:false);
            $schoolInfo[$k]['franchise_id']=getObjOnId($v['franchise_id'],!empty($v['franchise_id'])?true:false);
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$schoolInfo));
        $this->response($result, REST_Controller::HTTP_OK);

    }
    
    public function franchiseListForDropDown_get(){
        if($this->session_user_info->user_role_id==2 || $this->session_user_info->user_role_id==5 || $this->session_user_info->user_role_id==10){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        else{
            $data=null;
        }   
       $franchise= $this->Franchise_model->getFranchiseDropdown($data);//echo $this->db->last_query();exit;
       foreach($franchise as $k=>$v){
        $franchise[$k]=getObjOnId($v['franchise_id'],!empty($v['franchise_id'])?true:false);  
        }
        // $header[0]=array('label'=>'All Franchise','value'=>0);
        // $franchise=array_merge($header,$franchise);
        // print_r($header);exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$franchise));
        
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function schoolListForDropDown_get(){
        //print_r($this->session_user_info);exit;
        if($this->session_user_info->user_role_id==2){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        else{
            $data=null;
        }
        if($this->session_user_info->user_role_id==2 || $this->session_user_info->user_role_id==5){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        if($this->session_user_info->user_role_id==10){
            $school_id=$this->User_model->check_record('school_master',array('user_id'=>$this->session_user_info->user_id));
            $data['school_id']=!empty($school_id[0]['id'])?$school_id[0]['id']:0;
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        $schools= $this->Franchise_model->getschoolDropdown($data);//echo $this->db->last_query();exit;
        foreach($schools as $k=>$v){
         $schools[$k]=getObjOnId($v['schools'],!empty($v['schools'])?true:false);  
         }
        //  $header[0]=array('label'=>'All Schools','value'=>0);
        //  $schools_data=array_merge($header,$franchise);
         $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$schools));
         $this->response($result, REST_Controller::HTTP_OK);
     }
    
     public function addFranchiseFeeMaster_post(){
        $data=$this->input->post();
        // print_r($data);exit;
        if(empty($data)){
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
                $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('franchise_id',array('required' =>$this->lang->line('franchise_id_req')));
        $this->form_validator->add_rules('fee_master_id',array('required' =>$this->lang->line('fee_master_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $fee_master_id_exp=explode(',',$data['fee_master_id']);
        foreach($fee_master_id_exp as $k=>$v){
            $fee_data[$k]=array(
                'franchise_id'=>!empty($data['franchise_id'])?$data['franchise_id']:'',
                'fee_master_id'=>!empty($v)?$v:'',
                'status'=>!empty($data['status'])?$data['status']:'1',
                'created_by'=>$this->session_user_id,
                'created_on'=>currentDate()
            );

        }
        // print_r($fee_data);exit;
        $insert_id=$this->User_model->insertbatch('franchise_fee',$fee_data);
        if($insert_id>0){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('franchise_feemaster_add'),'data'=>array('data' =>$insert_id));
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);   
        }
     }

    public function addUpdateFranchiseContacts_post(){
        $data=$this->input->post();
        if(empty($data)){
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
                $this->response($result, REST_Controller::HTTP_OK);
        }
        $contact_data=array(
            'contact_title'=>$data['contact_title'],
            'contact_name'=>$data['contact_name'],
            'contact_number'=>$data['contact_number'],
            'contact_email'=>$data['contact_email'],
            'contact_email'=>$data['contact_email'],
            'franchise_id'=>$data['franchise_id']
        );
        if(!empty($data['franchise_contact_id'])){
            
            $contact_data['updated_on']=currentDate();
            $contact_data['updated_by']=$this->session_user_id;
            $is_update=$this->User_model->update_data('franchise_contacts',$contact_data,array('id'=>$data['franchise_contact_id']));//echo $this->db->last_query();exit;
            if(isset($is_update)){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('franchise_contacts_update'),'data'=>array('data' =>$data['franchise_contact_id']));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
        else{

            $contact_data['created_on']=currentDate();
            $contact_data['created_by']=$this->session_user_id;
            // print_r($contact_data);exit;
            $is_insert=$this->User_model->insert_data('franchise_contacts',$contact_data);
            if($is_insert >0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('franchise_contacts_created'),'data'=>array('data' =>$is_insert));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
                $this->response($result, REST_Controller::HTTP_OK);
            }

        }
    }
    public function updateFranchiseStatus_post()    {
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'2');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        //print_r($data);exit;
        $this->form_validator->add_rules('franchise_fee_id', array('required' => $this->lang->line('franchise_feeid_req')));
        $this->form_validator->add_rules('status', array('required' => $this->lang->line('franchise_fee_status')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $update_frachise_fee_status=$this->User_model->update_data('franchise_fee',array('status'=>$data['status']),array('id'=>$data['franchise_fee_id']));
        if(isset($update_frachise_fee_status)){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('frachise_status_update'),'data'=>array('data' =>$data['franchise_fee_id']));
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }

}

