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
        $query  =   "SET @a=0"; 
        $this->db->query($query);
        $query1='
        INSERT INTO  student_invoice (invoice_number,franchise_id,student_id,amount,tax,total_amount,franchise_fee_id,invoice_date,discount,discount_amount,tax_amount,created_on,created_by,due_date,invoice_type,paid_date)
        SELECT CONCAT("MIN/",f.franchise_code,"/",YEAR(CURDATE()),"/",DATE_FORMAT(CURDATE(),"%m"),"/",@a:=LPAD(@a+1, 6, 0)) invoice_number,f.id franchise_id,s.id student_id,fm.amount,fm.tax,((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))-((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100)+((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)) as total_amount,s.franchise_fee_id as franchise_fee_id,CURRENT_DATE()invoice_date,fm.discount,TRIM(((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100))+0 as discount_amount,TRIM((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)+0 as tax_amount,CURRENT_DATE() as created_on, 1 as created_by,DATE_ADD(CURDATE(), INTERVAL fm.due_days DAY) as due_date,1 as invoice_type,0 as paid_date
        FROM student s
        LEFT JOIN franchise f ON s.franchise_id = f.id
        LEFT JOIN fee_master fm ON s.franchise_fee_id = fm.id
        WHERE s.status=1
        AND s.next_invoice_date = CURDATE()
        AND s.subscription_status = 1
        AND s.school_id=0
        AND s.franchise_fee_id!=0';
        
        $student_invoiced_rows=$this->User_model->custom_query_affected_rows($query1);
        $query_b  =   "SET @b=0"; 
        $this->db->query($query_b);
        $query3='INSERT student_invoice(amount,franchise_id,invoice_number,royal_amount,invoice_date,invoice_type,created_on,tax,tax_amount,total_amount)
        SELECT SUM(si.total_amount) as amount,si.franchise_id, CONCAT("MIN/",f.name,"/",YEAR(CURDATE()),"/",DATE_FORMAT(CURDATE(),"%m"),"/",@b:=LPAD(@b+1, 6, 0)) invoice_number,(SUM(si.total_amount)*'.FRACHISE_PERCENTAGE.'/100) as royal_amount,si.invoice_date,3 as invoice_type,CURRENT_DATE as created_on ,'.FRACHISE_TAX_PERCENTAGE.' as tax,(SUM(si.total_amount)*'.FRACHISE_TAX_PERCENTAGE.'/100) as tax_amount,((SUM(si.total_amount)*'.FRACHISE_PERCENTAGE.'/100)+(SUM(si.total_amount)*'.FRACHISE_TAX_PERCENTAGE.'/100)) as total_amount
        FROM student_invoice si
        LEFT JOIN student s ON si.student_id=s.id
        LEFT JOIN franchise f ON si.franchise_id = f.id
        WHERE si.invoice_type=1
        AND s.next_invoice_date = CURDATE()
        AND s.subscription_status = 1
        AND s.school_id=0
        AND s.franchise_fee_id!=0
        GROUP BY si.franchise_id
        ORDER BY si.id
        ';
        $frachise_invoice_rows=$this->User_model->custom_query_affected_rows($query3);

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
        print_r($student_invoiced_rows);
        print_r($frachise_invoice_rows);
        print_r($update_rows);
        if($insert_rows>0 && $frachise_invoice_rows>0){
            echo 'Invoice generated Successfully';
        }
        else{
            echo 'No invoice generated';
        }
        exit;
    }
    
}   