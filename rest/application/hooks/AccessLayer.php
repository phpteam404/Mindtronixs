<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AccessLayer {
    var $CI;
    public $order_data = array();
    public $cnt =1;

    function __construct() {
        // Construct the parent class
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    function checkAccess()
    {
       if(isset($_GET) && !empty($_GET))
       {
           if(isset($_GET['module_url']) && isset($_GET['user_role_id']) && isset($_GET['module_access']))
           {
               $sql = 'SELECT am.*,ama.*,amac.user_role_id,amac.module_access_status
                       FROM `app_module` `am`
                       LEFT JOIN `app_module_action` `ama` ON am.id_app_module=ama.app_module_id
                       LEFT JOIN `app_module_access` `amac` ON ama.id_app_module_action=amac.app_module_action_id
                       and `amac`.`user_role_id` = '.$_GET['user_role_id'].' and `mcc`.`module_access_status`=1';

               $query = $this->CI->db->query($sql);
               $result = $query->result_array();
               $module_data = $this->order_data = array();

               for($s=0;$s<count($result);$s++)
               {
                   if($result[$s]['module_url']==$_GET['module_url'])
                   {
                       if( $result[$s]['sub_module']==0){
                           for($st=0;$st<count($result);$st++){

                               if($result[$s]['id_app_module']==$result[$st]['app_module_id']){

                                   if($result[$st]['action_name']=='list' || $result[$st]['action_name']=='add'){
                                       $this->order_data[] = array(
                                           $result[$st]['action_key'] => false
                                       );
                                   }else{
                                       $this->order_data[] = array(
                                           $result[$st]['action_key'] => ($result[$st]['module_access_status']==1)? true : false
                                       );
                                   }
                               }
                           }
                       }

                       $this->getChildNodes($result,$result[$s]['id_module']);
                       break;
                   }
               }
               if($this->cnt==0){
                   $result = array('status'=>FALSE, 'error' =>"You don't have permissions to this module", 'data'=>'');
                   echo json_encode($result); exit;
               }

               $result = array('status'=>TRUE, 'message' =>'success', 'data'=>$this->order_data);
               echo json_encode($result); exit;
           }
       }
    }

    public function getChildNodes($data,$parent_id)
    {
        for($s=0;$s<count($data);$s++)
        {
            if($data[$s]['parent_module_id']==$parent_id){
                if( $data[$s]['sub_module']==0){
                    for($st=0;$st<count($data);$st++){
                        if($data[$s]['id_app_module']==$data[$st]['app_module_id']){
                            if($data[$st]['module_access_status']==1){ $this->cnt=1; }
                            if($data[$st]['action_name']=='list' || $data[$st]['action_name']=='add'){
                                $this->order_data[] = array(
                                    $data[$st]['action_key'] => false
                                );
                            }else{
                                $this->order_data[] = array(
                                    $data[$st]['action_key'] => ($data[$st]['module_access_status']==1)? true : false
                                );
                            }
                        }
                    }
                }
                $this->getChildNodes($data,$data[$s]['id_module']);
            }
        }
        return $this->order_data;
    }

}
