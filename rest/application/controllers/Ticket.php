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
                    $path='uploads/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['document']['tmp_name'][$i],
                        'image' => $_FILES['document']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'type'=>'chat'));
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
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $ticket_data=array(
            'description'=>$data['description'],
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
               $ticket_chat_id=$this->User_model->insertdata('ticket_chat',array('sender_user_id'=>!empty($this->session_user_id)?$this->session_user_id:'0','ticket_id'=>$inserted_id,'message'=>$data['description'],'created_on'=>currentDate()));
            }
            $this->User_model->update_where_in('documents',array('ticket_chat_id'=>$ticket_chat_id),array('id'=>$document_id));
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
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',array('sender_user_id'=>!empty($this->session_user_id)?$this->session_user_id:'0','ticket_id'=>$inserted_id,'message'=>$data['description'],'created_on'=>currentDate()));
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

        $chat_data=array(
            'ticket_id'=>$data['ticket_id'],
            'message'=>!empty($data['message'])?$data['message']:'',
            'created_on'=>currentDate(), 
            'sender_user_id'=>!empty($this->session_user_id)?$this->session_user_id:'0'
        );
        if(!empty($_FILES)){
            $no_of_files=count($_FILES['document']['name']);
            for ($i=0; $i <$no_of_files ; $i++) { 
                // print_r($_FILES['document']);//
                $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png');   
              //  print_r(in_array($_FILES['document']['name'][$i],$allowed_types));exit;
                if(in_array($_FILES['document']['type'][$i],$allowed_types))
                {
                    $path='uploads/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['document']['tmp_name'][$i],
                        'image' => $_FILES['document']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        $document_id[]=$this->User_model->insertdata('documents',array('ticket_chat_id'=>$ticket_chat_id,'document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'type'=>'chat'));
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
            $this->User_model->update_where_in('documents',array('ticket_chat_id'=>$ticket_chat_id),array('id'=>$document_id));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK);

        }

        elseif(empty($_FILES)){
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',$chat_data);
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
                    $document[$k][$l]['document_url']=DOCUMENT_PATH.$m;
    
                }
                unset($chatdata[$k]['documents'][$l]);
                $chatdata[$k]['documents']=$document[$k];
            }
                
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array($chatdata));
        $this->response($result, REST_Controller::HTTP_OK);
        
    } 

}



