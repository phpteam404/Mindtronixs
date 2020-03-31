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
        // print_r($data);exit;
        $this->db->select('fm.id as fee_master_id,fm.name,fm.amount,fm.term,discount,fm.discount_details,fm.status,CONCAT(mc.child_name,"-",mc.id) term,fm.tax,fm.due_days');
        $this->db->from('fee_master fm');
        $this->db->join('master_child mc','fm.term = mc.id AND mc.master_id = 11','left');
        if(isset($data['search_key']))
        {
            $this->db->group_start();
            $this->db->like('fm.name', $data['search_key'], 'both');
            $this->db->or_like('fm.amount', $data['search_key'], 'both');
            $this->db->or_like('mc.child_name', $data['search_key'], 'both');
            $this->db->or_like('fm.discount', $data['search_key'], 'both');
            $this->db->or_like('fm.tax', $data['search_key'], 'both');
            $this->db->group_end();
        }
        if(isset($data['status']) && $data['status']!=''){
            $this->db->where('fm.status',$data['status']);
        }
        else{
            $this->db->where_in('fm.status',array(1,0));
        }
        if(!empty($data['fee_master_id_not_in'])){
            $this->db->where_not_in('fm.id',$data['fee_master_id_not_in']);
        }
        if(!empty($data['fee_master_id'])){
            $this->db->where('fm.id',$data['fee_master_id']);
        }
        
        if(isset($data['sort']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('fm.id','desc');
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }

    public function getfeeStructureDropdown($data=null){
        $this->db->select('CONCAT(fm.name, "-", fm.id) fee_master,TRIM(fm.amount)+0 as amount,fm.tax,fm.discount');
        $this->db->from('fee_master fm');
        $this->db->join('franchise_fee ff','fm.id=ff.fee_master_id','left');
        if(!empty($data['franchise_id']))
        $this->db->where('ff.franchise_id',$data['franchise_id']);
        $this->db->where('fm.status','1');
        $this->db->where('ff.status','1');
        $this->db->group_by('fm.id');
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }

  
    
}