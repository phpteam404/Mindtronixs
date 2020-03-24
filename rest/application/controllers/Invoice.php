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
            $student_invoice_payment_history=$this->Invoices_model->getStudentPaymentHistory(array('student_invoice_id'=>$data['student_invoice_id']));
            $student_invoice_payment_history=!empty( $student_invoice_payment_history)?$student_invoice_payment_history:array();
            $student_invoice_info=$this->Invoices_model->getStudentInvoiceList($data);//echo $this->db->last_query();exit;
            if(!empty($student_invoice_info['data'][0] && $student_invoice_info['data'][0]['payment_status']==98 ||$student_invoice_info['data'][0]['payment_status']==100)){
                $date=date("Y-m-d");
                if($student_invoice_info['data'][0]['term']==19){
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['invoice_date'] .'+10 day')); 
                }
                if($student_invoice_info['data'][0]['term']==21){
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['invoice_date'] .'+1 month')); 
                }
                if($student_invoice_info['data'][0]['term']==20){
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['invoice_date'] .'+15 day')); 
                }
                if($student_invoice_info['data'][0]['term']==22){
                    $due_date= date('Y-m-d', strtotime($student_invoice_info['data'][0]['invoice_date'] .'+1 month')); 
                }
                $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_info['data'],'due_date'=>$due_date,'student_invoice_payment_history'=>$student_invoice_payment_history));
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
        //this function is used to get student previous invoices $data['student_invoice_id']
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
             'comments'=>$data['comments'],
             'updated_by'=>$this->session_user_info->user_id,
             'update_on'=>currentDate(),
            );
           // print_r($update_data);exit;
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


}
