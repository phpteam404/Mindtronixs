<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Digitalcontent_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    public function getContentList($data){
        $this->db->select('dcm.id as content_id, dcm.category as category_id,dcm.sub_category as sub_category_id, dcm.status, dcm.tags, dcm.grade as grade_id, mc.child_name as category_name, mc1.child_name as sub_category_name,mc2.child_name as grade, GROUP_CONCAT(d.document_name) as documents');
        $this->db->from('digital_content_management dcm');  
        $this->db->join('documents d','dcm.id=d.module_type_id','left ');
        $this->db->join('master_child mc','dcm.category=mc.id	 AND mc.master_id=1','left');
        $this->db->join('master_child mc1','dcm.sub_category=mc1.id AND mc1.master_id=2','left');
        $this->db->join('master_child mc2','dcm.grade=mc2.id AND mc2.master_id=5','left');
        $this->db->where('d.module_type','digital_content');
        if(isset($data['tags']) && $data['tags']!=''){
            foreach($data['tags'] as $k=>$t){
                // $this->db->or_group_start();
                if($k==0){
                    $this->db->where("JSON_CONTAINS(tags,'$t', '$')");
                }
                else{
                    $this->db->or_where("JSON_CONTAINS(tags,'$t', '$')");
                }            
            } 
        }
        $this->db->group_by('dcm.id');
        $subQuery =  $this->db->get_compiled_select();
        $subQuery=trim($subQuery,' ');
        $this->db->select('*');
        $this->db->from("($subQuery) temp");
        if(isset($data['category_id']) && $data['category_id']>0){
            $this->db->where('category_id',$data['category_id']);
        }
        if(isset($data['sub_category_id']) && $data['sub_category_id']>0){
            $this->db->where('sub_category_id',$data['sub_category_id']);
        }
        if(isset($data['grade_id']) && $data['grade_id']!=''){
            $this->db->where('grade_id',$data['grade_id']);
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;    
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
}
