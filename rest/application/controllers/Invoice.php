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
        $student_invoice_list=$this->Invoices_model->getStudentInvoiceList($data);
        $total_invoices_amount=!empty($invoice_amount[0]['total_amount'])?(int)$invoice_amount[0]['total_amount']:0;
        $total_collected_amount=!empty($collected_amount[0]['total_amount'])?(int)$collected_amount[0]['total_amount']:0;
        $due_amount=!empty($due_amount[0]['total_amount'])?(int)$due_amount[0]['total_amount']:0;
        $invoices_count=!empty($invoice_amount[0]['count'])?(int)$invoice_amount[0]['count']:0;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$student_invoice_list['data'],'total_records' =>$student_invoice_list['total_records'],'total_invoices_amount'=>$total_invoices_amount,'total_collected_amount'=>$total_collected_amount,'invoices_count'=>$invoices_count,'table_headers'=>getTableHeads('student_invoice_list')));
        $this->response($result, REST_Controller::HTTP_OK);
    }


}
