<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Fee_model extends CI_Model
{
    public function __construct(){
        parent::__construct();
        // $this->load->model('Mcommon');
    }

    public function addFee($data)
    {
        $this->db->insert('fee_master', $data);
        return $this->db->insert_id();
    }
    public function listFeeMasterInfo($data)
    {
        $this->db->select('fm.id as fee_master_id,fm.name as fee_title,fm.amount,fm.term,discount,fm.discount_details,status');
        $this->db->from('fee_master fm');
        if(isset($data['search']))
        {
            $this->db->group_start();
            $this->db->like('fm.name', $data['search'], 'both');
            $this->db->or_like('fm.amount', $data['search'], 'both');
            $this->db->or_like('fm.term', $data['search'], 'both');
            $this->db->or_like('fm.discount', $data['search'], 'both');
            $this->db->group_end();
        }
        if(isset($data['status']) && $data['status']!=''){
            $this->db->where('fm.status',$data['status']);
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);.
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }

  
    
}