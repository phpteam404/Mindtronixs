<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Digitalcontent_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    
    public function getContentList($data=null){
        // print($data['search_key']);exit;
        $this->db->select('dcm.id as digital_content_management_id,dcm.content_name,mc.child_name as category,mc1.child_name as sub_category,mc2.child_name as content_level,mc3.child_name as grade,IFNULL(dcm.no_of_views, 0) no_of_views,dcm.tags,if(dcm.status=1,"Active","Inactive") as status');
        $this->db->from('digital_content_management dcm');
        $this->db->join('master_child mc','dcm.category=mc.id and  mc.master_id=1','left');
        $this->db->join('master_child mc1','dcm.sub_category=mc1.id and mc1.master_id=2','left');
        $this->db->join('master_child mc2','dcm.content_level=mc2.id AND mc2.master_id=3','left');
        $this->db->join('master_child mc3','dcm.grade=mc3.id AND mc3.master_id=5','left');
        $this->db->where_in('dcm.status',array(1,0));
        if(isset($data['search_key'])){
            $this->db->group_start();
            $this->db->like('dcm.content_name', $data['search_key'], 'both');
            $this->db->or_like('mc.child_name', $data['search_key'],'both');
            $this->db->or_like('mc1.child_name', $data['search_key'],'both');
            $this->db->or_like('mc2.child_name', $data['search_key'],'both');
            $this->db->or_like('mc3.child_name', $data['search_key'],'both');
            $this->db->or_like('dcm.tags', $data['search_key'],'both');
            $this->db->group_end();
        }
        if(!empty($data['exclude_content_ids'])){
            $this->db->where_not_in('dcm.id',$data['exclude_content_ids']);
        }
        if(!empty($data['sort']) && !empty($data['order']))
        { 
            $this->db->order_by($data['sort'],$data['order']);
        }
        else{
            $this->db->order_by('dcm.id','desc');
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        if(isset($data['number']) && isset($data['start']))
           $this->db->limit($data['number'],$data['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());

    }
    public function getDigitalContentInfo($data=null){
        if($data['request_type']=='view'){
            $this->db->select('dcm.id as digital_content_management_id,dcm.content_name,dcm.content_description as description,dcm.expiry_date,mc.child_name as category,mc1.child_name as sub_category,mc2.child_name as content_level,mc3.child_name as grade,dcm.tags ,dcm.no_of_views,IF(dcm.status=1,"Active","Inactive") as status,dcm.pre_url,dcm.post_url');
        }
        if($data['request_type']=='edit'){
            $this->db->select('dcm.id as digital_content_management_id, dcm.content_name as name, dcm.content_description as description, dcm.expiry_date,CONCAT(mc.child_name, "-", mc.id)as category,CONCAT(mc1.child_name, "-", mc1.id)as sub_category,CONCAT(mc2.child_name, "-", mc2.id)as content_level,CONCAT(mc3.child_name, "-", mc3.id)as grade,dcm.tags,dcm.status,dcm.pre_url,dcm.post_url');
        }
        $this->db->from('digital_content_management dcm');
        $this->db->join('master_child mc','dcm.category=mc.id and  mc.master_id=1','left');
        $this->db->join('master_child mc1','dcm.sub_category=mc1.id and mc1.master_id=2','left');
        $this->db->join('master_child mc2','dcm.content_level=mc2.id and mc2.master_id=3','left');
        $this->db->join('master_child mc3','dcm.grade=mc3.id AND mc3.master_id=5','left');
        // $this->db->join('master_child mc4','dcm.tags=mc4.id AND mc4.master_id=4','left');
        if(!empty($data['digital_content_management_id'])){
            $this->db->where('dcm.id',$data['digital_content_management_id']);
        }
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getDocuments($data=null){
        $this->db->select('d.id as document_id,d.document_name,if(d.module_type="digital_content","files","url") as module_type');
        $this->db->from('documents d');
        $this->db->join('digital_content_management dcm','d.module_type_id=dcm.id');
        if(!empty($data['module_type_id'])){
            $this->db->where('d.module_type_id',$data['module_type_id']);
        }
        if(!empty($data['module_type'])){
            $this->db->where_in('d.module_type',$data['module_type']);
        }
        $this->db->where('d.status','1');
        $this->db->order_by('d.id','desc');
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getExcludeContentIds($data=null){
        $this->db->select('content_id');
        $this->db->from('content_maping cm');
        if(!empty($data['franchise_id'])){
            $this->db->where('cm.exclude_franchise',$data['franchise_id']);
        }
        if(!empty($data['school_id'])){
            $this->db->where('cm.exclude_school',$data['school_id']);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

}
