<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH.'/libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
class GeneratePdfController extends REST_Controller {
    // public function __construct()
    // {
    //     // print_r(APPPATH.'/libraries/mpdf/mpdf.php');exit;
    //     // $this->load->library('tcpdf');
    //     parent::__construct();
    // }

    public function generateinvoicepdf_get()
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(!empty($data['student_invoice_id']) || $data['online_user_invoice_id']){
            $student_invoice_data=$this->Invoices_model->getStudentInvoiceInfo(array('student_invoice_id'=>!empty($data['student_invoice_id'])?$data['student_invoice_id']:$data['online_user_invoice_id']));
            $invoice_number=!empty($student_invoice_data[0]['invoice_number'])?$student_invoice_data[0]['invoice_number']:'';
            $amount=!empty($student_invoice_data[0]['amount'])?$student_invoice_data[0]['amount']:0;
            $tax=!empty($student_invoice_data[0]['tax'])?$student_invoice_data[0]['tax']:0;
            $tax_amount=!empty($student_invoice_data[0]['tax_amount'])?$student_invoice_data[0]['tax_amount']:0;
            $disount=!empty($student_invoice_data[0]['disount'])?$student_invoice_data[0]['disount']:0;
            $discount_amount=!empty($student_invoice_data[0]['discount_amount'])?$student_invoice_data[0]['discount_amount']:0;
            $total_amount=!empty($total_amount['total_amount'])?$student_invoice_data[0]['total_amount']:0;
            $invoice_date=!empty($student_invoice_data[0]['invoice_date'])?$student_invoice_data[0]['invoice_date']:'';
            $due_date=!empty($student_invoice_data[0]['due_date'])?$student_invoice_data[0]['due_date']:'';
            $user_name=!empty($student_invoice_data[0]['user_name'])?$student_invoice_data[0]['user_name']:'';
            $email=!empty($student_invoice_data[0]['email'])?$student_invoice_data[0]['email']:'';
            $address=!empty($student_invoice_data[0]['address'])?$student_invoice_data[0]['address']:'';
            $phone_no=!empty($student_invoice_data[0]['phone_no'])?$student_invoice_data[0]['phone_no']:'';
            $description=!empty($student_invoice_data[0]['description'])?$student_invoice_data[0]['description']:'';
            print_r($student_invoice_data);exit;
         }
        if(!empty($data['school_invoice_id'])){
            $school_invoice_data=$this->Invoices_model->getSchoolInvoiceInfo(array('school_invoice_id'=>$data['school_invoice_id']));
            $invoice_number=!empty($school_invoice_data[0]['invoice_number'])?$school_invoice_data[0]['invoice_number']:'';
            $amount=!empty($school_invoice_data[0]['amount'])?$school_invoice_data[0]['amount']:0;
            $tax=!empty($school_invoice_data[0]['tax'])?$school_invoice_data[0]['tax']:0;
            $tax_amount=!empty($school_invoice_data[0]['tax_amount'])?$school_invoice_data[0]['tax_amount']:0;
            $discount=!empty($school_invoice_data[0]['discount'])?$school_invoice_data[0]['discount']:0;
            $discount_amount=!empty($school_invoice_data[0]['discount_amount'])?$school_invoice_data[0]['discount_amount']:0;
            $total_amount=!empty($school_invoice_data[0]['total_amount'])?$school_invoice_data[0]['total_amount']:0;
            $invoice_date=!empty($school_invoice_data[0]['invoice_date'])?$school_invoice_data[0]['invoice_date']:'';
            $user_name=!empty($school_invoice_data[0]['user_name'])?$school_invoice_data[0]['user_name']:'';
            $email=!empty($school_invoice_data[0]['email'])?$school_invoice_data[0]['email']:'';
            $phone_no=!empty($school_invoice_data[0]['phone'])?$school_invoice_data[0]['phone']:'';
            $address=!empty($school_invoice_data[0]['address'])?$school_invoice_data[0]['address']:'';
            $due_date=!empty($school_invoice_data[0]['due_date'])?$school_invoice_data[0]['due_date']:'';
            print_r($user_name);exit;
         }
         if(!empty($data['franchise_invoice_id'])){
            $franchise_invoice_data=$this->Invoices_model->getFranchiseInvoiceInfo(array('franchise_invoice_id'=>$data['franchise_invoice_id']));
            print_r($franchise_invoice_data);exit;
            $invoice_number=!empty($franchise_invoice_data[0]['invoice_number'])?$franchise_invoice_data[0]['invoice_number']:'';
            $amount=!empty($franchise_invoice_data[0]['amount'])?$franchise_invoice_data[0]['amount']:0;
            $tax=!empty($franchise_invoice_data[0]['tax'])?$franchise_invoice_data[0]['tax']:0;
            $tax_amount=!empty($franchise_invoice_data[0]['tax_amount'])?$franchise_invoice_data[0]['tax_amount']:0;
            $disount=!empty($franchise_invoice_data[0]['disount'])?$franchise_invoice_data[0]['disount']:0;
            $discount_amount=!empty($franchise_invoice_data[0]['discount_amount'])?$franchise_invoice_data[0]['discount_amount']:0;
            $total_amount=!empty($franchise_invoice_data[0]['total_amount'])?$franchise_invoice_data[0]['total_amount']:0;
            $invoice_date=!empty($franchise_invoice_data[0]['invoice_date'])?$franchise_invoice_data[0]['invoice_date']:'';
            $due_date=!empty($franchise_invoice_data[0]['due_date'])?$franchise_invoice_data[0]['due_date']:'';
            $user_name=!empty($franchise_invoice_data[0]['franchise_name'])?$franchise_invoice_data[0]['franchise_name']:'';
            $email=!empty($franchise_invoice_data[0]['email'])?$franchise_invoice_data[0]['email']:'';
            $phone_no=!empty($franchise_invoice_data[0]['primary_contact'])?$franchise_invoice_data[0]['primary_contact']:'';
            $address=!empty($franchise_invoice_data[0]['address'])?$franchise_invoice_data[0]['address']:'';
           
         }


     $filename='data'; $stream=TRUE; $paper = 'A4'; $orientation = "portrait";
    // $name='Sri Rama';
    $html='<!DOCTYPE html>
    <html>
<head>
<title></title>
<style>
    body{
        font-family: sans-serif;
        font-size: 14px;
    }
    .main-head-leftblock{
        float: left;
        display:inline-block;
        width:50%;
    }
    .main-head-rightblock{
         text-align: right;
         float:left;
         width: 50%;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
    table thead th,
    table tbody td{
        border:1px solid #cccccc;
        padding: 5px;
    }
    
    tfoot{  
        border-top: 1px solid #cccccc;
    }
    tfoot tr td{
        text-align: right;
        padding: 5px;
    }
    tfoot tr td:last-child{
        border-bottom:1px solid #cccccc;
    }
    </style>
</head>
<body>
<div style="max-width: 1160px;margin: 0 auto;">
    <div class="main-head clearfix">
        <div class="main-head-leftblock">
            <h2 style="margin: 0;color: #000000;">Mindtronix</h2>
            <p style="margin: 10px 0px;color: #555555;">KT Rd, Srinivasa Nagar,<br> Khadi Colony, Tirupati,<br> Andhra Pradesh 517501</p>
        </div>
        <div class="main-head-rightblock">
            <h3 style="color: #000000;margin: 0;">INVOICE</h3>
            <p><img src="images/logo.png" alt="" style="width: 80px;"></p>
        </div>
    </div>
    <div class="clearfix">
        <div class="main-head-leftblock">
            <h2 style="margin: 0;color: #000000;border-bottom:1px solid #cccccc;font-size: 16px;">BILL TO</h2>
            <p style="margin: 5px 0px 0px;">Allen smith</p>
            <p style="margin: 5px 0px 0px;">87 Private st, Seattle, Wa</p>
            <p style="margin: 5px 0px 0px;">allen@gmail.com</p>
            <p style="margin: 5px 0px 0px;">990-302-1898</p>
        </div>
        <div class="main-head-rightblock">
           <p><span style="width: 100px;text-align: right;">Invoice No: &nbsp;</span><span>#MI0000001</span></p>
           <p><span style="width: 100px;text-align: right;">Invoice Date: &nbsp;</span><span>15/04/20</span></p>
           <p><span style="width: 100px;text-align: right;">Due Date: &nbsp;</span><span>11/10/20</span></p>
        </div>
    </div>
    <div style="margin-top: 30px;">
        <table style="width:100%;border-collapse: collapse;">
            <thead>
            <tr>
              <th>DESCRIPTION</th>
              <th>QTY / HR</th> 
              <th>UNIT PRICE</th>
              <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
              <td>Toto sink</td>
              <td>1</td>
              <td style="text-align: right;border-bottom: 1px solid #ccc;">500.00</td>
              <td style="text-align: right;border-bottom: 1px solid #ccc;">500.00</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
            <th colspan="2" rowspan="7">
                <p>Thank you for your business!</p>
            </th>
            
           
            </tr>
            <tr>
                <td>SUBTOTAL</td>
                <td>2590.00</td>
              </tr>
              <tr>
                <td>DISCOUNT</td>
                <td>50.00</td>
              </tr>
              <tr>
                <td>SUBTOTAL LESS DISCOUNT</td>
                <td>2540.00</td>
              </tr>
              <tr>
                <td>TAX RATE</td>
                <td>12.00%</td>
              </tr>
              <tr>
                <td>TOTAL TAX</td>
                <td>304.40</td>
              </tr>
              <tr>
                <td>Balance Due</td>
                <td>$ 2,844.80</td>
              </tr>
                        
            </tfoot>
          </table>
    </div>
    
    <div>
        <h3 style="margin:0px;font-size:18px;border-bottom: 1px solid #cccccc;width: 30%;">Terms & Instructions</h3>
        <p style="margin:5px 0px 0px;">Please pay within 20 Days by PayPal (bob@stanfordplumbing.com)</p>
        <p style="margin:5px 0px 0px;">installed products have 5 years warranty.</p>
    </div>
</div>
</body>
</html>';
    //   print_r($html);exit;
      $dompdf = new DOMPDF();
      $dompdf->loadHtml($html);
      $dompdf->setPaper($paper, $orientation);
      $dompdf->render();
      $dompdf->stream($filename.".pdf", array("Attachment" => 1));

    }
}
?>