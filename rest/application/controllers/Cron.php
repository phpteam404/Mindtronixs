<?php

defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(1);
require APPPATH . '/third_party/mailer/mailer.php';

class Cron extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Invoices_model');
    }
    public function testcron(){
        // $_SESSION['__ci_last_regenerate']
        echo '<pre>'.print_r($_SESSION);exit;
            
    }
    // send email through cron
    public function sendemails()
    {
        $limit=30;
        $mailer_data = $this->Email_model->getMailer(array('limit'=>$limit));
        
        foreach($mailer_data as $k=>$v){
            
            if($v['cron_status']==0 && $v['is_cron']==1){
                $this->Email_model->updateMailer(array('cron_status'=>1,'mailer_id'=>$v['mailer_id']));
                $from_name=$v['mail_from_name'];
                $from=$v['mail_from'];
                $subject=$v['mail_subject'];
                $body=$v['mail_message'];
                $to_name=$v['mail_to_name'];
                $to=$v['mail_to'];
                $mailer_id=$v['mailer_id'];
                
                $mail_sent_status=sendmail($to,$subject,$body);
                if($mail_sent_status==1) {
                    $this->Email_model->updateMailer(array('status' => 1,'cron_status'=>2,'mailer_id' => $mailer_id));
                }
                else{
                    $this->Email_model->updateMailer(array('cron_status'=>3,'mailer_id'=>$mailer_id));
                }
            }
        }
    }

    public function studentinvoicegeneration(){
        $STUDENT_INVOICE = TRUE;
        $STUDENT_NOTIFICATION = TRUE;
        $FRANCHISE_INVOICE = TRUE;
        $FRANCHISE_NOTIFICATION = TRUE;
        $STUDENT_NEXT_INVOICE_DATE = FALSE;
        /* Studnet Invoice Start*/
        if($STUDENT_INVOICE){
            $query  =   "SET @a=0"; 
            $this->db->query($query);
            
            //Student invoice Select Query
            $student_select = 'SELECT CONCAT("MIN/",f.franchise_code,"/",YEAR(CURDATE()),"/",DATE_FORMAT(CURDATE(),"%m"),"/",@a:=LPAD(@a+1, 6, 0)) invoice_number,f.id franchise_id,s.id student_id,fm.amount,fm.tax,((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))-((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100)+((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)) as total_amount,s.franchise_fee_id as franchise_fee_id,CURRENT_DATE()invoice_date,fm.discount,TRIM(((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100))+0 as discount_amount,TRIM((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)+0 as tax_amount,CURRENT_DATE() as created_on, 1 as created_by,DATE_ADD(CURDATE(), INTERVAL fm.due_days DAY) as due_date,1 as invoice_type,0 as paid_date
            FROM student s
            LEFT JOIN franchise f ON s.franchise_id = f.id
            LEFT JOIN fee_master fm ON s.franchise_fee_id = fm.id
            WHERE s.status=1
            AND s.next_invoice_date = CURDATE()
            AND s.subscription_status = 1
            AND s.school_id=0
            AND s.franchise_fee_id!=0';
    
            //Appending Student invoice select query with insert query
            $query1='INSERT INTO  student_invoice (invoice_number,franchise_id,student_id,amount,tax,total_amount,franchise_fee_id,invoice_date,discount,discount_amount,tax_amount,created_on,created_by,due_date,invoice_type,paid_date) '.$student_select;
            
            $student_invoiced_rows=$this->User_model->custom_query_affected_rows($query1);
            if($student_invoiced_rows >0) echo 'Student Invoice Generated.!'; else 'Student Invoice Failure.!';
        }
        /* Studnet Invoice End*/

        /* ======================================= */

        /* Email + Notification for Students Invoice Starts */
        if($STUDENT_NOTIFICATION){
            $student_select = "SELECT mc.child_name,si.invoice_number,si.id invoice_pk_id,u.email,u.id uid,CONCAT(u.first_name,' ',u.last_name) user_name FROM user u LEFT JOIN student s ON u.id = s.user_id LEFT JOIN student_invoice si ON si.student_id = s.id LEFT JOIN franchise_fee ff ON s.franchise_fee_id = ff.id LEFT JOIN fee_master fm ON ff.fee_master_id = fm.id LEFT JOIN master_child mc ON fm.term = mc.id WHERE s.next_invoice_date = CURDATE()";
            // echo $student_select;exit;
            $studnets_list = $this->User_model->custom_query($student_select);
            $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'INVOICE_CREATION'));
            foreach($studnets_list as $slk => $slv){
                if($template_configurations['total_records']>0){
                    $template_configurations=$template_configurations['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['name'] = $slv['user_name'];
                    $wildcards_replaces['fee_term'] = $slv['child_name'];
                    $wildcards_replaces['month'] = date('M');
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url'] = WEB_BASE_URL;
                    $wildcards_replaces['href_text'] = $slv['invoice_number'];
                    $wildcards_replaces['logo'] = WEB_BASE_URL.'assets/img/logo.png';
                    
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
                    $to=$slv['email'];
                    $to_name=$data['first_name'].' '.$data['last_name'];
                    $mailer_data['mail_from_name']=$from_name;
                    $mailer_data['mail_to_name']=$slv['user_name'];
                    $mailer_data['mail_to_user_id']=$slv['uid'];
                    $mailer_data['mail_from']=$from;
                    $mailer_data['mail_to']=$slv['email'];
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
                    $link ='<a class="sky-blue" href="#/invoices/students_invoice/view/'.urlencode($slv['user_name']).'/'.base64_encode($slv['invoice_pk_id']).'">'.$slv['invoice_number'].'</a>';
                    $notification_wildcards_replaces['fee_term'] = $slv['child_name'];
                    $notification_wildcards_replaces['month'] = date('M');
                    $notification_wildcards_replaces['year'] = date('Y');
                    $notification_wildcards_replaces['url_link'] = $link;
                    $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
                    $this->Email_model->addNotification(array(
                        'assigned_to' => $slv['uid'],
                        'notification_template' => $notification_message,
                        'notification_link' => '',
                        'notification_comments' => str_replace('{invoice_id}',$slv['invoice_number'],$template_configurations['notification_comments']),
                        'notification_type' => 'app',
                        'created_date_time' => currentDate(),
                        'module_type' => 'user'
                    ));
                    
                }
            }
        }
        /* Email + Notification for Students Invoice Ends */

        /* Franchise Invoice Start*/
        if($FRANCHISE_INVOICE){
            $query_b  =   "SET @b=0"; 
            $this->db->query($query_b);
            
            //Franchise Invoice Select Query
            $franchise_select = 'SELECT SUM(si.total_amount) as amount,si.franchise_id, CONCAT("MIN/",f.name,"/",YEAR(CURDATE()),"/",DATE_FORMAT(CURDATE(),"%m"),"/",@b:=LPAD(@b+1, 6, 0)) invoice_number,(SUM(si.total_amount)*'.FRACHISE_PERCENTAGE.'/100) as royal_amount,CURDATE(),3 as invoice_type,CURRENT_DATE as created_on ,'.FRACHISE_TAX_PERCENTAGE.' as tax,(SUM(si.total_amount)*'.FRACHISE_TAX_PERCENTAGE.'/100) as tax_amount,((SUM(si.total_amount)*'.FRACHISE_PERCENTAGE.'/100)+(SUM(si.total_amount)*'.FRACHISE_TAX_PERCENTAGE.'/100)) as total_amount
            FROM student_invoice si
            LEFT JOIN student s ON si.student_id=s.id
            LEFT JOIN franchise f ON si.franchise_id = f.id
            WHERE si.invoice_type=1
            AND s.next_invoice_date = CURDATE()
            AND s.subscription_status = 1
            AND s.school_id=0
            AND s.franchise_fee_id!=0
            GROUP BY si.franchise_id
            ORDER BY si.id';
            
            //Appending Franchise invoice select query with insert query
            $query3='INSERT student_invoice(amount,franchise_id,invoice_number,royal_amount,invoice_date,invoice_type,created_on,tax,tax_amount,total_amount) '. $franchise_select;
            $frachise_invoice_rows=$this->User_model->custom_query_affected_rows($query3);
            if($frachise_invoice_rows >0) echo 'Franchise Invoice Generated.!'; else 'Franchise Invoice Failure.!';
        }
        /* Franchise Invoice End*/

        /* =========================================== */

        /* Email + Notification for Franchise Invoice Starts */
        if($FRANCHISE_NOTIFICATION){
            // echo $franchise_select;exit;
            $franchise_select = "SELECT si.invoice_number,si.id invoice_pk_id,u.id uid,u.email,CONCAT(u.first_name,' ',u.last_name) user_name FROM user u JOIN student_invoice si ON u.franchise_id = si.franchise_id AND u.user_role_id = 5 WHERE si.invoice_date = CURDATE() AND si.invoice_type = 3";
            // echo $franchise_select;exit;
            $franchise_list = $this->User_model->custom_query($franchise_select);
            $template_configurations=$this->Email_model->EmailTemplateList(array('language_id' =>1,'module_key'=>'INVOICE_CREATION'));
            foreach($franchise_list as $flk => $flv){
                if($template_configurations['total_records']>0){
                    $template_configurations=$template_configurations['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['name'] = $flv['user_name'];
                    $wildcards_replaces['fee_term'] = '';
                    $wildcards_replaces['month'] = date('M');
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url'] = WEB_BASE_URL;
                    $wildcards_replaces['href_text'] = $flv['invoice_number'];
                    $wildcards_replaces['logo'] = WEB_BASE_URL.'assets/img/logo.png';
                    
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
                    $to=$flv['email'];
                    $to_name=$data['first_name'].' '.$data['last_name'];
                    $mailer_data['mail_from_name']=$from_name;
                    $mailer_data['mail_to_name']=$flv['user_name'];
                    $mailer_data['mail_to_user_id']=$flv['uid'];
                    $mailer_data['mail_from']=$from;
                    $mailer_data['mail_to']=$flv['email'];
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
                    $link ='<a class="sky-blue" href="#/invoices/students_invoice/view/'.urlencode($flv['user_name']).'/'.base64_encode($flv['invoice_pk_id']).'">'.$flv['invoice_number'].'</a>';
                    $notification_wildcards_replaces['fee_term'] = '';
                    $notification_wildcards_replaces['month'] = date('M');
                    $notification_wildcards_replaces['year'] = date('Y');
                    $notification_wildcards_replaces['url_link'] = $link;
                    $notification_message = wildcardreplace($template_configurations['wildcards'],$notification_wildcards_replaces,$template_configurations['application_template_content']);
                    $this->Email_model->addNotification(array(
                        'assigned_to' => $flv['uid'],
                        'notification_template' => $notification_message,
                        'notification_link' => '',
                        'notification_comments' => str_replace('{invoice_id}',$flv['invoice_number'],$template_configurations['notification_comments']),
                        'notification_type' => 'app',
                        'created_date_time' => currentDate(),
                        'module_type' => 'user'
                    ));
                    
                }
            }
        }
        /* Email + Notification for Franchise Invoice Ends */

        /* Updating Next Invoice Date for Students */
        if($STUDENT_NEXT_INVOICE_DATE){
            $query2='UPDATE student s 
            LEFT JOIN fee_master fm ON s.franchise_fee_id=fm.id
            LEFT JOIN master_child mc ON fm.term=mc.id AND mc.master_id=11
            SET next_invoice_date =CASE 
                    WHEN mc.child_key="'.HALFYEARLY_TERM_KEY.'"THEN DATE_ADD(s.next_invoice_date, INTERVAL +6 MONTH) 
                    WHEN mc.child_key="'.MONTHLY_TERM_KEY.'" THEN DATE_ADD(s.next_invoice_date, INTERVAL +1 MONTH)
                    WHEN mc.child_key="'.QUARTERYL_TERM_KEY.'" THEN DATE_ADD(s.next_invoice_date, INTERVAL +3 MONTH) 
                    WHEN mc.child_key="'.ANNUAL_TERM_KEY.'" THEN DATE_ADD(s.next_invoice_date, INTERVAL +12 MONTH)
                    END,remaining_invoice_days=0
                    WHERE s.status=1
                    AND s.subscription_status = 1
                    AND s.next_invoice_date = CURDATE()
                    AND s.school_id=0
                    AND s.franchise_fee_id!=0';
            $update_rows=$this->User_model->custom_query_affected_rows($query2);
            if($update_rows >0) echo 'Updating Next Invoice Date for Students Generated.!'; else 'Updating Next Invoice Date for Students Failure.!';
        }

        echo 'Invoice Generation Cron End.';
        exit;
    }
    
}   