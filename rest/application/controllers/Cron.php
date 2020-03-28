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
        INSERT INTO  student_invoice (invoice_number,franchise_id,student_id,amount,tax,total_amount,franchise_fee_id,invoice_date,discount,discount_amount,tax_amount,created_on,created_by,due_date)
        SELECT CONCAT("MIN/",f.franchise_code,"/",YEAR(CURDATE()),"/",DATE_FORMAT(CURDATE(),"%m"),"/",@a:=LPAD(@a+1, 6, 0)) invoice_number,f.id franchise_id,s.id student_id,fm.amount,(SELECT mc1.child_key from master_child mc1  WHERE mc1.master_id=21) as tax,(fm.amount-(fm.amount*fm.discount/100)+(fm.amount*fm.tax/100)) as total_amount,s.franchise_fee_id as franchise_fee_id,CURRENT_DATE()invoice_date,fm.discount,TRIM((fm.amount*fm.discount/100))+0 as discount_amount,TRIM(fm.amount*fm.tax/100)+0 as tax_amount,CURRENT_DATE() as created_on, 1 as created_by,DATE_ADD(CURDATE(), INTERVAL fm.due_days DAY) as due_date
        FROM student s
        LEFT JOIN franchise f ON s.franchise_id = f.id
        LEFT JOIN fee_master fm ON s.franchise_fee_id = fm.id
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
        if($insert_rows>0){
            echo 'Invoice generated Successfully';
        }
        else{
            echo 'No invoice generated';
        }
        exit;
    }
    
}   