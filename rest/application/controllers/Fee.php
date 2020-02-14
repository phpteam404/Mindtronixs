<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Fee extends REST_Controller
{
    public $session_user_id=NULL;
    public $session_user_info=NULL;
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('Validation_model');
        //$this->session_user_id=!empty($this->session->userdata('session_user_id_acting'))?($this->session->userdata('session_user_id_acting')):($this->session->userdata('session_user_id'));
        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
        if(!in_array($this->session_user_info->user_role_id,array(1,2,3,4))){
            $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
       
    }

    public function addFeeUpdate_post()
    {
        $data = $this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        //print_r($data);exit;
        $this->form_validator->add_rules('name', array('required' => $this->lang->line('fee_name')));
        $this->form_validator->add_rules('description', array('required' => $this->lang->line('fee_desc')));
        $this->form_validator->add_rules('price', array('required' => $this->lang->line('fee_price')));
        
        // $this->form_validator->add_rules('name', array('required'=> $this->lang->line('fee_name')));
        // $this->form_validator->add_rules('description', array('required'=> $this->lang->line('fee_desc')));
        // $this->form_validator->add_rules('price', array('requied' => $this->lang->line('fee_price')));
        $validated = $this->form_validator->validate($data);
        //print_r($validated);exit;
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(isset($data['fee_id'])) {
            $data['fee_id'] = $data['fee_id'];
        }
        /*adding record*/
        $add =array(
             'name' => $data['name'],
             'description' => $data['description'],
             'price'=>$data['price'],
             'offer_details' =>isset($data['offer_details'])?$data['offer_details']:'',
             'discount' =>isset($data['discount'])?$data['discount']:'',
             'discount_details' =>isset($data['discount_details'])?$data['discount_details']:'',
             'offer_type' =>isset($data['offer_type'])?$data['offer_type']:'',
             'status'=>isset($data['status'])?$data['status']:'1'
        );
        if(isset($data['id']) && $data['id']>0){
            // print_r($data);exit;
            $add['updated_by'] =  $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('fee_master',$add,array('id'=>$data['id']));
            if($update>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('fee_update'),'data' =>'2');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'4');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
        else{
            $add['created_by'] = $this->session_user_id;
            $add['created_on'] = currentDate();
            $addFeeData = $this->Fee_model->addFee($add);
            //echo ''.$this->db->last_query(); exit;
            if($addFeeData >0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('fee_added'), 'data' => '1');
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

    public function feeStructure_get() //this function is used to get fee structure information
    {
        $data = $this->input->get();
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $result = $this->Fee_model->listFeeMasterInfo($data);
        // echo ''.$this->db->last_query(); exit;
        //print_r($result);exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>$result['data'],'total_records' =>$result['total_records']);
        $this->response($result, REST_Controller::HTTP_OK);

    }
    
}