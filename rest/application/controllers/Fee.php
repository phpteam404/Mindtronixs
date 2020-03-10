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
        $this->form_validator->add_rules('amount', array('required' => $this->lang->line('amount_price')));
        $this->form_validator->add_rules('term', array('required' => $this->lang->line('term_req')));
        
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $add =array(
             'name' => $data['name'],
             'amount'=>$data['amount'],
             'term' =>isset($data['term'])?$data['term']:'',
             'discount' =>isset($data['discount'])?$data['discount']:'',
             'discount_details' =>isset($data['discount_details'])?$data['discount_details']:'',
             'status'=>isset($data['status'])?$data['status']:'1'
        );
        if(isset($data['fee_master_id']) && $data['fee_master_id']>0){
          
            $add['updated_by'] =  $this->session_user_id;
            $add['updated_on'] = currentDate();
            $update = $this->User_model->update_data('fee_master',$add,array('id'=>$data['fee_master_id']));
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
            // $check_fee_name_exits=$this->User_model->check_record('fee_master', array('name'=>ltrim(rtrim($data['name']))));
            // if(count($check_fee_name_exits)>0){
            //     $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('duplicate_fee_name')),'data'=>'4');
            //     $this->response($result, REST_Controller::HTTP_OK);
            // }
            // echo $this->db->last_query();exit;
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
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // $data = tableOptions($data);//print_r($data);exit
        $result = $this->Fee_model->listFeeMasterInfo($data);//echo $this->db->last_query();exit;
// print_r($result);exit;
        foreach($result['data'] as $k => $v){
            if(isset($data['fee_master_id']) && $data['fee_master_id'] > 0){
                //Getting Objects for dropdown When One record is needed.
                $result['data'][$k]['status']=getStatusObj($v['status']);
                $result['data'][$k]['term']=getObjOnId($v['term'],!empty($v['term'])?true:false);
            }else{
                //Getting Lable for List when List is needed.
                $result['data'][$k]['status']=getStatusText($v['status']);
                $result['data'][$k]['term']=getObjOnId($v['term'],!empty($v['term'])?false:true);
            }
        }
        
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data' =>array('data'=>$result['data'],'total_records' =>$result['total_records'],'table_headers'=>getTableHeads('fee_structure_list')));
        $this->response($result, REST_Controller::HTTP_OK);

    }
    public function feeStructureDropdown_get()
    {   
        $data=$this->input->get();
        $drop_down_data=array();
        if(isset($data['dropdown']) && $data['dropdown']!==''){
            $fee_dropdown_data=$this->User_model->check_record_selected('concat(fm.name,"-",fm.id) as fee_master','fee_master fm',array('status'=>1));//echo $this->db->last_query();exit;
            foreach($fee_dropdown_data as $k=>$v){
                $fee_dropdown_data[$k]=getObjOnId($v['fee_master'],!empty($v['fee_master'])?true:false);
            }
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$fee_dropdown_data));

        }
        else{
            if(!empty($this->session_user_info->franchise_id)){
                $fee_structure=$this->Fee_model->getfeeStructureDropdown(array('franchise_id'=>$this->session_user_info->franchise_id));//echo $this->db->last_query();exit;
                foreach($fee_structure as $k=>$v){
                    $drop_down_data[$k]=getObjOnId($v['fee_master'],!empty($v['fee_master'])?true:false);
                }
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$drop_down_data));
            }
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }
}


