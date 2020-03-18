<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }
    public function getStudentData($data=null){//this function used for get student details
        $this->db->select('s.id as student_id,s.franchise_fee_id,fm.amount,fm.discount,s.created_on,mc.child_name as term,s.status');
        $this->db->from('student s');
        $this->db->join('fee_master fm','s.franchise_fee_id=fm.id','left');
        $this->db->join('master_child mc','fm.term=mc.id AND mc.master_id=11','left');
        $this->db->where('s.status','1');
        $query = $this->db->get();
        return $query->result_array();

    }
}