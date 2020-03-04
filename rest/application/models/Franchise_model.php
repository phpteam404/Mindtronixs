<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Franchise_model extends CI_Model
{
    public function __construct(){
        parent::__construct();
        // $this->load->model('Mcommon');
    }

    public function addFranchise($data)
    {
        $this->db->insert('franchise', $data);
        return $this->db->insert_id();
    }
    
    public function updateFranchise($data)
    {
        $this->db->where('franchise_id', $data['franchise_id']);
        $this->db->update('franchise', $data);
        return 1;
    }

    public function listfranchise($data)
    {
        $this->db->select('f.id as franchise_id,f.name as franchise_name, f.email,f.primary_contact as contact_number,f.franchise_code,
        CONCAT(mc.child_name,"-",mc.id) as city,f.status,DATE_FORMAT(f.created_on, "%d-%m-%Y") as created_on');
        // $this->db->select('*');
        $this->db->from('franchise f');
        $this->db->join('master_child mc','f.city=mc.id AND mc.master_id=14','left');
        $this->db->join('master_child mc1','f.state=mc1.id AND mc1.master_id=13','left');
        $this->db->join('master_child mc2','f.country=mc2.id AND mc2.master_id=12','left');
        if(isset($data['franchise_id']) && $data['franchise_id'] > 0){
            $this->db->select('f.franchise_contacts,f.website_address,f.owner_name,f.address,CONCAT(mc1.child_name,"-",mc1.id) as state,CONCAT(mc2.child_name,"-",mc2.id) as country');
            // $this->db->join('franchise_fee af','a.id = af.franchise_id','');
            // $this->db->join('fee_master fm','fm.id = af.fee_master_id','');
            $this->db->where('f.id',$data['franchise_id']);
            // $this->db->where('af.status',1);
            //$this->db->where('fm.status',1);
        }
        // print_r($data);exit;
        $this->db->where_in('f.status',array(0,1));
        if(isset($data['search'])){
            $this->db->group_start();
            $this->db->like('f.name', $data['search'], 'both');
            $this->db->or_like('f.franchise_code', $data['search'], 'both');
            $this->db->or_like('f.email', $data['search'], 'both');
            $this->db->or_like('f.city', $data['search'], 'both');
            $this->db->or_like('f.primary_contact',$data['search'],'both');
            $this->db->group_end();
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        if(isset($data['sort']))
            $this->db->order_by($data['sort'],$data['order']);
        else
        $this->db->order_by('f.id','desc');
        
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
        $this->db->where('sm.status','1');
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
        if(isset($data['franchise_id']) && $data['franchise_id']>0){
            $this->db->where('sm.franchise_id',$data['franchise_id']);
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
        // if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        // $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
  
    public function addFranchiseFee($data)
    {
        $this->db->insert('franchise_fee', $data);
        return $this->db->insert_id();
    }
    public function getFranchiseInfo($data)//this function is used for get franchise information
    {
        $this->db->select('f.id as franchise_id,f.name as franchise_name, f.franchise_code,f.website_address,mc.child_name as country,mc1.child_name as stage,mc2.child_name as state,f.landmark,f.email,f.pincode,f.primary_contact as contact_number,f.owner_name,GROUP_CONCAT(fee_master_id) as fee_master_id,f.franchise_contacts,if(f.status=1,"active","inactive") as status');
        $this->db->from('franchise f');
        $this->db->join('master_child mc','f.country=mc.id and mc.master_id=12','left');
        $this->db->join('master_child mc1','f.state=mc1.id and mc1.master_id=13','left');
        $this->db->join('master_child mc2','f.city=mc2.id and mc2.master_id=14','left');
        $this->db->join('franchise_fee af','f.id=af.franchise_id','left');
        if(!empty($data['franchise_id'])){
            $this->db->where('f.id',$data['franchise_id']);
        }
        $this->db->group_by('f.id');
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getFeeData($data)//this function is used for get fee data in for franchise
    {
        $this->db->select('CASE WHEN mc.id=19 THEN "1 One Month)" WHEN mc.id=20 THEN "3 (Three Months)" WHEN mc.id=21 THEN "6 (Six Months)" WHEN mc.id=22 THEN "12 (Twelve Months)"
        ELSE "" END as fee_title,fm.amount,mc.child_name as term,fm.discount');
        $this->db->from('fee_master fm');
        $this->db->join('master_child mc','fm.term=mc.id AND mc.master_id=11','left');
        $this->db->where('fm.id',$data['fee_master_id']);
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getSchoolInfo($data)
    {
        $this->db->select('sm.id as school_id, sm.name,sm.address,sm.phone,sm.school_code as code,sm.contact_person,sm.email,sm.franchise_id,CONCAT(mc.child_name,"-",mc.id) as city,
        CONCAT(mc1.child_name,"-",mc1.id) as state,sm.pincode,CONCAT(f.name, "-", f.id) as franchise_id');
        $this->db->from('school_master sm');
        $this->db->join('master_child mc','sm.city =mc.id AND mc.master_id=14','left');
        $this->db->join('master_child mc1','sm.state =mc1.id AND mc1.master_id=13','left');
        $this->db->join('franchise f','sm.franchise_id=f.id','left');
        if(isset($data['school_id']) && $data['school_id']>0){
            $this->db->where('sm.id',$data['school_id']);
        }
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getFranchiseDropdown(){
        $this->db->select('CONCAT(f.name, "-", f.id) as franchise_id');
        $this->db->from('franchise f');
        $this->db->where('f.status','1');
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getschoolDropdown(){
        $this->db->select('CONCAT(sm.name, "-", sm.id) as schools');
        $this->db->from('school_master sm');
        $this->db->where('sm.status','1');
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }

}