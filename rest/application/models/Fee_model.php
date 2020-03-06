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
        $this->db->select('fm.id as fee_master_id,fm.name,fm.amount,fm.term,discount,fm.discount_details,fm.status,CONCAT(mc.child_name,"-",mc.id) term');
        $this->db->from('fee_master fm');
        $this->db->join('master_child mc','fm.term = mc.id AND mc.master_id = 11','left');
        $this->db->where('fm.status','1');
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
        // else{
        //     $this->db->where_in('fm.status',array('0','1'); 
        // }
        if(isset($data['fee_master_id']) && $data['fee_master_id'] > 0){
            $this->db->where('fm.id',$data['fee_master_id']);
        }
        if(isset($data['sort']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('fm.id','desc');
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);.
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }

    public function getfeeStructureDropdown($data){
        $this->db->select('CONCAT(fm.name,"-",fm.id) as fee_master');
        $this->db->from('fee_master fm');
        $this->db->join('franchise_fee ff','fm.id=ff.fee_master_id','left');
        $this->db->where('ff.franchise_id',$data['franchise_id']);
        $this->db->where('fm.status','1');
        $this->db->group_by('fm.id');
        $query = $this->db->get();
        return $query->result_array();
    }

  
    
}