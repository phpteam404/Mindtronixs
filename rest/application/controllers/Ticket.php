<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Ticket extends REST_Controller
{
    public $order_data = array();
    public $cnt =1;

    public $user_id = 0 ;
    public $session_user_id=NULL;
    public $session_user_parent_id=NULL;
    public $session_user_id_acting=NULL;
    public $session_user_info=NULL;

    public function __construct()
    {
        parent::__construct();
        //$this->load->model('Ticket_model');
        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_parent_id=$getLoggedUserId[0]['parent_user_id'];
        $this->session_user_id_acting=$getLoggedUserId[0]['child_user_id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
    }

    public function addTicket_post()
    {
        $data=$this->input->post();
        // print_r($_FILES);exit;
        if(!empty($_FILES)){
            $no_of_files=count($_FILES['document']['name']);
            for ($i=0; $i <$no_of_files ; $i++) { 
                //  print_r($_FILES['document']['type'][$i]);exit;
                $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png');   
            //    print_r(in_array($_FILES['document']['type'][$i],$allowed_types));exit;
                if(in_array($_FILES['document']['type'][$i],$allowed_types))
                {
                    $path='uploads/ticket/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['document']['tmp_name'][$i],
                        'image' => $_FILES['document']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        // $imageName='ticket/'.$imageName;
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'module_type'=>'1'));
                }
                else
                {
                    $result = array('status'=>FALSE,'error'=>array('document' => $this->lang->line('upload_document')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            
        }
        //  print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('description', array('required' => $this->lang->line('ticket_desc')));
        $this->form_validator->add_rules('title', array('required' => $this->lang->line('title_req_desc')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $ticket_data=array(
            'description'=>$data['description'],
            'title'=>$data['title'],
            'assigned_to'=>1,
            'ticket_rised_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'created_on'=>currentDate(),
            'status'=>1
            // 'documents'=>!empty($imageName)?$imageName:''

        );
       if(!empty($_FILES) && count($document_id)>0){

           $inserted_id= $this->User_model->insertdata('ticket',$ticket_data);
           if($inserted_id>0){
               $ticket_id="MINDTKTNO_".$inserted_id;
               $this->User_model->update_data('ticket',array('ticket_no'=>$ticket_id),array('id'=>$inserted_id));
               $ticket_chat_id=$this->User_model->insertdata('ticket_chat',array('created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','ticket_id'=>$inserted_id,'message'=>$data['description'],'created_on'=>currentDate(),'type'=>'1','from_user'=>$this->session_user_id,'to_user'=>1));
            }
            $this->User_model->update_where_in('documents',array('module_type_id'=>$ticket_chat_id),array('id'=>$document_id));
        //    if(!empty($_FILES) && $document_id){
        //        $this->User_model->insertdata('documents',array('ticket_chat_id'=>$ticket_chat_id,'document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'type'=>'chat'));
        //    }
           $result = array('status'=>TRUE, 'message' => $this->lang->line('ticket_add'), 'data'=>array('data'=>$ticket_id));
           $this->response($result, REST_Controller::HTTP_OK);   
       }
       elseif(empty($_FILES)){
            $inserted_id= $this->User_model->insertdata('ticket',$ticket_data);
            if($inserted_id>0){
            $ticket_id="MINDTKTNO_".$inserted_id;
            $this->User_model->update_data('ticket',array('ticket_no'=>$ticket_id),array('id'=>$inserted_id));
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',array('created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','ticket_id'=>$inserted_id,'message'=>$data['description'],'created_on'=>currentDate(),'type'=>'1','from_user'=>$this->session_user_id,'to_user'=>1));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('ticket_add'), 'data'=>array('data'=>$ticket_id));
           $this->response($result, REST_Controller::HTTP_OK);
         }
       }
       else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
       }
    }


    public function addTicketChat_post()
    {
        $data=$this->input->post();
        // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('ticket_id', array('required' => $this->lang->line('ticket_id_req')));
       // $this->form_validator->add_rules('message', array('required' => $this->lang->line('message_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $to_user=$this->getFromAndToUser(array('ticket_id'=>$data['ticket_id']));
        // print_r($to_user);exit;
        $chat_data=array(
            'ticket_id'=>$data['ticket_id'],
            'message'=>!empty($data['message'])?$data['message']:'',
            'created_on'=>currentDate(), 
            'created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'type'=>'1',
            'from_user'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'to_user'=>$to_user
            
        );
        if(!empty($_FILES)){
            $no_of_files=count($_FILES['document']['name']);
            for ($i=0; $i <$no_of_files ; $i++) { 
                // print_r($_FILES['document']);//
                $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png');   
              //  print_r(in_array($_FILES['document']['name'][$i],$allowed_types));exit;
                if(in_array($_FILES['document']['type'][$i],$allowed_types))
                {
                    $path='uploads/ticket/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['document']['tmp_name'][$i],
                        'image' => $_FILES['document']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        
                        // $imageName='ticket/'.$imageName;
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'module_type'=>'1'));
                }
                else
                {
                    $result = array('status'=>FALSE,'error'=>array('document' => $this->lang->line('upload_document')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            
        }
        if(!empty($_FILES) && count($document_id)>0){
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',$chat_data);
            $this->User_model->update_where_in('documents',array('module_type_id'=>$ticket_chat_id),array('id'=>$document_id));
            $this->User_model->update_data('ticket',array('last_updated_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','last_update_on'=>currentDate()),array('id'=>$data['ticket_id']));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK);

        }

        elseif(empty($_FILES)){
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',$chat_data);
            $this->User_model->update_data('ticket',array('last_updated_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','last_update_on'=>currentDate()),array('id'=>$data['ticket_id']));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array());
           $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK); 
        }
        
    }
    
    public function getTicketChat_get()
    {
        $data=$this->input->get();
        // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('ticket_id', array('required' => $this->lang->line('ticket_id_req')));
       // $this->form_validator->add_rules('message', array('required' => $this->lang->line('message_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $chatdata=$this->Ticket_model->getChat($data);
            // print_r($chatdata);exit;
        foreach($chatdata  as $k=>$v){
            if(!empty($v['documents'])){
                $chatdata[$k]['documents']=explode(",",$v['documents']); 
                foreach($chatdata[$k]['documents'] as $l=>$m){
                    $document[$k][$l]['document_name']=$m;
                    $document[$k][$l]['document_url']=DOCUMENT_PATH.'ticket/'.$m;
    
                }
                unset($chatdata[$k]['documents'][$l]);
                $chatdata[$k]['documents']=$document[$k];
            }
                
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array($chatdata));
        $this->response($result, REST_Controller::HTTP_OK);
        
    } 
    public function ticketList_get()
    {
        $data=$this->input->get();
        // print_r($data);exit;
        $data['user_role_id']=$this->session_user_info->user_role_id;
        $data['user_id']=$this->session_user_info->user_id;
        $data = tableOptions($data);
        // print_r($this->session_user_info);exit;
        if(in_array($data['user_role_id'],array('1','4','5'))){
            $ticket_list=$this->Ticket_model->getTickets($data);
        }
        else{
            $ticket_list=array();    
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data'=>$ticket_list['data'],'total_records'=>$ticket_list['total_records']));
        $this->response($result, REST_Controller::HTTP_OK);

    }
    public function getFromAndToUser($data)//this function is used to return to user
    {
        $result=$this->User_model->Check_record('ticket',array('id'=>$data['ticket_id'],'status'=>1));
        if($result[0]['ticket_rised_by']==$this->session_user_info->user_id){
            return $result[0]['assigned_to'];
        }
        if($result[0]['assigned_to']==$this->session_user_info->user_id){
            return $result[0]['ticket_rised_by'];
        }

    }
    public function ticketAssignment_post(){
        $data=$this->input->post();//print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('ticket_id', array('required' => $this->lang->line('ticket_id_req')));
        $this->form_validator->add_rules('user_id', array('required' => $this->lang->line('assigned_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(!empty($data['is_ticket_closed']) && $data['is_ticket_closed']==1){
            $this->User_model->update_data('ticket',array('assigned_to'=>$data['user_id'],'last_update_on'=>currentDate(),'last_updated_by'=>$this->session_user_info->user_id,'status'=>2),array('id'=>$data['ticket_id']));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('ticket_close'), 'data'=>array($data['ticket_id']));
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $from_user_name=$this->User_model->check_record_selected('concat(first_name," ",last_name) as from_user_name','user',array('id'=>$this->session_user_info->user_id));
            $to_user_name=$this->User_model->check_record_selected('concat(first_name," ",last_name) as to_user_name','user',array('id'=>$data['user_id']));//echo $this->db->last_query();exit;
            $is_update=$this->User_model->update_data('ticket',array('assigned_to'=>$data['user_id'],'last_update_on'=>currentDate(),'last_updated_by'=>$this->session_user_info->user_id),array('id'=>$data['ticket_id']));
            $ticket_chat=array(
                'created_by'=>$this->session_user_info->user_id,
                'ticket_id'=>$data['ticket_id'],
                'message'=>$from_user_name[0]['from_user_name'].' Assingn ticket to '.$to_user_name[0]['to_user_name'],
                'created_on'=>currentDate(),
                'from_user'=>$this->session_user_info->user_id,
                'to_user'=>$data['user_id'],
                'type'=>2
            );
            $is_insert=$this->User_model->insert_data('ticket_chat',$ticket_chat);
            if(isset($is_update) && $is_insert>0){

                $result = array('status'=>TRUE, 'message' => $this->lang->line('ticket_assign'), 'data'=>array($data['ticket_id']));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }

    }

}



