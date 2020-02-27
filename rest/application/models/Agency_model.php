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
        $this->db->select('a.id as agency_id,a.name as agency_name, a.email,a.primary_contact as contact_number,a.franchise_code,
        CONCAT(mc.child_name,"-",mc.id) as city,a.status,DATE_FORMAT(a.created_on, "%d-%m-%Y") as created_on');
        // $this->db->select('*');
        $this->db->from('agency a');
        $this->db->join('master_child mc','a.city=mc.id AND mc.master_id=14','left');
        $this->db->join('master_child mc1','a.state=mc1.id AND mc1.master_id=13','left');
        $this->db->join('master_child mc2','a.country=mc2.id AND mc2.master_id=12','left');
        if(isset($data['agency_id']) && $data['agency_id'] > 0){
            $this->db->select('a.agency_contacts,a.website_address,a.owner_name,a.address,CONCAT(mc1.child_name,"-",mc1.id) as state,CONCAT(mc2.child_name,"-",mc2.id) as country');
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
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        if(isset($data['sort']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('a.id','desc');
        
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
        // $this->db->select('sm.id as school_id,sm.*');
        // $this->db->from('school_master sm');
        // $this->db->where_in('sm.status',array(0,1));
        // if(isset($data['status']) && $data['status']!==''){
        //     $this->db->where('sm.status',$data['status']);
        // }

        $this->db->select('sm.id as school_id,sm.school_code code,sm.name,COUNT(DISTINCT s.id ) as no_of_students,sm.phone,sm.email');
        $this->db->from('school_master sm');
        $this->db->join('student s','sm.id=s.school_id','left');
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
        $this->db->group_by('sm.id');
        if(isset($data['sort']) && isset($data['order']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('sm.id','desc');
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
  
    public function addAgencyFee($data)
    {
        $this->db->insert('agency_fee', $data);
        return $this->db->insert_id();
    }
    public function getAgencyInfo($data)//this function is used for get agency information
    {
        $this->db->select('a.id as agency_id,a.name as agency_name, a.franchise_code,a.website_address,mc.child_name as country,mc1.child_name as stage,mc2.child_name as state,a.landmark,a.email,a.pincode,a.primary_contact as contact_number,a.owner_name,GROUP_CONCAT(fee_master_id) as fee_master_id,a.agency_contacts,if(a.status=1,"active","inactive") as status');
        $this->db->from('agency a');
        $this->db->join('master_child mc','a.country=mc.id and mc.master_id=12','left');
        $this->db->join('master_child mc1','a.state=mc1.id and mc1.master_id=13','left');
        $this->db->join('master_child mc2','a.city=mc2.id and mc2.master_id=14','left');
        $this->db->join('agency_fee af','a.id=af.agency_id','left');
        if(!empty($data['agency_id'])){
            $this->db->where('a.id',$data['agency_id']);
        }
        $this->db->group_by('a.id');
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getFeeData($data)//this function is used for get fee data in for agency
    {
        $this->db->select('CASE WHEN mc.id=19 THEN "1 One Month)" WHEN mc.id=20 THEN "3 (Three Months)" WHEN mc.id=21 THEN "6 (Six Months)" WHEN mc.id=22 THEN "12 (Twelve Months)"
        ELSE "" END as fee_title,fm.amount,mc.child_name as term,fm.discount');
        $this->db->from('fee_master fm');
        $this->db->join('master_child mc','fm.term=mc.id AND mc.master_id=11','left');
        $this->db->where('fm.id',$data['fee_master_id']);
        $query = $this->db->get();
        return $query->result_array();
    }

}