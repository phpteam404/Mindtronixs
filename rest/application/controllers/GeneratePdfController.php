<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH.'/libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
class GeneratePdfController extends REST_Controller {
    public function __construct()
    {
        parent::__construct();
    }
    //* generating pdf for all invoices start *//
    public function generateinvoicepdf_get()
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(!empty($data['student_invoice_id']) || $data['onlineuser_invoice_id']){// to get student/online user information to generate pdf
            $student_invoice_data=$this->Invoices_model->getStudentInvoiceInfo(array('student_invoice_id'=>!empty($data['student_invoice_id'])?$data['student_invoice_id']:$data['onlineuser_invoice_id'])); 
            $invoice_number=!empty($student_invoice_data[0]['invoice_number'])?$student_invoice_data[0]['invoice_number']:'';
            $amount=!empty($student_invoice_data[0]['amount'])?number_format($student_invoice_data[0]['amount']):0;
            $tax=!empty($student_invoice_data[0]['tax'])?$student_invoice_data[0]['tax']:0;
            $tax_amount=!empty($student_invoice_data[0]['tax_amount'])?number_format($student_invoice_data[0]['tax_amount']):0;
            $discount=!empty($student_invoice_data[0]['discount'])?$student_invoice_data[0]['discount']:0;
            $discount_amount=!empty($student_invoice_data[0]['discount_amount'])?number_format($student_invoice_data[0]['discount_amount']):0;
            $total_amount=!empty($student_invoice_data[0]['total_amount'])?number_format($student_invoice_data[0]['total_amount']):0;
            $invoice_date=!empty($student_invoice_data[0]['invoice_date'])?$student_invoice_data[0]['invoice_date']:'';
            $due_date=!empty($student_invoice_data[0]['due_date'])?$student_invoice_data[0]['due_date']:'';
            $user_name=!empty($student_invoice_data[0]['user_name'])?$student_invoice_data[0]['user_name']:'';
            $email=!empty($student_invoice_data[0]['email'])?$student_invoice_data[0]['email']:'';
            $address=!empty($student_invoice_data[0]['address'])?$student_invoice_data[0]['address']:'';
            $phone_no=!empty($student_invoice_data[0]['phone_no'])?$student_invoice_data[0]['phone_no']:'';
            $description=!empty($student_invoice_data[0]['description'])?$student_invoice_data[0]['description']:'';
            // $file_name=trim($user_name)."_".date("M")."_".date("Y");
            $filename=!empty($student_invoice_data[0]['invoice_number'])?str_replace("/","_",$student_invoice_data[0]['invoice_number']):''; 
         }
        if(!empty($data['school_invoice_id'])){// to get school information to generate pdf
            $school_invoice_data=$this->Invoices_model->getSchoolInvoiceInfo(array('school_invoice_id'=>$data['school_invoice_id']));
            $invoice_number=!empty($school_invoice_data[0]['invoice_number'])?$school_invoice_data[0]['invoice_number']:'';
            $amount=!empty($school_invoice_data[0]['amount'])?number_format($school_invoice_data[0]['amount']):0;
            $tax=!empty($school_invoice_data[0]['tax'])?$school_invoice_data[0]['tax']:0;
            $tax_amount=!empty($school_invoice_data[0]['tax_amount'])?number_format($school_invoice_data[0]['tax_amount']):0;
            $discount=!empty($school_invoice_data[0]['discount'])?$school_invoice_data[0]['discount']:0;
            $discount_amount=!empty($school_invoice_data[0]['discount_amount'])?number_format($school_invoice_data[0]['discount_amount']):0;
            $total_amount=!empty($school_invoice_data[0]['total_amount'])?number_format($school_invoice_data[0]['total_amount']):0;
            $invoice_date=!empty($school_invoice_data[0]['invoice_date'])?$school_invoice_data[0]['invoice_date']:'';
            $user_name=!empty($school_invoice_data[0]['user_name'])?$school_invoice_data[0]['user_name']:'';
            $email=!empty($school_invoice_data[0]['email'])?$school_invoice_data[0]['email']:'';
            $phone_no=!empty($school_invoice_data[0]['phone'])?$school_invoice_data[0]['phone']:'';
            $address=!empty($school_invoice_data[0]['address'])?$school_invoice_data[0]['address']:'';
            $due_date=!empty($school_invoice_data[0]['due_date'])?$school_invoice_data[0]['due_date']:'';
            $description=!empty($school_invoice_data[0]['description'])?$school_invoice_data[0]['description']:'';
            // $file_name=trim($user_name)."_".date("M")."_".date("Y");
            $filename=!empty($school_invoice_data[0]['invoice_number'])?str_replace("/","_",$school_invoice_data[0]['invoice_number']):''; 
         }
         if(!empty($data['franchise_invoice_id'])){// to get franchise  information to generate pdf
            $franchise_invoice_data=$this->Invoices_model->getFranchiseInvoiceInfo(array('franchise_invoice_id'=>$data['franchise_invoice_id']));
            $invoice_number=!empty($franchise_invoice_data[0]['invoice_number'])?$franchise_invoice_data[0]['invoice_number']:'';
            $amount=!empty($franchise_invoice_data[0]['amount'])?$franchise_invoice_data[0]['amount']:0;
            $tax=!empty($franchise_invoice_data[0]['tax'])?$franchise_invoice_data[0]['tax']:0;
            $tax_amount=!empty($franchise_invoice_data[0]['tax_amount'])?number_format($franchise_invoice_data[0]['tax_amount']):0;
            $discount=!empty($franchise_invoice_data[0]['discount'])?$franchise_invoice_data[0]['discount']:0;
            $discount_amount=!empty($franchise_invoice_data[0]['discount_amount'])?number_format($franchise_invoice_data[0]['discount_amount']):0;
            $total_amount=!empty($franchise_invoice_data[0]['total_amount'])?number_format($franchise_invoice_data[0]['total_amount']):0;
            $invoice_date=!empty($franchise_invoice_data[0]['invoice_date'])?$franchise_invoice_data[0]['invoice_date']:'';
            $due_date=!empty($franchise_invoice_data[0]['due_date'])?$franchise_invoice_data[0]['due_date']:'';
            $user_name=!empty($franchise_invoice_data[0]['franchise_name'])?$franchise_invoice_data[0]['franchise_name']:'';
            $email=!empty($franchise_invoice_data[0]['email'])?$franchise_invoice_data[0]['email']:'';
            $phone_no=!empty($franchise_invoice_data[0]['primary_contact'])?$franchise_invoice_data[0]['primary_contact']:'';
            $address=!empty($franchise_invoice_data[0]['address'])?$franchise_invoice_data[0]['address']:'';
            $description=!empty($franchise_invoice_data[0]['description'])?$franchise_invoice_data[0]['description']:''; 
            // $file_name=trim($user_name)."_".date("M")."_".date("Y");
            $filename=!empty($franchise_invoice_data[0]['invoice_number'])?str_replace("/","_",$franchise_invoice_data[0]['invoice_number']):''; 

         }
         $stream=TRUE; $paper = 'A4'; $orientation = "portrait";
        //  print_r($filename);exit;
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
                    width:45%;
                }
                .main-head-rightblock{
                     text-align: right;
                     float:left;
                     width: 55%;
                }
                .clearfix::after {
                    content: "";
                    clear: both;
                    display: table;
                }
                .invoice-list table thead th,
                .invoice-list table tbody td{
                    border:1px solid #cccccc;
                    padding: 5px;
                    height: 15px;
                }
                .invoice-list tr:nth-child(even) {
                    background: #f7f7f7;
                }
                /* tfoot{  
                    border-top: 1px solid #cccccc;
                } */
                .total-invoice-list tr td{
                    text-align: right;
                    padding: 5px;
                    width: 100px;
                }
              
                .total-invoice-list tr td:last-child{
                    border-bottom:1px solid #cccccc;
                }
                
                .total-invoice-list tr td:first-child{
                    width: 250px;
                }
            
                </style>
            </head>
        <body>
        <div style="max-width: 1160px;margin: 0 auto;">
            <div class="main-head clearfix">
                <div class="main-head-leftblock">
                    <h2 style="margin: 0;color: #000000;">Mindtronix</h2>
                    <h4 style="margin: 0;color: #000000;">GSTIN: 37AAKCM6507B2ZK</h4>
                    <p style="margin: 10px 0px;color: #555555;">KT Rd, Srinivasa Nagar,<br> Khadi Colony, Tirupati,<br> Andhra Pradesh 517501</p>
                </div>
                <div class="main-head-rightblock">
                    <h3 style="color: #000000;margin: 0;">INVOICE</h3>
                    <p><img src="http://139.59.59.231/Mindtronix_test/assets/img/logo.png" alt="" style="width: 80px;"></p>
                </div>
            </div>
            <div class="clearfix">
                <div class="main-head-leftblock">
                    <h2 style="margin: 0;color: #000000;border-bottom:1px solid #cccccc;font-size: 16px;">Bill to</h2>
                    <p style="margin: 5px 0px 0px;">'.$user_name.'</p>
                    <p style="margin: 5px 0px 0px;">'.$address.'</p>
                    <p style="margin: 5px 0px 0px;">'.$email.'</p>
                    <p style="margin: 5px 0px 0px;">'.$phone_no.'</p>
                </div>
                <div class="main-head-rightblock">
                   <!-- <p><span style="width: 100px;text-align: right;">Invoice No: &nbsp;</span><span></span></p>
                   <p><span style="width: 100px;text-align: right;">Invoice Date: &nbsp;</span><span></span></p>
                   <p><span style="width: 100px;text-align: right;">Due Date: &nbsp;</span><span></span></p> -->
                   <table style="width:100%">
                       <tr>
                           <td style="text-align: right;width:120px;">Invoice No:</td>
                           <td style="text-align: right;">'.$invoice_number.'</td>
                       </tr>
                       <tr>
                           <td style="text-align: right;width:120px;">Invoice Date:</td>
                           <td style="text-align: right;">'.$invoice_date.'</td>
                        </tr>
                        <tr>
                            <td style="text-align: right;width:120px;">Due Date:</td>
                            <td style="text-align: right;">'.$due_date.'</td>
                        </tr>
                   </table>
                </div>
            </div>
            <div style="margin-top: 30px;">
                <div class="invoice-list">
                <table style="width:100%;border-collapse: collapse;">
                    <thead>
                    <tr>
                      <th>DESCRIPTION</th>
                      <th style="width: 100px;">QTY</th> 
                      <th style="width: 100px;">UNIT PRICE</th>
                      <th style="width: 100px;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                      <td>'.$description.'</td>
                      <td style="text-align: center;">1</td>
                      <td style="text-align: right;">'.$amount.'</td>
                      <td style="text-align: right;">'.$amount.'</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                      <tr>
                        <td></td>
                        <td style="text-align: center;"></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"></td>
                      </tr>
                </tbody>
            
                  </table>
                </div>
                <div class="total-invoice-list">
                  <table style="width:100%;border-collapse: collapse;">
                    <tr>
                        <th colspan="2" rowspan="7">
                            <p>Thank you for your business!</p>
                        </th>

                        </tr>
                        <tr>
                            <td>Sub total</td>
                            <td>'.$amount.'</td>
                          </tr>
                          <tr>
                            <td>Discount(%)</td>
                            <td>'.$discount.'</td>
                          </tr>
                          <tr>
                            <td>Discount amount</td>
                            <td>'.$discount_amount.'</td>
                          </tr>
                          <tr>
                            <td>Tax(%)</td>
                            <td>'.$tax.'</td>
                          </tr>
                          <tr>
                            <td>Tax amount</td>
                            <td>'.$tax_amount.'</td>
                          </tr>
                          <tr>
                            <td>Balance due</td>
                            <td>'.$total_amount.'</td>
                          </tr>

                  </table>
                </div>
            </div>
            
            <div>
                <h3 style="margin:0px;font-size:18px;border-bottom: 1px solid #cccccc;width: 30%;">Terms & Instructions</h3>
                <p style="margin:5px 0px 0px;">Please pay within 20 Days.</p>
            </div>
        </div>
        </body>
        </html>';
        if(!is_dir('downloads/')){ mkdir('downloads/'); }
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $dompdf = new Dompdf($options);
        // $dompdf = new Dompdf();
        // $contxt = stream_context_create([ 
        // 'ssl' => [ 
        //     'verify_peer' => FALSE, 
        //     'verify_peer_name' => FALSE,
        //     'allow_self_signed'=> TRUE
        //     ] 
        // ]);
        // $dompdf->setHttpContext($contxt);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        // $dompdf->stream("downloads/".$filename.".pdf", array("Attachment" => 1));
        $output = $dompdf->output();
        file_put_contents('downloads/'.$filename.".pdf", $output);
        if(!empty($invoice_number)){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('filename'=>$filename,'file_url'=>WEB_BASE_URL.'rest/downloads/'.$filename.'.pdf'));
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }
    //* generating pdf for all invoices(student/) end *//
}
?>