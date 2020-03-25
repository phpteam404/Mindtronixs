<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Invoice extends REST_Controller
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

    public function studentInvoiceList_get(){//this service is used to get student invoices
        $data=$this->input->get();

        if(!empty($data['student_invoice_id'])){
            $student_invoice_payment_history=$this->Invoices_model->getStudentPaymentHistory(array('student_invoice_id'=>$data['student_invoice_id']));//echo $this->db->last_query();exit;
            $student_invoice_payment_history=!empty( $student_invoice_payment_history)?$student_invoice_payment_history:array();
            $student_invoice_info=$this->Invoices_model->getStudentInvoiceList($data);//echo $this->db->last_query();exit;
            
            if(!empty($student_invoice_info['data'][0] && $student_invoice_info['data'][0]['payment_status']==98 ||$student_invoice_info['data'][0]['payment_status']==100)){//if payment status= 98 then it is consider as invoice payment status in Due,similarly 100 then it is OverDue
                $date=date("Y-m-d");
                if($student_invoice_info['data'][0]['term']==19){//if term  value is 19 then we consider as Monthly plan in fee master
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['next_invoice_date'] .'-10 day')); //this is due date that return before 10 days
                }
                if($student_invoice_info['data'][0]['term']==21){//if term  value is 19 then we consider as halfyearly plan in fee master
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['next_invoice_date'] .'-1 month')); 
                }
                if($student_invoice_info['data'][0]['term']==20){//if term  value is 19 then we consider as Quarterly plan in fee master
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['next_invoice_date'] .'-15 day')); 
                }
                if($student_invoice_info['data'][0]['term']==22){//if term  value is 19 then we consider as Yearly plan in fee master
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['next_invoice_date'] .'-2 month')); 
                }
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_info['data'],'due_date'=>!empty($due_date)?$due_date:$date,'student_invoice_payment_history'=>$student_invoice_payment_history));
            } 
            else{
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_info['data'],'paid_date'=>$date=date("Y-m-d"),'student_invoice_payment_history'=>$student_invoice_payment_history));
            }
        }
        else{
                if($this->session_user_info->user_role_id==2){
                    $data['franchise_id']=$this->session_user_info->franchise_id;
                }
                $invoice_amount=$this->Invoices_model->getAmount($data);
                $data['payment_status']=97;//to get collected amount  pass the payment status id is 97
                $collected_amount=$this->Invoices_model->getAmount($data);
                unset($data['payment_status']);
                $data['payment_status']=98;//to get the due amount pass the payment status id as 98
                $due_amount=$this->Invoices_model->getAmount($data);
                unset($data['payment_status']);
                $student_invoice_list=$this->Invoices_model->getStudentInvoiceList($data);//echo $this->db->last_query();exit;
                $total_invoices_amount=!empty($invoice_amount[0]['total_amount'])?(int)$invoice_amount[0]['total_amount']:0;
                $total_collected_amount=!empty($collected_amount[0]['total_amount'])?(int)$collected_amount[0]['total_amount']:0;
                $due_amount=!empty($due_amount[0]['total_amount'])?(int)$due_amount[0]['total_amount']:0;
                $invoices_count=!empty($invoice_amount[0]['count'])?(int)$invoice_amount[0]['count']:0;
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_list['data'],'total_records' =>$student_invoice_list['total_records'],'total_invoices_amount'=>$total_invoices_amount,'total_collected_amount'=>$total_collected_amount,'invoices_count'=>$invoices_count,'due_amount'=>$due_amount,'table_headers'=>getTableHeads('student_invoice_list')));

        }
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function getPreviousStudentInvoices_get(){
        //this function is used to get student previous invoices 
        $data=$this->input->get();
       if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('student_id', array('required'=>$this->lang->line('student_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $previous_invoice_data=$this->Invoices_model->getPreviousStudentInvoice($data);//echo $this->db->last_query();exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>!empty($previous_invoice_data)?$previous_invoice_data:array()));
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function updateStudentInvoicePayment_post(){
        $data=$this->input->post();
        if(empty($data)){
             $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
             $this->response($result, REST_Controller::HTTP_OK);
         }
         $this->form_validator->add_rules('student_invoice_id', array('required'=>$this->lang->line('student_invoice_id_req')));
         $this->form_validator->add_rules('status', array('required'=>$this->lang->line('payment_status_req')));
         $this->form_validator->add_rules('payment_type', array('required'=>$this->lang->line('payment_type_req')));

         $validated = $this->form_validator->validate($data);
         if($validated != 1)
         {
             $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
             $this->response($result, REST_Controller::HTTP_OK);
         }
         $update_data=array(
             'student_invoice_id'=>$data['student_invoice_id'],
             'payment_status'=>$data['status'],
             'payment_type'=>$data['payment_type'],
             'comments'=>!empty($data['comments'])?$data['comments']:'',
             'updated_by'=>$this->session_user_info->user_id,
             'update_on'=>currentDate(),
            );
         $histor_update=$this->User_model->insert_data('student_invoice_payment_history',$update_data);
         $student_invoice_update=$this->User_model->update_data('student_invoice',array('payment_status'=>$data['status'],'payment_mode'=>$data['payment_type']),array('id'=>$data['student_invoice_id']));
         if(isset($histor_update) && $student_invoice_update){
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('update_payment_status'), 'data'=>array('data'=>''));
            $this->response($result, REST_Controller::HTTP_OK);
         }
         else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
         }
    }
     public function generateStudentInvoice_get(){
        $data=$this->input->get();
        if(empty($data)){
             $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
             $this->response($result, REST_Controller::HTTP_OK);
         }
         $this->form_validator->add_rules('student_id', array('required'=>$this->lang->line('student_id_req')));
         $validated = $this->form_validator->validate($data);
         if($validated != 1)
         {
             $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
             $this->response($result, REST_Controller::HTTP_OK);
         }
         $student_data=$this->Invoices_model->getStudentInvoicedData($data);
        $date=date("Y-m-d");
        $day=date("d");
        if(!empty($student_data[0]['term'])){
            if($student_data[0]['term']==20){ //if term is 20 means it quarterly plan and generate quarterly invoice date
                if($day==1){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+3 month'));
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+4 month'));
                }
            }
            if($student_data[0]['term']==19){//if term is 19 means it quarterly plan and generate monthly invoice date
                if($day==1){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+1 month'));
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+2 month'));
                }
            }
            if($student_data[0]['term']==22){//if term is 22 means it quarterly plan and generate yearly invoice date
                if($day==1){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+12 month'));
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+13 month'));
                }
            }
            if($student_data[0]['term']==21){//if term is 21 means it quarterly plan and generate halfyearly invoice date
                if($day==1){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+6 month'));
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+7 month'));
                }
            }
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
            'created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
        );
        $franchise_name=str_replace(" ","",$student_data[0]['franchise_name']);
        $month=date("F");
        $year=date("Y");
        $update_student=$this->User_model->update_data('student',array('next_invoice_date'=>$next_invoice_date),array('id'=>$data['student_id']));
        $invoice_insert=$this->User_model->insert_data('student_invoice',$invoice_data);
        //  MIN/test/March/2020/00
         $id=str_pad($invoice_insert,6,"0",STR_PAD_LEFT);
        $invoice_number="MIN/".$franchise_name."/".$month."/".$year."/".$id;
        $this->User_model->update_data('student_invoice',array('invoice_number'=>$invoice_number),array('id'=>$invoice_insert));
        // print_r($invoice_number);exit;
        if(!empty($invoice_insert)){
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('invoice_generate'), 'data'=>array('data'=>$invoice_insert));
            $this->response($result, REST_Controller::HTTP_OK);
         }
         else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
         }
    }

}
