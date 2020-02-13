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
        $this->db->select('*');
        $this->db->from('fee_master fm');
        if(isset($data['search']))
        {
            $this->db->group_start();
            $this->db->like('fm.name', $data['search'], 'both');
            $this->db->or_like('fm.description', $data['search'], 'both');
            $this->db->or_like('fm.discount_details', $data['search'], 'both');
            $this->db->or_like('fm.offer_type', $data['search'], 'both');
            $this->db->or_like('fm.price',$data['search'],'both');
            $this->db->group_end();
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        if(isset($data['limit']) && isset($data['offset']))
           $this->db->limit($data['limit'],$data['offset']);
        
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }

  
    
}