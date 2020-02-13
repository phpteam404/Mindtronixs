<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Agency extends REST_Controller
{
    public $user_id = 0 ;
    public $session_user_id=NULL;
    public $session_user_info=NULL;
    public function __construct()
    {
        parent::__construct();
        if(isset($_SERVER['HTTP_USER'])){
            $this->user_id = $_SERVER['HTTP_USER'];
        }

        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
        //print_r($this->session_user_info->id_user); exit;
       
    }

    public function PostAgency_post() //this function is to add/update agency information.
    {
        $data = $this->input->post();
        //print_r($data); exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('agency_name_req')));
        $this->form_validator->add_rules('address', array('required'=>$this->lang->line('agency_add_req')));
        $this->form_validator->add_rules('manager', array('required' =>$this->lang->line('agency_manager')));
        $this->form_validator->add_rules('email',array('required'=>$this->lang->line('agency_email')));
        $this->form_validator->add_rules('phone',array('required'=>$this->lang->line('agency_phone')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        if(isset($data['created_by'])) {
            $data['created_by'] = $data['created_by'];
            if($data['created_by']!=$this->session_user_id){
                $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }

        $add = array(
            'name' => $data['name'],
            'address' =>$data['address'],
            'manager'  =>$data['manager'],
            'landmark' => isset($data['landmark'])?$data['landmark']:'',
            'email' => $data['email'],
            'phone' =>$data['phone'],
            'status' =>isset($data['status'])?$data['status']:1,
    
        );
        if(isset($data['agency_id']) && $data['agency_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('agency',$add,array('agency_id'=>$data['agency_id']));
            // echo ''.$this->db->last_query(); exit;
            //print_r($update); exit;
            if(isset($data['fee_master_id']) && count(explode(',',$data['fee_master_id']))>0 && $data['fee_master_id']!="")
            {
                //print_r($data['fee_master_id']);
                $fee_master_id_exp=explode(',',$data['fee_master_id']);
                //print_r($fee_master_id_exp); 
                $fee_master_id=array();
                foreach($fee_master_id_exp as $k=>$v)
                {
                    $checkAlreadyExists =$this->User_model->check_record('agency_fee',array('agency_id'=>$data['agency_id'],
                    'fee_master_id'=>$v));
                    //echo ''.$this->db->last_query(); exit;
                    if(count($checkAlreadyExists)==0)
                    {
                        $updateFee = array('agency_id' =>$data['agency_id'],'fee_master_id' => $v,
                        'status'=>1,'updated_by'=>$this->session_user_id,'updated_on' =>currentDate());
                       $this->User_model->insert_data('agency_fee',$updateFee);
                    }
                    $fee_master_id[]=$v;
                }
                $data['fee_master_id']=implode(',',$fee_master_id);
            }
            // new 
            if(isset($data['fee_master_id']) && count(explode(',',$data['fee_master_id']))>0 && $data['fee_master_id']!="")
            {
                $fee_master_id_exp=explode(',',$data['fee_master_id']); 
                $fee_master_id=array();
                foreach($fee_master_id_exp as $k=>$v)
                {
                    //print_r($v); 
                    $updateStatus = "update agency_fee SET status=0,updated_on=CURDATE(),updated_by=$this->session_user_id where agency_id='".$data['agency_id']."' and fee_master_id  in ('".$v."')"; 
                    $query=$this->db->query($updateStatus); 
                    $fee_master_id[]=$v;
                }
                $data['fee_master_id']=implode(',',$fee_master_id);
            }
            if($update>0){
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
            $addData = $this->User_model->insert_data('agency',$add);
            //echo ''.$this->db->last_query(); exit;
            //print_r($addData); exit;

            if($addData)
            {
                if(isset($data['fee_master_id']) && count(explode(',',$data['fee_master_id']))>0 && $data['fee_master_id']!="")
                {
                    //print_r($data['fee_master_id']);
                    $fee_master_id_exp=explode(',',$data['fee_master_id']);
                    //print_r($fee_master_id_exp); 
                    $fee_master_id=array();
                    foreach($fee_master_id_exp as $k=>$v)
                    {
                        $agencyFee = array('agency_id'=>$addData,'fee_master_id'=>$v,'created_by'=>$this->session_user_id,'created_on'=>currentDate());
                        $this->User_model->insert_data('agency_fee',$agencyFee);
                        
                        $fee_master_id[]=$v;
                    }
                    $data['fee_master_id']=implode(',',$fee_master_id);
                }

            }
            if($addData >0){
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

    public function franchiseList_get() //this function is used to get franchise(agencies) list information
    {
        $data = $this->input->get();
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $result = $this->Agency_model->listfranchise($data);
        //echo ''.$this->db->last_query(); exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);
    }


    public function addSchool_post() //this function is used to add/update schools information
    {
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('name', array('required'=>$this->lang->line('school_name')));
        $this->form_validator->add_rules('address', array('required'=>$this->lang->line('school_add')));
        $this->form_validator->add_rules('email',array('required' =>$this->lang->line('school_email')));
        $this->form_validator->add_rules('phone',array('required' =>$this->lang->line('school_phone')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        if(isset($data['created_by'])) {
            $data['created_by'] = $data['created_by'];
            if($data['created_by']!=$this->session_user_id){
                $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }

        $add = array(
            'name' => $data['name'],
            'address' =>$data['address'],
            'email'  =>$data['email'],
            'phone' => $data['phone'],
            'status' =>isset($data['status'])?$data['status']:1,
        );
        if(isset($data['school_id']) && $data['school_id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('school_master',$add,array('school_id'=>$data['school_id']));
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

    public function schoolsList_get() //this function is used to get schools list information
    {
        $data = $this->input->get();
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $result = $this->Agency_model->listSchools($data);
        //echo ''.$this->db->last_query(); exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);
    }

}

