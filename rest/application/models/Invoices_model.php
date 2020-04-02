<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }
    public function getStudentData($data=null){//this function used for get student details
        $this->db->select('s.id as student_id,s.franchise_fee_id,fm.amount,fm.discount,s.created_on,mc.child_name as term,s.status,fm.term as term_id');
        $this->db->from('student s');
        $this->db->join('fee_master fm','s.franchise_fee_id=fm.id','left');
        $this->db->join('master_child mc','fm.term=mc.id AND mc.master_id=11','left');
        $this->db->where('s.status','1');
        $query = $this->db->get();
        return $query->result_array();

    }
    public function getStudentInvoiceList($data=null){
            $this->db->select('si.id as student_invoice_id,replace(si.invoice_number," ","") as invoice_number,concat(u.first_name," ",u.last_name) as student_name,u.phone_no ,u.email as email,si.invoice_date,TRIM(si.total_amount)+0 as amount,mc.child_name as status,si.franchise_id,s.id as student_id');
            if(!empty($data['student_invoice_id'])){
                $this->db->select('fm.name as fee_structure,fm.term,si.payment_status,DATE_FORMAT(s.created_on, "%Y-%m-%d") as member_since,s.id as student_id,s.next_invoice_date,si.due_date,si.paid_date');
            }
        $this->db->from('student_invoice si');
        $this->db->join('student s','si.student_id=s.id','left');
        $this->db->join('user u','s.user_id=u.id','left');
        $this->db->join('master_child mc','si.payment_status=mc.id AND mc.master_id=23','left');
        if(!empty($data['student_invoice_id'])){
            $this->db->join('fee_master fm','si.franchise_fee_id=fm.id','left');
            $this->db->join('master_child mc1','fm.term=mc1.id AND mc1.master_id=11','left');
        }
        if(isset($data['search_key']) && $data['search_key']!==''){
            $this->db->group_start();
            $this->db->where('u.first_name like "%'.$data['search_key'].'%" or u.last_name like "%'.$data['search_key'].'%" or CONCAT(u.first_name,\' \',u.last_name) like "%'.$data['search_key'].'%" or u.email like "%'.$data['search_key'].'%"  or u.phone_no like "%'.$data['search_key'].'%"or si.invoice_number like "%'.$data['search_key'].'%"');
            $this->db->group_end();
        }
        
        $this->db->where('si.invoice_type',1);
        if(!empty($data['from_date']) && !empty($data['to_date'])){
            $this->db->where('si.invoice_date between "'.$data['from_date'].'" and "'.$data['to_date'].'"');
        }
        if(!empty($data['status_id'])){
            $this->db->where('si.payment_status',$data['status_id']);
        }
        if(!empty($data['franchise_id'])){
            $this->db->where('si.franchise_id',$data['franchise_id']);
        }
        if(empty($data['from_date']) && empty($data['to_date']) && empty($data['status_id']) && empty($data['month'])){
            $this->db->where('MONTH(si.invoice_date)', date('m')); //For current month
            $this->db->where('YEAR(si.invoice_date)', date('Y')); // For current year
        }
        if(!empty($data['student_invoice_id'])){
            $this->db->where('si.id',$data['student_invoice_id']);
        }
        if(!empty($data['month'])){
            $this->db->like('si.invoice_date', $data['month'], 'both');
        }
        if(isset($data['sort']) && isset($data['order']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('si.id','desc');
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
    public function getAmount($data=null){

        $this->db->select('SUM(total_amount) as total_amount,count(*) as count');
        $this->db->from('student_invoice si');
        // if(!empty($data['franchise_id'])){
        //     $this->db->where('franchise_id',$data['franchise_id']);
        // }
        if(!empty($data['status'])){
            $this->db->where('si.invoice_type',$data['status']);
        }
        if(!empty($data['payment_status'])){
            $this->db->where('payment_status',$data['payment_status']);
        }
        if(!empty($data['from_date']) && !empty($data['to_date'])){
            $this->db->where('si.invoice_date between "'.$data['from_date'].'" and "'.$data['to_date'].'"');
        }
        if(!empty($data['status_id'])){
            $this->db->where('si.payment_status',$data['status_id']);
        }
        if(!empty($data['franchise_id'])){
            $this->db->where('si.franchise_id',$data['franchise_id']);
        }
        if(empty($data['from_date']) && empty($data['to_date']) && empty($data['status_id']) && empty($data['month'])){
            $this->db->where('MONTH(si.invoice_date)', date('m')); //For current month
            $this->db->where('YEAR(si.invoice_date)', date('Y')); // For current year
        }
        if(!empty($data['month'])){
            $this->db->like('si.invoice_date', $data['month'], 'both');
        }
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getPreviousStudentInvoice($data=null){
        $this->db->select('si.invoice_number,si.invoice_date,si.amount,mc.child_key as status');
        $this->db->from('student_invoice si');
        $this->db->join('master_child mc','mc ON si.payment_status=mc.id AND mc.master_id=23','left');
        if(!empty($data['student_id'])){
            $this->db->where('si.student_id',$data['student_id']);
        }
        if(!empty($data['student_invoice_id'])){
            $this->db->select('si.id as student_invoice_id,si.student_id');
            $this->db->where_not_in('si.id',array($data['student_invoice_id']));
        }
        if(!empty($data['school_id'])){
            $this->db->where('si.school_id',$data['school_id']);
        }
        if(!empty($data['school_invoice_id'])){
            $this->db->select('si.id as school_invoice_id,si.school_id');
            $this->db->where_not_in('si.id',array($data['school_invoice_id']));
        }
        $this->db->order_by('si.id','desc');
        $query = $this->db->get();
        return $query->result_array();
        
    }
    public function getStudentPaymentHistory($data=null){
        $this->db->select('sih.student_invoice_id,concat(u.first_name," ",u.last_name) as updated_by,mc.child_key as status,mc1.child_key as payment_type,comments,DATE_FORMAT(sih.update_on,"%Y-%m-%d") updated_on');
        $this->db->from('student_invoice_payment_history sih');
        $this->db->join('user u','sih.updated_by =u.id','left');
        $this->db->join('master_child mc','sih.payment_status=mc.id AND mc.master_id=23','left');
        $this->db->join(' master_child mc1',' sih.payment_type=mc1.id AND mc1.master_id=24','left');
        if(!empty($data['student_invoice_id'])){
            $this->db->where('sih.student_invoice_id',$data['student_invoice_id']);
        }
        if(!empty($data['school_invoice_id'])){
            $this->db->where('sih.school_invoice_id',$data['school_invoice_id']);
        }
        $this->db->order_by('sih.id','desc');
        $query = $this->db->get();
        return $query->result_array();

    }
    public function getStudentInvoicedData($data=null){
        $this->db->select(' `s`.`id` `student_id`, `s`.`franchise_id`, `fm`.`amount`, `fm`.`discount`, `fm`.`tax`, `fm`.`term`, TRIM(((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))-((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100)+((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)))+0 as total_amount, `s`.`franchise_fee_id`, `f`.`franchise_code`, TRIM(((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.discount/100))+0 as discount_amount, TRIM((fm.amount+(s.remaining_invoice_days*(fm.amount/30)))*fm.tax/100)+0 as tax_amount, `fm`.`due_days`');
        $this->db->from('student s');
        $this->db->join('franchise f','s.franchise_id=f.id','left');
        $this->db->join('fee_master fm','s.franchise_fee_id=fm.id','left');
        if(!empty($data['student_id'])){
            $this->db->where('s.id',$data['student_id']);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getSchoolData($data=null){
        $this->db->select('sm.school_code,f.name as franchise_name,sm.franchise_id,f.franchise_code');
        $this->db->from('school_master sm');
        $this->db->join('franchise f','sm.franchise_id=f.id');
        if(!empty($data['school_id'])){
            $this->db->where('sm.id',$data['school_id']);
        }
        if(!empty($data['status'])){
            $this->db->where('sm.status',$data['status']);
        }
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getSchoolInvoiceList($data=null){
        $this->db->select('si.id as school_invoice_id,si.invoice_number,sm.`name` as school_name,f.`name` as frachise_name,si.total_amount as amount,DATE_FORMAT(si.invoice_date, "%Y-%m-%d") as invoice_date,mc.child_name as status,si.paid_date,si.school_id');
        $this->db->from('student_invoice si');
        $this->db->join('school_master sm','si.school_id=sm.id');
        $this->db->join('franchise f','sm.franchise_id=f.id');
        $this->db->join('master_child mc','si.payment_status=mc.id AND mc.master_id=23','left');
        $this->db->where('si.invoice_type',2);
        if(isset($data['search_key']))
        {
            $this->db->group_start();
            $this->db->like('si.invoice_number', $data['search_key'], 'both');
            $this->db->or_like('sm.name', $data['search_key'], 'both');
            $this->db->or_like('f.name', $data['search_key'], 'both');
            $this->db->or_like('si.amount', $data['search_key'], 'both');
            $this->db->group_end();
        }
        if(!empty($data['from_date']) && !empty($data['to_date'])){
            $this->db->where('si.invoice_date between "'.$data['from_date'].'" and "'.$data['to_date'].'"');
        }
        if(!empty($data['status_id'])){
            $this->db->where('si.payment_status',$data['status_id']);
        }
        if(!empty($data['franchise_id'])){
            $this->db->where('si.franchise_id',$data['franchise_id']);
        }
        // if(empty($data['from_date']) && empty($data['to_date']) && empty($data['status_id']) && empty($data['month'] && empty($data[]))){
        //     $this->db->where('MONTH(si.invoice_date)', date('m')); //For current month
        //     $this->db->where('YEAR(si.invoice_date)', date('Y')); // For current year
        // }
        if(!empty($data['school_invoice_id'])){
            $this->db->where('si.id',$data['school_invoice_id']);
        }
        if(!empty($data['month'])){
            $this->db->like('si.invoice_date', $data['month'], 'both');
        }
        if(isset($data['sort']) && isset($data['order']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('si.id','desc');
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
}