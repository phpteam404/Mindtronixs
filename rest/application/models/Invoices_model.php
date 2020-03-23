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
        $this->db->select('s.id as student_invoice_number,si.invoice_number,concat(u.first_name," ",u.last_name) as student_name,u.phone_no ,u.email as email,si.invoice_date,si.amount,mc.child_name as status,si.franchise_id');
        $this->db->from('student_invoice si');
        $this->db->join('student s','si.student_id=s.id','left');
        $this->db->join('user u','s.user_id=u.id','left');
        $this->db->join('master_child mc','si.payment_status=mc.id AND mc.master_id=23','left');
        if(isset($data['search_key']) && $data['search_key']!==''){
            $this->db->group_start();
            $this->db->where('u.first_name like "%'.$data['search_key'].'%" or u.last_name like "%'.$data['search_key'].'%" or CONCAT(u.first_name,\' \',u.last_name) like "%'.$data['search_key'].'%" or u.email like "%'.$data['search_key'].'%"  or u.phone_no like "%'.$data['search_key'].'%"or si.invoice_number like "%'.$data['search_key'].'%"');
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

        $this->db->select('SUM(amount) as total_amount,count(*) as count');
        $this->db->from('student_invoice');
        if(!empty($data['franchise_id'])){
            $this->db->where('franchise_id',$data['franchise_id']);
        }
        if(!empty($data['payment_status'])){
            $this->db->where('payment_status',$data['payment_status']);
        }
        $query = $this->db->get();
        return $query->result_array();
    }
}