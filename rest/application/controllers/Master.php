<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Master extends REST_Controller
{
   
    public function __construct()
    {
        parent::__construct();
        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_parent_id=$getLoggedUserId[0]['parent_user_id'];
        $this->session_user_id_acting=$getLoggedUserId[0]['child_user_id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
        $this->load->model('Master_model');
        $this->load->model('User_model');
        
    }

    public function addMasterChild_post()
    {
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('master_id', array('required' => $this->lang->line('master_id_req')));
        $this->form_validator->add_rules('child_name', array('required' => $this->lang->line('child_name_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $child_key=str_replace(' ','_',strtolower(rtrim(ltrim($data['child_name']))));
        $master_child_data=array(
            'master_id'=>$data['master_id'],
            'child_name'=>$data['child_name'],
            'child_key'=> $child_key,
            'description'=>!empty($data['description'])?$data['description']:'',
            'status'=>isset($data['status'])?$data['status']:'1'   
        );
        if(isset($data['child_id']) && $data['child_id']){
            $check_existance=$this->Master_model->check_not_in('master_child',array('child_name'=>$data['child_name']),array('id'=>array($data['child_id'])));
            if(count($check_existance)>0){
                $result = array('status'=>FALSE,'error'=>$this->lang->line('child_name_duplicate'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            $master_child_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
            $master_child_data['updated_date_time']=currentDate();
            $is_update=$this->User_model->update_data('master_child',$master_child_data,array('id'=>$data ['child_id']));
            //print_r($is_update);exit;
            if($is_update>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('master_update'), 'data'=>array  ('data'=>$inserted_id));
                   $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
       }
       else{
            $check_existance=$this->User_model->check_record('master_child',array('child_name'=>$data['child_name'],'master_id'=>$data['master_id']),array());
            if(count($check_existance)>0){
                $result = array('status'=>FALSE,'error'=>$this->lang->line('child_name_duplicate'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            $master_child_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
            $master_child_data['created_date_time']=currentDate();
            $inserted_id=$this->User_model->insertdata('master_child',$master_child_data);
            if($inserted_id>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('master_add'), 'data'=>array ('data'=>$inserted_id));
               $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
       }

    }

    public function getMsaterData_get()
    {
        $data=$this->input->get();
        $this->form_validator->add_rules('master_key', array('required'=> $this->lang->line('master_key_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $result = $this->Master_model->getMaster($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$result);
        $this->response($result, REST_Controller::HTTP_OK);
         
    }
    public function masterList_get(){
         $data=$this->input->get();
        $master_list=$this->User_model->check_record_selected('id as master_id, master_name,master_key,status','master',!empty($data)?$data:'');
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$master_list);
        $this->response($result, REST_Controller::HTTP_OK);
            
    }
    



}