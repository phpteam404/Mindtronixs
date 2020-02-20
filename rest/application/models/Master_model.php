<?php
/**
 * Created by PhpStorm.
 * User: THRESHOLD
 * Date: 2015-12-01
 * Time: 04:09 PM
 */
class Master_model extends CI_Model
{
    public function check_not_in($table,$where,$wherenotin=null){
        $this->db->select('*');
        $this->db->from($table);
        if(isset($wherenotin)){
            foreach($wherenotin as $columnname=>$values){
                $this->db->where_not_in($columnname,$values);
            }
        }
        if(isset($where)) {
            $this->db->where($where);
        }
        $query = $this->db->get();
        return $query->result_array();
        
    }
    function getMaster($data)
    {
        $this->db->select(' `mc`.`id` as `master_child_id`, `mc`.`child_name`,  `mc`.`child_key`,`m`.`master_key`, `m`.`master_name`,m.id as master_id, CONCAT(UCASE(LEFT(mc.description, 1)), SUBSTRING(mc.description, 2)) description');
        $this->db->from('master m');
        $this->db->join('master_child mc','m.id=mc.master_id','left');
        if(isset($data['master_key']))
            $this->db->where('m.master_key',$data['master_key']);
        if(isset($data['child_key']))
            $this->db->where('mc.child_key',$data['child_key']);
        $this->db->where('mc.status',1);
        if(isset($data['order']))
            $this->db->order_by($data['order']);
        else{
            $this->db->order_by('mc.child_name');
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
}