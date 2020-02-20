<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Digitalcontent_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    public function getContentList($data){
        $this->db->select(' `dcm`.*,m.master_name as category_name,m1.master_name as sub_category_name,GROUP_CONCAT(d.document_name) as documents');
        $this->db->from('digital_content_management dcm');  
        $this->db->join('documents d','dcm.id=d.module_type_id','left');
        $this->db->join('master m','dcm.category=m.id','left');
        $this->db->join('master m1','dcm.sub_category=m1.id','left');
        $this->db->where('d.module_type','digital_content');
        // $t=1;
        // $where=
        if(isset($data['tag']) && $data['tag']!=''){
            foreach($data['tag'] as $k=>$t){
                $this->db->or_group_start();
                $this->db->where("JSON_CONTAINS(tags,'$t', '$')");                
                    $this->db->group_end();
                } 
            }
        // $this->db->_protect_identifiers=true;
        // $this->db->where("tag REGEXP'(,5|^5$|5,)'");
        // $this->db->where("tag REGEXP'(,".$t."|^".$t."$|".$t.",)'");
        // $this->db->where('tag REGEXP'(,".$t."|^".$t."$|".$t.",)'');
        $this->db->group_by('dcm.id');
        $subQuery =  $this->db->get_compiled_select();
        $subQuery=trim($subQuery,' ');
        $this->db->select('*');
        $this->db->from("($subQuery) temp");
        if(isset($data['category']) && $data['category']>0){
            $this->db->where('category',$data['category']);
        }
        if(isset($data['sub_category']) && $data['sub_category']>0){
            $this->db->where('sub_category',$data['sub_category']);
        }
        // print_r($data);exit;
        $query = $this->db->get();
        // echo $this->db->last_query();exit;
        return $query->result_array();
    }
}
