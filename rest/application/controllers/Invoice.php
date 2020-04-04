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
    }

    public function studentInvoiceList_get(){//this service is used to get student invoices
        $data=$this->input->get();

        if(!empty($data['student_invoice_id'])){
            $student_invoice_payment_history=$this->Invoices_model->getStudentPaymentHistory(array('student_invoice_id'=>$data['student_invoice_id']));
            $student_invoice_payment_history=!empty( $student_invoice_payment_history)?$student_invoice_payment_history:array();
            $student_invoice_info=$this->Invoices_model->getStudentInvoiceList($data);//print_r($student_invoice_info['data'][0]);exit;
            
            if(!empty($student_invoice_info['data'] && $student_invoice_info['data'][0]['payment_status']==98 ||$student_invoice_info['data'][0]['payment_status']==100)){//if payment status= 98 then it is consider as invoice payment status in Due,similarly 100 then it is OverDue
                
                $due_date=$student_invoice_info['data'][0]['due_date']?$student_invoice_info['data'][0]['due_date']:'';
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_info['data'],'due_date'=>!empty($due_date)?$due_date:'','student_invoice_payment_history'=>$student_invoice_payment_history));
            } 
            else{
                $paid_date=$student_invoice_info['data'][0]['paid_date']?$student_invoice_info['data'][0]['paid_date']:'';
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_info['data'],'paid_date'=>$paid_date,'student_invoice_payment_history'=>$student_invoice_payment_history));
            }
        }
        else{
                if($this->session_user_info->user_role_id==2){
                    $data['franchise_id']=$this->session_user_info->franchise_id;
                }
                if($this->session_user_info->user_role_id==4){
                    $data['student_id']=$this->session_user_info->franchise_id;
                    $student_id=$this->User_model->check_record_selected('id as student_id','student',array('user_id'=>$this->session_user_info->user_id));
                    $data['student_id']=$student_id[0]['student_id'];
                }
                $data['status']=1;
                $invoice_amount=$this->Invoices_model->getAmount($data);
                $data['payment_status']=97;//to get collected amount  pass the payment status id is 97
                $collected_amount=$this->Invoices_model->getAmount($data);//print_r($collected_amount);exit;
                unset($data['payment_status']);
                $data['payment_status']=98;//to get the due amount pass the payment status id as 98
                $due_amount=$this->Invoices_model->getAmount($data);
                unset($data['payment_status']);
                $student_invoice_list=$this->Invoices_model->getStudentInvoiceList($data);
                $total_invoices_amount=!empty($invoice_amount[0]['total_amount'])?$invoice_amount[0]['total_amount']:0;
                $total_collected_amount=!empty($collected_amount[0]['paid_amount'])?$collected_amount[0]['paid_amount']:0;
                $due_amount=!empty($due_amount[0]['total_amount'])?$due_amount[0]['total_amount']:0;
                $invoices_count=!empty($invoice_amount[0]['count'])?(int)$invoice_amount[0]['count']:0;
                for ($i = 0; $i <= 5; $i++) 
                {
                   $months[$i]['label'] = date("M Y", strtotime( date( 'Y-m-01' )." -$i months"));
                   $months[$i]['value'] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
                
                }
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_list['data'],'total_records' =>$student_invoice_list['total_records'],'total_invoices_amount'=>$total_invoices_amount,'total_collected_amount'=>$total_collected_amount,'invoices_count'=>$invoices_count,'due_amount'=>$due_amount,'last_six_months'=>$months,'table_headers'=>getTableHeads('student_invoice_list')));

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
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $previous_invoice_data=$this->Invoices_model->getPreviousStudentInvoice($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>!empty($previous_invoice_data)?$previous_invoice_data:array()));
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function updateStudentInvoicePayment_post(){
        $data=$this->input->post();
        if(empty($data)){
             $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
             $this->response($result, REST_Controller::HTTP_OK);
         }
         $this->form_validator->add_rules('status', array('required'=>$this->lang->line('payment_status_req')));
         $validated = $this->form_validator->validate($data);
         if($validated != 1)
         {
             $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
             $this->response($result, REST_Controller::HTTP_OK);
         }
        //  print_r($data);exit;
         $update_data=array(
             'student_invoice_id'=>!empty($data['student_invoice_id'])?$data['student_invoice_id']:'0',
             'school_invoice_id'=>!empty($data['school_invoice_id'])?$data['school_invoice_id']:'0',
             'franchise_invoice_id'=>!empty($data['franchise_invoice_id'])?$data['franchise_invoice_id']:'0',
             'payment_status'=>$data['status'],
             'payment_type'=>isset($data['payment_type'])?$data['payment_type']:'',
             'comments'=>!empty($data['comments'])?$data['comments']:'',
             'updated_by'=>$this->session_user_info->user_id,
             'update_on'=>currentDate(),
            );
            if(!empty($data['student_invoice_id'])){$id=$data['student_invoice_id'];}
            if(!empty($data['school_invoice_id'])){$id=$data['school_invoice_id'];}
            if(!empty($data['franchise_invoice_id'])){$id=$data['franchise_invoice_id'];}
         $histor_update=$this->User_model->insert_data('student_invoice_payment_history',$update_data);
         $student_invoice_update=$this->User_model->update_data('student_invoice',array('payment_status'=>$data['status'],'payment_mode'=>isset($data['payment_type'])?$data['payment_type']:'','paid_date'=>$data['status']==97?currentDate():'','update_on'=>currentDate(),'update_by'=>$this->session_user_info->user_id,'paid_amount'=>!empty($data['paid_amount'])?$data['paid_amount']:0),array('id'=>$id));
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
            $term_type=$this->User_model->getTermTypeKey(array('term_id'=>$student_data[0]['term']));
            if($term_type[0]['child_key']==QUARTERYL_TERM_KEY){ //if term is quarterly plan then generate quarterly invoice date
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+3 month'));
                     $remaining_days='0';
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+4 month'));
                     $remaining_days='0';

                }
            }
            if($term_type[0]['child_key']==MONTHLY_TERM_KEY){//if term is  monthly plan then generate monthly invoice date
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+1 month'));
                     $remaining_days='0';
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+2 month'));
                     $curren_date=date_create(date("Y-m-d"));
                     $end_of_month_date=date_create(date("Y-m-t"));
                     $diff_days=date_diff($curren_date,$end_of_month_date);
                     $remaining_days='0';
                }
            }
            if($term_type[0]['child_key']==ANNUAL_TERM_KEY){//if  yearly plan then generate yearly invoice date
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+12 month'));
                     $remaining_days='0';
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+13 month'));
                     $remaining_days='0';
                }
            }
            if($term_type[0]['child_key']==HALFYEARLY_TERM_KEY){//if term is  halfyearly  plan then generate halfyearly invoice date
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+6 month'));
                     $remaining_days='0';
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+7 month'));
                     $remaining_days='0';
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
            'discount_amount'=>$student_data[0]['discount_amount'],
            'tax_amount'=>$student_data[0]['tax_amount'],
            'due_date'=>date('Y-m-d', strtotime($date .'+'.$student_data[0]['due_days'].'days')),
            'created_on'=>CurrentDate(),
            'invoice_type'=>1,
            'paid_amount'=>0
        );
        $franchise_name=str_replace(" ","",$student_data[0]['franchise_code']);
        $month=date("m");
        $year=date("Y");
        $update_student=$this->User_model->update_data('student',array('next_invoice_date'=>!empty($next_invoice_date)?$next_invoice_date:'','remaining_invoice_days'=>!empty($remaining_days)?$remaining_days:0),array('id'=>$data['student_id']));
        $invoice_insert=$this->User_model->insert_data('student_invoice',$invoice_data);
         $id=str_pad($invoice_insert,6,"0",STR_PAD_LEFT);
        $invoice_number="MIN/".$franchise_name."/".$year."/".$month."/".$id;
        $this->User_model->update_data('student_invoice',array('invoice_number'=>$invoice_number),array('id'=>$invoice_insert));
        if(!empty($invoice_insert)){
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('invoice_generate'), 'data'=>array('data'=>$invoice_insert));
            $this->response($result, REST_Controller::HTTP_OK);
         }
         else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
         }
    }

    public function generateSchoolInvoice_post(){//this function is used to generate school invoice
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('school_id', array('required'=>$this->lang->line('school_id_req')));
        $this->form_validator->add_rules('amount', array('required'=>$this->lang->line('amount_req')));

        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }


        $schooldata=$this->Invoices_model->getSchoolData(array('school_id'=>$data['school_id']));
        $tax=!empty($data['tax'])?$data['tax']:0;
        $discount=!empty($data['discount'])?$data['discount']:0;
        $tax_amount=$data['amount']*$tax/100;
        $discount_amount=$data['amount']*$discount/100;
        $total_amount=($data['amount']-$discount_amount)+$tax_amount;
        $school_invoice_data=array(
            'school_id'=>$data['school_id'],
            'franchise_id'=>!empty($schooldata[0]['franchise_id'])?$schooldata[0]['franchise_id']:0,
            'school_manual_invoice_id'=>!empty($data['school_manual_invoice_id'])?$data['school_manual_invoice_id']:'',
            'school_invoice_description'=>!empty($data['school_invoice_description'])?$data['school_invoice_description']:'',
            'amount'=>$data['amount'],
            'tax'=>$tax,
            'discount'=>$discount,
            'tax_amount'=>$tax_amount,
            'discount_amount'=>$discount_amount,
            'total_amount'=>$total_amount,
            'invoice_date'=>date("Y-m-d"),
            'created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
            'created_on'=>currentDate(),
            'invoice_type'=>2,
            'paid_amount'=>0
        );
        $insert_id=$this->User_model->insert_data('student_invoice',$school_invoice_data);
        $school_code=str_replace(" ","",$schooldata[0]['school_code']);
        $franchise_code=str_replace(" ","",$schooldata[0]['franchise_code']);
        $id=str_pad($insert_id,6,"0",STR_PAD_LEFT);
        $student_invoice_number='MIN/'.$franchise_code.'/'.$school_code.'/'.date("Y").'/'.date("m").'/'.$id;
        $this->User_model->update_data('student_invoice',array('invoice_number'=>$student_invoice_number),array('id'=>$insert_id));
        if(!empty($insert_id)){
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('school_invoice_generate'), 'data'=>array('data'=>$insert_id));
            $this->response($result, REST_Controller::HTTP_OK);
         }
         else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
         }

    }
    public function schoolInvoiceList_get(){//this function is get school invoice list
        $data=$this->input->get();
        if(isset($data['school_invoice_id'])){
            $data['status']=2;
            $school_invoice_list=$this->Invoices_model->getSchoolInvoiceList($data);
            $school_invoice_payment_history=$this->Invoices_model->getStudentPaymentHistory(array('school_invoice_id'=>$data['school_invoice_id']));
            $school_invoice_payment_history=!empty( $school_invoice_payment_history)?$school_invoice_payment_history:array();
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$school_invoice_list['data'],'school_invoice_payment_history'=>$school_invoice_payment_history));
        }
        else{

            if($this->session_user_info->user_role_id==2){
                $data['franchise_id']=$this->session_user_info->franchise_id;
            }
            $data['status']=2;//for school invoice
            $invoice_amount=$this->Invoices_model->getAmount($data);
            $data['payment_status']=97;//to get collected amount  pass the payment status id is 97
            $collected_amount=$this->Invoices_model->getAmount($data);
            unset($data['payment_status']);
            $data['payment_status']=98;//to get the due amount pass the payment status id as 98
            $due_amount=$this->Invoices_model->getAmount($data);
            unset($data['payment_status']);
            $school_invoice_list=$this->Invoices_model->getSchoolInvoiceList($data);
            $total_invoices_amount=!empty($invoice_amount[0]['total_amount'])?$invoice_amount[0]['total_amount']:0;
            $total_collected_amount=!empty($collected_amount[0]['paid_amount'])?$collected_amount[0]['paid_amount']:0;
            $due_amount=!empty($due_amount[0]['total_amount'])?$due_amount[0]['total_amount']:0;
            $invoices_count=!empty($invoice_amount[0]['count'])?(int)$invoice_amount[0]['count']:0;
            for ($i = 0; $i <= 5; $i++) 
            {
               $months[$i]['label'] = date("M Y", strtotime( date( 'Y-m-01' )." -$i months"));
               $months[$i]['value'] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
            
            }
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$school_invoice_list['data'],'total_records' =>$school_invoice_list['total_records'],'total_invoices_amount'=>$total_invoices_amount,'total_collected_amount'=>$total_collected_amount,'invoices_count'=>$invoices_count,'due_amount'=>$due_amount,'last_six_months'=>$months,'table_headers'=>getTableHeads('school_invoice_list')));
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function FrachiseInvoiceList_get(){// this function is used to get the franchise invoices list
        $data=$this->input->get();
        if(isset($data['franchise_invoice_id'])){
            $data['status']=3;
            $school_invoice_list=$this->Invoices_model->getFrachiseInvoiceList($data);
            $school_invoice_payment_history=$this->Invoices_model->getStudentPaymentHistory(array('franchise_invoice_id'=>$data['franchise_invoice_id']));
            $school_invoice_payment_history=!empty( $school_invoice_payment_history)?$school_invoice_payment_history:array();
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$school_invoice_list['data'],'school_invoice_payment_history'=>$school_invoice_payment_history));
        }
        else{

            if($this->session_user_info->user_role_id==2){
                $data['franchise_id']=$this->session_user_info->franchise_id;
            }
            $data['status']=3;//for franchise invoice
            $invoice_amount=$this->Invoices_model->getAmount($data);
            $data['payment_status']=97;//to get collected amount  pass the payment status id is 97
            $collected_amount=$this->Invoices_model->getAmount($data);
            unset($data['payment_status']);
            $data['payment_status']=98;//to get the due amount pass the payment status id as 98
            $due_amount=$this->Invoices_model->getAmount($data);
            unset($data['payment_status']);
            $school_invoice_list=$this->Invoices_model->getFrachiseInvoiceList($data);
            $total_invoices_amount=!empty($invoice_amount[0]['total_amount'])?$invoice_amount[0]['total_amount']:0;
            $total_collected_amount=!empty($collected_amount[0]['paid_amount'])?$collected_amount[0]['paid_amount']:0;
            $due_amount=!empty($due_amount[0]['total_amount'])?$due_amount[0]['total_amount']:0;
            $invoices_count=!empty($invoice_amount[0]['count'])?(int)$invoice_amount[0]['count']:0;
            for ($i = 0; $i <= 5; $i++) 
            {
               $months[$i]['label'] = date("M Y", strtotime( date( 'Y-m-01' )." -$i months"));
               $months[$i]['value'] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
            
            }
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$school_invoice_list['data'],'total_records' =>$school_invoice_list['total_records'],'total_invoices_amount'=>$total_invoices_amount,'total_collected_amount'=>$total_collected_amount,'invoices_count'=>$invoices_count,'due_amount'=>$due_amount,'last_six_months'=>$months,'table_headers'=>getTableHeads('franchise_invoice_list')));
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }

}
