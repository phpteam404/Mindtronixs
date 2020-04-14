<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);
require APPPATH . '/third_party/mailer/mailer.php';
class Externalcontroller extends CI_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $language = 'en';
        if(isset($_SERVER['HTTP_LANG']) && $_SERVER['HTTP_LANG']!=''){
            $language = $_SERVER['HTTP_LANG'];
            if(is_dir('application/language/'.$language)==0){
                $language = $this->config->item('rest_language');
            }
        }
        $this->lang->load('rest_controller', $language);
        $this->load->model('DigitalContent_model');
        $this->load->model('User_model');

    }

    //* digital content view for online user  start *//
    public function digitalcontentonlineuser(){
        $sample_responce='{"digital_content_management_id":"117","content_name":"on line user digital content","description":"<p>digital content description</p>","expiry_date":"2020-04-13","category":"Required","sub_category":"Sub Categories","content_level":"high content","grade":"VII","tags":"test","no_of_views":0,"status":"Active","pre_url":"https://www.youtube.com/watch?v=sGymqz_1K5Y","post_url":"https://www.youtube.com/watch?v=pby0jqNriiM","documents":[{"document_id":"478","document_name":"working_icon_1586759266.png","module_type":"files","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/working_icon_1586759266.png"},{"document_id":"477","document_name":"Google_Pay_-_Pay_your_electricity_bill_in_just_a_few_taps_-_#MoneyMadeSimple_1586759266.mp4","module_type":"files","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/Google_Pay_-_Pay_your_electricity_bill_in_just_a_few_taps_-_#MoneyMadeSimple_1586759266.mp4"},{"document_id":"476","document_name":"https://www.youtube.com/watch?v=pby0jqNriiM","module_type":"url","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/https://www.youtube.com/watch?v=pby0jqNriiM"}]}';
        //echo $sample_responce;
        $data = $this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'message'=>$this->lang->line('invalid_data'),'data'=>'1');
            echo json_encode($result); exit;
        }
        $this->form_validator->add_rules('digital_content_management_id', array('required'=> $this->lang->line('digital_content_management_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            echo json_encode($result);exit;
        }
        if(!empty($data['digital_content_management_id'])){
            $data['request_type']='view';
            $content_info=$this->Digitalcontent_model->getDigitalContentInfo($data);
            $content_info[0]['no_of_views']=!empty($content_info[0]['no_of_views'])?$content_info[0]['no_of_views']:0;
            $documents=$this->Digitalcontent_model->getDocuments(array('module_type_id'=>$data['digital_content_management_id'],'module_type'=>array('digital_content','url')));//echo $this->db->last_query();exit;
            if(!empty($documents)){
                foreach($documents as $k=>$v){
                    $documents[$k]['document_url']=DOCUMENT_PATH.'digitalcontent/'.$v['document_name'];
                }
            }

            $content_history = array(
                'content_id' => $data['digital_content_management_id'],
                'session_user_id' => !empty($_GET['user_id'])?$_GET['user_id']:0,
                'client_browser' => $_SERVER['HTTP_USER_AGENT'],
                'client_os' => getUserOS($_SERVER['HTTP_USER_AGENT']),
                'client_remote_address' => $_SERVER['REMOTE_ADDR'],
                'created_date' => currentDate()
                );
            $this->User_model->insert_data('digital_content_view_history',$content_history);
            $content_info[0]['documents']=!empty($documents)?$documents:array();
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $content_info));
            echo json_encode($result);exit;
        }
    }
    //* digital content view for online user  end *//


    //* online user registration start *//
    public function addOnlineUser(){
        $data=$this->input->post();
        // $data = json_decode(file_get_contents("php://input"), true);
        // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'message'=>$this->lang->line('invalid_data'),'data'=>'1');
            echo json_encode($result); exit;
        }
        $this->form_validator->add_rules('email', array('required'=> $this->lang->line('email_req')));
        $this->form_validator->add_rules('name', array('required'=> $this->lang->line('name_req')));
        $this->form_validator->add_rules('leadsource', array('required'=> $this->lang->line('lead_source_req')));
        $this->form_validator->add_rules('password', array('required'=> $this->lang->line('password_req')));
        $this->form_validator->add_rules('number', array('required'=> $this->lang->line('contact_num_req')));
        $this->form_validator->add_rules('fee_master_id', array('required'=> $this->lang->line('fee_master_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            echo json_encode($result);exit;
        }
        if(!empty($data['email'])){
            $email_check = $this->User_model->check_email(array('email' => $data['email']));
            if(!empty($email_check)){
                $result = array('status'=>FALSE,'error'=>array('email' => $this->lang->line('email_duplicate')),'data'=>'');
                echo json_encode($result); exit;
            }
        }
        if(!empty($data['fee_master_id'])){
            $next_invoice_days=$this->getInvoiceDate($data['fee_master_id']);
        }
        // print_r($next_invoice_days['next_invoice_date']);exit;
        $user_table_data=array(
            'first_name'=>!empty($data['name'])?$data['name']:'',
            'last_name'=>!empty($data['last_name'])?$data['last_name']:' ',
            'email'=>!empty($data['email'])?$data['email']:'',
            'password'=>!empty($data['password'])?md5($data['password']):'',
            'user_role_id'=>9,
            'phone_no'=>!empty($data['number'])?$data['phone_no']:'',
            'created_by'=>0,
            'created_on'=>currentDate()
        );
        $user_id=$this->User_model->insert_data('user',$user_table_data);
        $student_table_data=array(
            'user_id'=>$user_id,
            'grade'=>!empty($data['grade'])?$data['grade']:'',
            'parent'=>!empty($data['parentname'])?$data['parentname']:'',
            'lead_source'=>!empty($data['leadsource'])?$data['leadsource']:'',
            'franchise_fee_id'=>!empty($data['fee_master_id'])?$data['fee_master_id']:0,
            'created_by'=>0,
            'created_on'=>currentDate(),
            'relation_with_student'=>!empty($data['relation'])?$data['relation']:0,
            'next_invoice_date'=>!empty($next_invoice_days['next_invoice_date'])?$next_invoice_days['next_invoice_date']:'',
            'remaining_invoice_days'=>!empty($next_invoice_days['days'])?$next_invoice_days['days']:0
        );
        $student_id=$this->User_model->insert_data('student',$student_table_data);
        // email notification and application notification for online user start //

        $get_fee_structure_details=$this->User_model->Check_record('fee_master',array('id'=>$data['fee_master_id']));
        $fee_structure=$get_fee_structure_details[0]['name'].','.round($get_fee_structure_details[0]['amount']).'/-';
        $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'ONLINE_USER_CREATION'));
        if($template_configurations['total_records']>0){
            $template_configurations=$template_configurations['data'][0];
            $wildcards=$template_configurations['wildcards'];
            $wildcards_replaces=array();
            $wildcards_replaces['name']=$data['name'];
            $wildcards_replaces['logo']=WEB_BASE_URL.'assets/img/logo.png';
            $wildcards_replaces['fee_structure']=!empty($fee_structure)?$fee_structure:'';
            $wildcards_replaces['email']=!empty($data['email'])?$data['email']:'';
            $wildcards_replaces['password']=!empty($data['password'])?$data['password']:'';
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
            $to=$data['email'];
            $to_name=$data['name'];
            $mailer_data['mail_from_name']=$from_name;
            $mailer_data['mail_to_name']=$data['name'];
            $mailer_data['mail_to_user_id']= $user_id;
            $mailer_data['mail_from']=$from;
            $mailer_data['mail_to']=$data['email'];
            $mailer_data['mail_subject']=$subject;
            $mailer_data['mail_message']=$body;
            $mailer_data['status']=0;
            $mailer_data['send_date']=currentDate();
            $mailer_data['is_cron']=0;//0-immediate mail,1-through cron job
            $mailer_data['email_template_id']=$template_configurations['id_email_template'];
            //echo '<pre>';print_r($customer_logo);exit;
            $mailer_id=$this->Email_model->addMailer($mailer_data);
            if($mailer_data['is_cron']==0) {
                $mail_sent_status=sendmail($data['email'],$subject,$body);                        
                if($mail_sent_status==1)
                    $this->Email_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
            }

            //App notification to be saved in Notification table.
            $link ='<a class="sky-blue" href="'.WEB_BASE_URL . '#/notifications/'.base64_encode($is_insert).'">Here</a>';
            $notification_wildcards_replaces['url_link'] = $link;
            $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
            $notification_comments = wildcardreplace($template_configurations['application_wildcards'],$notification_wildcards_replaces,$template_configurations['notification_comments']);
            $this->Email_model->addNotification(array(
                'assigned_to' => $user_id,
                'notification_template' => $notification_message,
                'notification_link' => '',
                'notification_comments' => $notification_comments,
                'notification_type' => 'app',
                'created_date_time' => currentDate(),
                'module_type' => 'user'
            ));
            
        }
        // email notification and application notification for online user end //


        // online user invoice generation  start//
        if($student_id>0){
            $student_data=$this->Invoices_model->getStudentInvoicedData(array('student_id'=>$student_id));
        }
        $invoice_data=array(
            'student_id'=>$student_data[0]['student_id'],
            'franchise_id'=>$student_data[0]['franchise_id'],
            'franchise_fee_id'=>$student_data[0]['franchise_fee_id'],
            'amount'=>$student_data[0]['amount'],
            'discount'=>$student_data[0]['discount'],
            'tax'=>$student_data[0]['tax'],
            'total_amount'=>$student_data[0]['total_amount'],
            'invoice_date'=>date("Y-m-d"),
            'total_amount'=>$student_data[0]['total_amount'],
            'created_by'=>0,
            'discount_amount'=>$student_data[0]['discount_amount'],
            'tax_amount'=>$student_data[0]['tax_amount'],
            'due_date'=>date('Y-m-d', strtotime(date("Y-m-d") .'+'.$student_data[0]['due_days'].'days')),
            'created_on'=>CurrentDate(),
            'invoice_type'=>4,// for online suer invoices
            'paid_amount'=>0
            );
        $month=date("m");
        $year=date("Y");
        $invoice_insert=$this->User_model->insert_data('student_invoice',$invoice_data);
        $id=str_pad($invoice_insert,6,"0",STR_PAD_LEFT);
        $invoice_number="MIN/".$year."/".$month."/".$id;
        $this->User_model->update_data('student_invoice',array('invoice_number'=>$invoice_number),array('id'=>$invoice_insert));
        // online user invoice generation  end//

        //  email notification and app notification for online user  start //

        $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'INVOICE_CREATION_ONLINE_USER'));
        if($template_configurations['total_records']>0){
            $template_configurations=$template_configurations['data'][0];
            $wildcards=$template_configurations['wildcards'];
            $wildcards_replaces=array();
            $wildcards_replaces['name'] = $data['name'];
            $wildcards_replaces['fee_term'] =$get_fee_structure_details[0]['name'];
            $wildcards_replaces['payment_url'] ='http://mindtronix.com/';
            $wildcards_replaces['month'] = date('M');
            $wildcards_replaces['year'] = date("Y");
            $wildcards_replaces['url'] = WEB_BASE_URL;
            $wildcards_replaces['href_text'] = '#'.$invoice_number;
            $wildcards_replaces['logo'] = WEB_BASE_URL.'assets/img/logo.png';
            $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
            $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
            /*$from_name=SEND_GRID_FROM_NAME;
            $from=SEND_GRID_FROM_EMAIL;
            $from_name=$cust_admin['name'];
            $from=$cust_admin['email'];*/
            $from_name=$template_configurations['email_from_name'];
            $from=$template_configurations['email_from'];
            $to=$data['email'];
            $to_name=$data['name'];
            $mailer_data['mail_from_name']=$from_name;
            $mailer_data['mail_to_name']=$data['name'];
            $mailer_data['mail_to_user_id']=$user_id;
            $mailer_data['mail_from']=$from;
            $mailer_data['mail_to']=$data['email'];
            $mailer_data['mail_subject']=$subject;
            $mailer_data['mail_message']=$body;
            $mailer_data['status']=0;
            $mailer_data['send_date']=currentDate();
            $mailer_data['is_cron']=1;//0-immediate mail,1-through cron job
            $mailer_data['email_template_id']=$template_configurations['id_email_template'];
            //echo '<pre>';print_r($customer_logo);exit;
            $mailer_id=$this->Email_model->addMailer($mailer_data);
            if($mailer_data['is_cron']==0) {
                $mail_sent_status=sendmail($data['email'],$subject,$body);                        
                if($mail_sent_status==1)
                    $this->Email_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
            }
            //Your Invoice Term {fee_term}  -  {current_month} -{year} is ready for view. Click on the link to view {url_link}
            //App notification to be saved in Notification table.
            $link ='<a class="sky-blue" href="#/invoices/online_users_invoice/view/'.$data['name'].'/'.base64_encode($invoice_insert).'">'.$invoice_number.'</a>';
            $notification_wildcards_replaces['fee_term'] = $get_fee_structure_details[0]['name'];
            $notification_wildcards_replaces['month'] = date('M');
            $notification_wildcards_replaces['year'] = date('Y');
            $notification_wildcards_replaces['url_link'] = $link;
            $notification_wildcards_replaces['payment_link'] ='<a class="sky-blue" href="http://mindtronix.com/">Here</a>';
            $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
            $this->Email_model->addNotification(array(
                'assigned_to' => $user_id,
                'notification_template' => $notification_message,
                'notification_link' => '',
                'notification_comments' => str_replace('{invoice_id}','#'.$invoice_number,$template_configurations['notification_comments']),
                'notification_type' => 'app',
                'created_date_time' => currentDate(),
                'module_type' => 'user'
            ));
            
        }
        


        //  email notification and app notification for online user  end //

        if($user_id>0 && $student_id>0){
            $result = array('status'=>TRUE, 'message' => "online User Created Scucessfully", 'data'=>array('data' =>''));
            echo json_encode($result);exit;
        }
        else{
            $result = array('status'=>FALSE,'message'=>$this->lang->line('invalid_data'),'data'=>'2');
            echo json_encode($result); exit;
        }

        
    }
    //* online user registration end *//

    //* calculate the next invoice date start *//
    function getInvoiceDate($franchise_fee_id){
        $date=date("Y-m-d");
        $day=date("d");
        $term_type=$this->User_model->getTermTypeKey(array('fee_master_id'=>$franchise_fee_id));
        if(!empty($term_type[0]['child_key'])){
            if($term_type[0]['child_key']==MONTHLY_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+1 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                    $curren_date=date_create(date("Y-m-d"));
                    $end_of_month_date=date_create(date("Y-m-t"));
                    $diff_days=date_diff($curren_date,$end_of_month_date);//it will return the no of days b/w current and endof month day 
                    $next_invoice_date= date('Y-m-01', strtotime($date .'+2 month'));
                    return array('days'=>$diff_days->days,'next_invoice_date'=>$next_invoice_date);
                }
            }
            if($term_type[0]['child_key']==HALFYEARLY_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+6 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                   
                    $next_invoice_date= date('Y-m-01', strtotime($date .'+7 month')); 
                    return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
            if($term_type[0]['child_key']==QUARTERYL_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+3 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+4 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
            if($term_type[0]['child_key']==ANNUAL_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+12 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+13 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
        }
    }
        //* calculate the next invoice date end *//
}
