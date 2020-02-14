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
    {   //this function is to add/update agency information.
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
        $this->form_validator->add_rules('primary_contact',array('required'=>$this->lang->line('agency_phone_primary')));
        $this->form_validator->add_rules('pincode',array('required'=>$this->lang->line('postal_code_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $add = array(
            'name' => $data['name'],
            'manager'  =>$data['manager'],
            'address' =>$data['address'],
            'email' => $data['email'],
            'primary_contact' =>$data['primary_contact'],
            'pincode' =>$data['pincode']
        );
        if(isset($data['landmark']))
            $add['landmark'] = $data['landmark'];
        if(isset($data['city']))
            $add['city'] = $data['city'];
        if(isset($data['alternative_contact']))
            $add['alternative_contact'] = $data['alternative_contact'];
        if(isset($data['status']))
            $add['status'] = $data['status'];

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
    {//This Function will Create Agency fee records.
        if(isset($data['fee_master_id']) && count(explode(',',$data['fee_master_id']))>0){
            $fee_master_id_exp=explode(',',$data['fee_master_id']);
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
    {//this function is used to get franchise(agencies) list information
        $data = $this->input->get();
        // $validated = $this->form_validator->validate($data);
        // if($validated != 1){
        //     $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
        //     $this->response($result, REST_Controller::HTTP_OK);
        // }
        
        $result = $this->Agency_model->listfranchise($data);

        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
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
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        $add = array(
            'name' => $data['name'],
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
    {//this function is used to get schools list information
        $data = $this->input->get();
        // $validated = $this->form_validator->validate($data);
        // if($validated != 1){
        //     $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
        //     $this->response($result, REST_Controller::HTTP_OK);
        // }
        $result = $this->Agency_model->listSchools($data);
        //echo ''.$this->db->last_query(); exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);
    }

}

