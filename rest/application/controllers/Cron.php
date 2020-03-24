<?php

defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);
require APPPATH . '/third_party/mailer/mailer.php';

class Cron extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Invoices_model');
    }

    public function studentinvoicegeneration(){
        //invoice_generation_status // From insert query
        //1 as invoice_generation_status // From Select query
        $query  =   "SET @a=0";
        $this->db->query($query);
        $query1='
        INSERT INTO  student_invoice (invoice_number,franchise_id,student_id,amount,tax,tax_amount,franchise_fee_id,invoice_date)
        SELECT CONCAT("MIN/",f.franchise_code,"/",MONTHNAME(CURDATE()),"/",YEAR(CURDATE()),"/",@a:=LPAD(@a+1, 6, 0)) invoice_number,f.id franchise_id,s.id student_id,fm.amount,(SELECT mc1.child_key from master_child mc1  WHERE mc1.master_id=21) as tax,(fm.amount-(fm.amount*fm.discount/100)+(fm.amount*fm.tax/100)) as tax_amount,s.franchise_fee_id as franchise_fee_id,CURRENT_DATE()invoice_date
        FROM student s
        LEFT JOIN franchise f ON s.franchise_id = f.id
        LEFT JOIN franchise_fee ff ON s.franchise_fee_id = ff.id
        LEFT JOIN fee_master fm ON ff.fee_master_id = fm.id
        WHERE s.status=1
        AND s.next_invoice_date = CURDATE()
        AND s.subscription_status = 1';
        $insert_rows=$this->User_model->custom_query_affected_rows($query1);
        $query2='UPDATE student s SET next_invoice_date =CASE 
        WHEN s.franchise_fee_id=1 THEN DATE_ADD(s.next_invoice_date, INTERVAL +6 MONTH) 
        WHEN s.franchise_fee_id=27 THEN DATE_ADD(s.next_invoice_date, INTERVAL +1 MONTH)
        WHEN s.franchise_fee_id=28 THEN DATE_ADD(s.next_invoice_date, INTERVAL +3 MONTH) 
        WHEN s.franchise_fee_id=29 THEN DATE_ADD(s.next_invoice_date, INTERVAL +12 MONTH)
        END
        WHERE s.status=1
        AND s.subscription_status = 1
        AND s.next_invoice_date = CURDATE()';
        $update_rows=$this->User_model->custom_query_affected_rows($query2);
        print_r($insert_rows);
        print_r($update_rows);
        exit;
    }
    
}   