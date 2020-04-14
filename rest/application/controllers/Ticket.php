<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
require 'ImageFactory.php';

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
        if(!empty($_FILES)){
            $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png','application/pdf','video/mp4','video/quicktime'); 
            $no_of_files=count($_FILES['files']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = $_FILES['files']['type'][$i];
            }
            $intersect_data=array_intersect($extensions,$allowed_types);
            for ($i=0; $i <$no_of_files ; $i++) { 
                if($extensions==$intersect_data)
                {
                    $path='uploads/ticket/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['files']['tmp_name'][$i],
                        'image' => $_FILES['files']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        // $imageName='ticket/'.$imageName;
                        $image_extensions=array('gif','jpg','jpeg','png');
                        if(in_array(pathinfo($_FILES['files']['name'][$i],PATHINFO_EXTENSION),$image_extensions)){
                            $ImageMaker =   new ImageFactory();
                            // Here is just a test landscape sized image
                            $image_target   =   "uploads/ticket/".$imageName;
                            // This will save the file to disk. $destination is where the file will save and with what name
                            if(!is_dir('uploads/ticket/small_images/')){ mkdir('uploads/ticket/small_images/'); }
                            $small_images_destination    =   "uploads/ticket/small_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,65,65,$small_images_destination);//this is used to resize image with 65X65 resolution
                            if(!is_dir('uploads/ticket/medium_images/')){ mkdir('uploads/ticket/medium_images/'); }
                            $medium_images_destination    =   "uploads/ticket/medium_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,150,150,$medium_images_destination);//this is used to resize image with 150X150 resolution
                        }
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'module_type'=>'ticket_create','status'=>1));
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
        $this->form_validator->add_rules('issue', array('required' => $this->lang->line('ticket_issue_req')));
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
            'issue_type'=>$data['issue'],
            'ticket_rised_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'created_on'=>currentDate(),
            'status'=>46
            // 'documents'=>!empty($imageName)?$imageName:''

        );
        // print_r($ticket_data);exit;//
        // print_r(ucwords($this->session_user_info->username));exit;
        // $message=ucwords($this->session_user_info->username).' Created ticket';
        // print_r($message);exit;
       if(!empty($_FILES) && count($document_id)>0){

           $inserted_id= $this->User_model->insertdata('ticket',$ticket_data);//echo $this->db->last_query();exit;
           if($inserted_id>0){
               $ticket_id="MINDTKTNO_".$inserted_id;
               $this->User_model->update_data('ticket',array('ticket_no'=>$ticket_id),array('id'=>$inserted_id));
            }
            $this->sendTicketEmails($type='ticket_create',$user_display_ticket_id=$ticket_id,$data_base_ticket=array('ticket_id'=>$inserted_id,'title'=>$data['title']),$user_ids=array(1));
            $this->User_model->update_where_in('documents',array('module_type_id'=>$inserted_id),array('id'=>$document_id));

           $result = array('status'=>TRUE, 'message' => $this->lang->line('ticket_add'), 'data'=>array('data'=>$ticket_id));
           $this->response($result, REST_Controller::HTTP_OK);   
       }
       elseif(empty($_FILES)){
            $inserted_id= $this->User_model->insertdata('ticket',$ticket_data);
            if($inserted_id>0){
            $ticket_id="MINDTKTNO_".$inserted_id;
            $this->User_model->update_data('ticket',array('ticket_no'=>$ticket_id),array('id'=>$inserted_id));
            $this->sendTicketEmails($type='ticket_create',$user_display_ticket_id=$ticket_id,$data_base_ticket=array('ticket_id'=>$inserted_id,'title'=>$data['title']),$user_ids=array(1));
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
        $this->form_validator->add_rules('ticket_status', array('required' => $this->lang->line('ticket_status_req')));
       // $this->form_validator->add_rules('message', array('required' => $this->lang->line('message_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // $get_ticket_data=$this->Ticket_model->getTicketData(array('ticket_id'=>$data['ticket_id']));//echo $this->db->last_query();exit;
        // if($get_ticket_data[0]['status_id']!=$data['ticket_status']){
        //     $get_current_status_name=$this->User_model->check_record_selected('child_name as status','master_child',array('master_id'=>18,'id'=>$data['ticket_status']));
        //     $action_message= 'Status changed from '.$get_ticket_data[0]['status'].' to '.$get_current_status_name[0]['status'];
        // }
        // // print_r($get_ticket_data[0]['status']);exit;
        // $to_user=$this->getFromAndToUser(array('ticket_id'=>$data['ticket_id']));
        // // print_r($to_user);exit;
        $chat_data=array(
            'ticket_id'=>$data['ticket_id'],
            'message'=>!empty($data['message'])?$data['message']:'',
            'created_on'=>currentDate(), 
            'created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'status'=>$data['ticket_status']         
        );
        // print_r($_FILES);exit;
        if(!empty($_FILES)){
            $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png','application/pdf','video/mp4','video/quicktime'); 
            $no_of_files=count($_FILES['files']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = $_FILES['files']['type'][$i];
            }
            $intersect_data=array_intersect($extensions,$allowed_types);
            for ($i=0; $i <$no_of_files ; $i++) { 
                if($extensions==$intersect_data)
                {
                    $path='uploads/ticket/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['files']['tmp_name'][$i],
                        'image' => $_FILES['files']['name'][$i],
                        'upload_path' => $path,''
                        ));
                        $image_extensions=array('gif','jpg','jpeg','png');
                        if(in_array(pathinfo($_FILES['files']['name'][$i],PATHINFO_EXTENSION),$image_extensions)){ 
                            $ImageMaker =   new ImageFactory();
                            // Here is just a test landscape sized image
                            $image_target   =   "uploads/ticket/".$imageName;
                            // This will save the file to disk. $destination is where the file will save and with what name
                            if(!is_dir('uploads/ticket/small_images/')){ mkdir('uploads/ticket/small_images/'); }
                            $small_images_destination    =   "uploads/ticket/small_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,65,65,$small_images_destination);//this is used to resize image with 65X65resolution
                            if(!is_dir('uploads/ticket/medium_images/')){ mkdir('uploads/ticket/medium_images/'); }
                            $medium_images_destination    =   "uploads/ticket/medium_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,150,150,$medium_images_destination);//this is used to resize image with 150X150 resolution
                            // $imageName='ticket/'.$imageName;
                        }
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'module_type'=>'ticket_chat'));
                }
                else
                {
                    $result = array('status'=>FALSE,'error'=>array('document' => $this->lang->line('upload_document')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            
        }
        $ticket_info = $this->User_model->check_record('ticket',array('id'=>$data['ticket_id']));
        if(!empty($_FILES) && count($document_id)>0){
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',$chat_data);
            $this->User_model->update_where_in('documents',array('module_type_id'=>$ticket_chat_id),array('id'=>$document_id));
            $this->User_model->update_data('ticket',array('last_updated_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','last_update_on'=>currentDate(),'status'=>$data['ticket_status']),array('id'=>$data['ticket_id']));
            $this->sendTicketEmails($type='ticket_update',$user_display_ticket_id=null,$data_base_ticket=array('ticket_id'=>$ticket_info[0]['id'],'title'=>$ticket_info[0]['title']),$user_ids=null,$update_comments=$data['message']);
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK);

        }

        elseif(empty($_FILES)){
            $ticket_chat_id=$this->User_model->insertdata('ticket_chat',$chat_data);
            $this->User_model->update_data('ticket',array('last_updated_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','last_update_on'=>currentDate(),'status'=>$data['ticket_status']),array('id'=>$data['ticket_id']));
            $this->sendTicketEmails($type='ticket_update',$user_display_ticket_id=null,$data_base_ticket=array('ticket_id'=>$ticket_info[0]['id'],'title'=>$ticket_info[0]['title']),$user_ids=null,$update_comments=$data['message']);
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK); 
        }
        
    }
    
    public function ticketInfo_get()
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('ticket_id', array('required' => $this->lang->line('ticket_id_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $ticket_data=$this->Ticket_model->getTicketData(array('ticket_id'=>$data['ticket_id']));//echo $this->db->last_query();exit;
        $ticket_data[0]['status_display']= getObjOnId($ticket_data[0]['status_display'],!empty($ticket_data[0]['status_display'])?true:false);

        $ticket_data=$ticket_data[0];
        $get_documents=$this->User_model->check_record('documents',array('module_type_id'=>$data['ticket_id'],'module_type'=>'ticket_create')); foreach($get_documents as $k=>$v){
            $document[$k]['document_name']=$v['document_name'];
            $document[$k]['document_url']=DOCUMENT_PATH.'ticket/'.$v['document_name'];
        }

        $ticket_data['documents']=!empty($document)?$document:array();
        $get_chat_details=$this->Ticket_model->getChat(array('ticket_id'=>$data['ticket_id']));//echo $this->db->last_query();exit;
        $created_data=array(
            'message'=>'Created ticket',
            'created_by'=>$ticket_data['created_by'],
            'date'=>date("d M Y",strtotime($ticket_data['created_date'])),
            'created_date'=>date("d-m-Y",strtotime($ticket_data['created_date'])),
            'time'=>date("h:i A",strtotime($ticket_data['created_date'])),
            'status'=>"New"
        );
        array_push($get_chat_details,$created_data);        
        $groupby_date_data=$this->groupArray($get_chat_details, "created_date");//this function group the chat data by date
        // print_r($groupby_date_data);exit;
        foreach($groupby_date_data as $k2=>$v2){ 
            if(!empty($v2))
            {
                foreach($v2 as $k3=>$v3){
                    if(!empty($v3['ticket_chat_id'])){
                        $url=DOCUMENT_PATH.'ticket/';
                        $get_chat_documents=$this->User_model->check_record_selected('concat("'.$url.'",document_name) as document_url','documents',array('module_type_id'=>$v3['ticket_chat_id'],'module_type'=>'ticket_chat'));//echo $this->db->last_query();exit;
                        if(!empty($get_chat_documents))
                        $groupby_date_data[$k2][$k3]['documents']=!empty($get_chat_documents)?$get_chat_documents:array();
                    }          
                }
            }
        }
        // print_r($groupby_date_data);exit;
        // array_merge()
        // array_push($get_chat_details,$created_data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('ticket_data'=>$ticket_data,'chat_history'=>$groupby_date_data));
        $this->response($result, REST_Controller::HTTP_OK);
        
    } 
    public function ticketList_get()
    {
        $data=$this->input->get();
        // print_r($data);exit;
        $data['user_role_id']=$this->session_user_info->user_role_id;
        $data['user_id']=$this->session_user_info->user_id;
        // if(in_array($data['user_role_id'],array('1','4'))){//display tickets only specific user only
        //     $ticket_list=$this->Ticket_model->getTickets($data);//echo $this->db->last_query();exit;
        // }
        // else{
        //     $result = array('status'=>FALSE,'error'=>$this->lang->line('permission_not_allowed'),'data'=>'');
        //     $this->response($result, REST_Controller::HTTP_OK);    
        // }
        if($this->session_user_info->user_role_id==2 || $this->session_user_info->user_role_id==5){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        if($this->session_user_info->user_role_id==10){
            $data['school_id'] = $this->session_user_info->school_id;
        }
        $ticket_list=$this->Ticket_model->getTickets($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data'=>$ticket_list['data'],'total_records'=>$ticket_list['total_records'],'table_headers'=>getTableHeads('ticket_list')));
        $this->response($result, REST_Controller::HTTP_OK);

    }
    public function getFromAndToUser($data)//this function is used to return to user
    {
        $result=$this->User_model->Check_record('ticket',array('id'=>$data['ticket_id']));//echo $this->db->last_query();exit;
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
    function groupArray($arr, $group, $preserveGroupKey = false, $preserveSubArrays = false) {
        $temp = array();
        foreach($arr as $key => $value) {
            $groupValue = $value[$group];
            if(!$preserveGroupKey)
            {
                unset($arr[$key][$group]);
            }
            if(!array_key_exists($groupValue, $temp)) {
                $temp[$groupValue] = array();
            }
            if(!$preserveSubArrays){
                $data = count($arr[$key]) == 1? array_pop($arr[$key]) : $arr[$key];
            } else {
                $data = $arr[$key];
            }
            $temp[$groupValue][] = $data;
        }
        // print_r($temp);exit;
        foreach($temp as $k1=>$v1){
            $chat_data[]=$v1;
        }
        return $chat_data;
    }
    function sendTicketEmails($type,$user_display_ticket_id=null,$data_base_ticket=null,$user_ids=null,$update_commets=null){//this function is used to send emails in create application notifications  
        $ticket_id=$user_display_ticket_id;
        if($type=='ticket_create'){//this type is used for send create/update ticket mails based on type 
            $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'TICKET_CREATION_OWNER'));
            if($template_configurations['total_records']>0){
                $template_configurations=$template_configurations['data'][0];
                $wildcards=$template_configurations['wildcards'];
                $wildcards_replaces=array();
                $wildcards_replaces['ticket_no']=$ticket_id;
                $wildcards_replaces['logo']=WEB_BASE_URL.'assets/img/logo.png';
                $wildcards_replaces['year'] = date("Y");
                $wildcards_replaces['url']=WEB_BASE_URL;
                $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                /*$from_name=SEND_GRID_FROM_NAME;
                $from=SEND_GRID_FROM_EMAIL;
                $from_name=$cust_admin['name'];
                $from=$cust_admin['email'];*/
                $from_name=$template_configurations['email_from_name'];
                $from=$template_configurations['email_from'];
                $to_name=$this->session_user_info->first_name.' '.$this->session_user_info->last_name;
                $mailer_data['mail_from_name']=$from_name;
                $mailer_data['mail_to_name']=$this->session_user_info->first_name.' '.$this->session_user_info->last_name;
                $mailer_data['mail_to_user_id']=$this->session_user_id;
                $mailer_data['mail_from']=$from;
                $mailer_data['mail_to']=$this->session_user_info->email;
                $mailer_data['mail_subject']=$subject;
                $mailer_data['mail_message']=$body;
                $mailer_data['status']=0;
                $mailer_data['send_date']=currentDate();
                $mailer_data['is_cron']=1;//0-immediate mail,1-through cron job
                $mailer_data['email_template_id']=$template_configurations['id_email_template'];
                //echo '<pre>';print_r($customer_logo);exit;
                $mailer_id=$this->Email_model->addMailer($mailer_data);
                if($mailer_data['is_cron']==0) {
                    $mail_sent_status=sendmail($to,$subject,$body);                        
                    if($mail_sent_status==1)
                        $this->Email_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                }
                //App notification to be saved in Notification table. array('ticket_id'=>$inserted_id,'title'=>$data['title'])
                // $link ='<a style="color: #22bcf2" class="sky-blue" href="'.WEB_BASE_URL . '#/notifications/'.base64_encode($is_insert).'">Here</a>';
                $link='<a class="sky-blue" href="#/ticket/view/'.urlencode($data_base_ticket['title']).'/'.base64_encode($data_base_ticket['ticket_id']).'">#'.$ticket_id.'</a>';
                $notification_wildcards_replaces['url_link'] =$link ;
                $notification_wildcards_replaces['ticket_no'] = '#'.$ticket_id;
                $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
                $notification_comments = wildcardreplace($template_configurations['application_wildcards'],$notification_wildcards_replaces,$template_configurations['notification_comments']);
                $this->Email_model->addNotification(array(
                    'assigned_to' =>$this->session_user_id,
                    'notification_template' => $notification_message,
                    'notification_link' => '',
                    'notification_comments' => $notification_comments,
                    'notification_type' => 'app',
                    'created_date_time' => currentDate(),
                    'module_type' => 'ticket'
                ));
            }
            $user_ids_send_mails=$user_ids; //this is used for send mails for  specific admins to pass user id 
            foreach($user_ids_send_mails as $k=>$v){
                $user_details=$this->User_model->check_record_selected('id as user_id,concat(first_name," ",last_name) as user_name,email','user',array('user_role_id'=>$v));
                $ticket_info=$this->Ticket_model->getTicketData(array('ticket_id'=>$data_base_ticket['ticket_id']));
                $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'TICKET_CREATION_ADMIN'));
                if($template_configurations['total_records']>0){
                        $template_configurations=$template_configurations['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['ticket_no']=$ticket_info[0]['issue_id'];
                    $wildcards_replaces['ticket_title']=$ticket_info[0]['title'];
                    $wildcards_replaces['ticket_description']=$ticket_info[0]['description'];
                    $wildcards_replaces['issue_type']=$ticket_info[0]['issue_type'];
                    $wildcards_replaces['status']=$ticket_info[0]['status'];
                    $wildcards_replaces['created_by']=$ticket_info[0]['created_by'];
                    $wildcards_replaces['created_date_time']=date("d M Y H:i:s A", strtotime($ticket_info[0]['created_date']));
                    $wildcards_replaces['logo']=WEB_BASE_URL.'assets/img/logo.png';
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url']=WEB_BASE_URL;
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
                    $to_name=$user_details[0]['user_name'];
                    $mailer_data['mail_from_name']=$from_name;
                    $mailer_data['mail_to_name']=$user_details[0]['user_name'];
                    $mailer_data['mail_to_user_id']=$user_details[0]['user_id'];
                    $mailer_data['mail_from']=$from;
                    $mailer_data['mail_to']=$user_details[0]['email'];
                    $mailer_data['mail_subject']=$subject;
                    $mailer_data['mail_message']=$body;
                    $mailer_data['status']=0;
                    $mailer_data['send_date']=currentDate();
                    $mailer_data['is_cron']=1;//0-immediate mail,1-through cron job
                    $mailer_data['email_template_id']=$template_configurations['id_email_template'];
                    //echo '<pre>';print_r($customer_logo);exit;
                    $mailer_id=$this->Email_model->addMailer($mailer_data);
                    if($mailer_data['is_cron']==0) {
                        $mail_sent_status=sendmail($to,$subject,$body);                        
                        if($mail_sent_status==1)
                            $this->Email_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                    }
                    //App notification to be saved in Notification table.
                    $link='<a class="sky-blue" href="#/ticket/view/'.urlencode($data_base_ticket['title']).'/'.base64_encode($data_base_ticket['ticket_id']).'">#'.$ticket_id.'</a>';
                    // $link='#/ticket/view/'.urlencode($data_base_ticket['title']).'/'.base64_encode($data_base_ticket['ticket_id']);
                    $notification_wildcards_replaces['url_link'] =$link ;
                    $notification_wildcards_replaces['ticket_no'] = '#'.$ticket_id;
                    $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
                    $notification_comments = wildcardreplace($template_configurations['application_wildcards'],$notification_wildcards_replaces,$template_configurations['notification_comments']);
                    $this->Email_model->addNotification(array(
                        'assigned_to' =>$user_details[0]['user_id'],
                        'notification_template' => $notification_message,
                        'notification_link' => '',
                        'notification_comments' => $notification_comments,
                        'notification_type' => 'app',
                        'created_date_time' => currentDate(),
                        'module_type' => 'ticket'
                    ));
                }
            }
            return 1;
        }
        if($type=='ticket_update'){//this is used for send mails when ticket is updated
            $ticket_info=$this->Ticket_model->getTicketData(array('ticket_id'=>$data_base_ticket['ticket_id']));
            if($this->session_user_info->user_id==$ticket_info[0]['ticket_rised_by']){//check the login user is ticket rised user or not for send maisl to admins/owners
                $user_ids_send_mails=array(1);
                $team_name='Support Team';
            }
            else{
                $user_ids_send_mails=array($ticket_info[0]['ticket_rised_by']);
                $team_name='Owner';
            }
            foreach($user_ids_send_mails as $k=>$v){
                $user_details=$this->User_model->check_record_selected('id as user_id,concat(first_name," ",last_name) as user_name,email','user',array('id'=>$v));
                $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'TICKET_UPDATE'));
                if($template_configurations['total_records']>0){
                    $template_configurations=$template_configurations['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['ticket_no']=$ticket_info[0]['issue_id'];
                    $wildcards_replaces['ticket_title']=$ticket_info[0]['title'];
                    $wildcards_replaces['comments']=!empty($update_commets)?$update_commets:'';
                    $wildcards_replaces['team_name']=$team_name;
                    $wildcards_replaces['update_by']=!empty($ticket_info[0]['last_updated_by'])?$ticket_info[0]['last_updated_by']:'';
                    $wildcards_replaces['update_date_time']=!empty($ticket_info[0]['last_updated'])?date("d M Y H:i:s A", strtotime($ticket_info[0]['last_updated'])):'';
                    $wildcards_replaces['status']=$ticket_info[0]['status'];
                    $wildcards_replaces['logo']=WEB_BASE_URL.'assets/img/logo.png';
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url']=WEB_BASE_URL;
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
                    $to_name=$user_details[0]['user_name'];
                    $mailer_data['mail_from_name']=$from_name;
                    $mailer_data['mail_to_name']=$user_details[0]['user_name'];
                    $mailer_data['mail_to_user_id']=$user_details[0]['user_id'];
                    $mailer_data['mail_from']=$from;
                    $mailer_data['mail_to']=$user_details[0]['email'];
                    $mailer_data['mail_subject']=$subject;
                    $mailer_data['mail_message']=$body;
                    $mailer_data['status']=0;
                    $mailer_data['send_date']=currentDate();
                    $mailer_data['is_cron']=1;//0-immediate mail,1-through cron job
                    $mailer_data['email_template_id']=$template_configurations['id_email_template'];
                    //echo '<pre>';print_r($customer_logo);exit;
                    $mailer_id=$this->Email_model->addMailer($mailer_data);
                    if($mailer_data['is_cron']==0) {
                        $mail_sent_status=sendmail($to,$subject,$body);                        
                        if($mail_sent_status==1)
                            $this->Email_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                    }
                    //App notification to be saved in Notification table.
                    $link='<a class="sky-blue" href="#/ticket/view/'.urlencode($data_base_ticket['title']).'/'.base64_encode($data_base_ticket['ticket_id']).'">#'.$ticket_id.'</a>';
                    // $link='#/ticket/view/'.urlencode($data_base_ticket['title']).'/'.base64_encode($data_base_ticket['ticket_id']);
                    $notification_wildcards_replaces['url_link'] =$link ;
                    $notification_wildcards_replaces['ticket_no'] = '#'.$ticket_info[0]['issue_id'];
                    $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
                    $notification_comments = wildcardreplace($template_configurations['application_wildcards'],$notification_wildcards_replaces,$template_configurations['notification_comments']);
                    $this->Email_model->addNotification(array(
                        'assigned_to' =>$user_details[0]['user_id'],
                        'notification_template' => $notification_message,
                        'notification_link' => '',
                        'notification_comments' => $notification_comments,
                        'notification_type' => 'app',
                        'created_date_time' => currentDate(),
                        'module_type' => 'ticket'
                    ));
                }
            }
            return 1;
        }
    }

}



