<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Agency_model extends CI_Model
{
    public function __construct(){
        parent::__construct();
        // $this->load->model('Mcommon');
    }

    public function addAgency($data)
    {
        $this->db->insert('agency', $data);
        return $this->db->insert_id();
    }
    
    public function updateAgency($data)
    {
        $this->db->where('agency_id', $data['agency_id']);
        $this->db->update('agency', $data);
        return 1;
    }

    public function listfranchise($data)
    {
        $this->db->select('a.id as agency_id,a.name as agency_name,a.franchise_code,a.primary_contact as contact_number,a.website_address,a.country,a.state,a.address,a.landmark,a.city,a.email,a.created_on,a.status,a.agency_contacts,a.owner_name');
        // $this->db->select('*');
        $this->db->from('agency a');
        if(isset($data['agency_id']) && $data['agency_id'] > 0){
            // $this->db->select('GROUP_CONCAT(af.fee_master_id) fee_master_id');
            // $this->db->join('agency_fee af','a.id = af.agency_id','');
            // $this->db->join('fee_master fm','fm.id = af.fee_master_id','');
            $this->db->where('a.id',$data['agency_id']);
            // $this->db->where('af.status',1);
            //$this->db->where('fm.status',1);
        }
        // print_r($data);exit;
        $this->db->where_in('a.status',array(0,1));
        if(isset($data['search'])){
            $this->db->group_start();
            $this->db->like('a.name', $data['search'], 'both');
            $this->db->or_like('a.franchise_code', $data['search'], 'both');
            $this->db->or_like('a.email', $data['search'], 'both');
            $this->db->or_like('a.city', $data['search'], 'both');
            $this->db->or_like('a.primary_contact',$data['search'],'both');
            $this->db->group_end();
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
            $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }

    public function addSchool($data)
    {
        $this->db->insert('school_master', $data);
        return $this->db->insert_id();
    }

    public function listSchools($data)
    {
        $this->db->select('sm.id as school_id,sm.*');
        $this->db->from('school_master sm');
        $this->db->where_in('sm.status',array(0,1));
        if(isset($data['status']) && $data['status']!==''){
            $this->db->where('sm.status',$data['status']);
        }
        if(isset($data['search']))
        {
            $this->db->group_start();
            $this->db->like('sm.name', $data['search'], 'both');
            $this->db->or_like('sm.address', $data['search'], 'both');
            $this->db->or_like('sm.email', $data['search'], 'both');
            $this->db->or_like('sm.phone',$data['search'],'both');
            $this->db->group_end();
        }
        if(isset($data['school_id']) && $data['school_id']>0){
            $this->db->where('sm.id',$data['school_id']);
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
  
    public function addAgencyFee($data)
    {
        $this->db->insert('agency_fee', $data);
        return $this->db->insert_id();
    }

}