<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Digitalcontent extends REST_Controller
{
    public $order_data = array();
    public $cnt =1;

    public $user_id = 0 ;
    public $session_user_id=NULL;
    public $session_user_parent_id=NULL;
    public $session_user_id_acting=NULL;
    public $session_user_info=NULL;

    public function __construct()
    {
        parent::__construct();
        // $this->load->model('Digitalcontent_model');
        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_parent_id=$getLoggedUserId[0]['parent_user_id'];
        $this->session_user_id_acting=$getLoggedUserId[0]['child_user_id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
        // print_r($this->session_user_info);exit;
    }
    Public function addDigitalContent_post()
    {
        $data=$this->input->post();
        if(!empty($data['tags'])){
            $data['tags']='['.$data['tags'].']';
        }
        // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('category_id', array('required' => $this->lang->line('category_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $content_data=array(
            'category'=>!empty($data['category_id'])?$data['category_id']:'',
            'sub_category'=>!empty($data['sub_category_id'])?$data['sub_category_id']:'',
            'tags'=>!empty($data['tags'])?$data['tags']:'',
            'grade'=>!empty($data['grade_id'])?$data['grade_id']:'',
            'status'=>!empty($data['status'])?$data['status']:'1'
        );
        if(!empty($_FILES)){
            $allowed_types=array('gif','jpg','jpeg','png','pdf','mp4','xlsx','xls');   
            $no_of_files=count($_FILES['document']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = pathinfo($_FILES['document']['name'][$i], PATHINFO_EXTENSION);
            }
            $intersect_data=array_intersect($extensions,$allowed_types);
            for($i=0; $i <$no_of_files ; $i++) { 
                if($extensions==$intersect_data)
                {
                    $path='uploads/digitalcontent/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['document']['tmp_name'][$i],
                        'image' => $_FILES['document']['name'][$i],
                        'upload_path' => $path,''
                        )); 
                        // $imageName='digitalcontent/'.$imageName;
                        // print_r($imageName);exit;
                        $document_id[]=$this->User_model->insertdata('documents',array('document_name'=>!empty($imageName)?$imageName:'','created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0','created_on'=>currentDate(),'module_type'=>'digital_content'));
                }
                else
                {
                    $result = array('status'=>FALSE,'error'=>array('document' => $this->lang->line('invalid_doc_format')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }   

            }
            
        }
        if(!empty($_FILES) && count($document_id>0)){   
            if(isset($data['digital_content_management_id']) && $data['digital_content_management_id']>0){
                // $content_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                // $content_data['updated_on']=currentDate();
                // $is_update=$this->User_model->update_data('digital_content_management',$content_data,array('id'=>$data['digital_content_management_id']));
                // if(isset($is_update)){
                //     $result = array('status'=>TRUE, 'message' => $this->lang->line('content_update'), 'data'=>array('data' => $insert_id));
                //     $this->response($result, REST_Controller::HTTP_OK);
                // }
            }
            else{
                $content_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $content_data['created_on']=currentDate();
                $insert_id = $this->User_model->insertdata('digital_content_management',$content_data);
                if($insert_id>0){
                    $this->User_model->update_where_in('documents',array('module_type_id'=>$insert_id),array('id'=>$document_id));
                    $result = array('status'=>TRUE, 'message' => $this->lang->line('content_add'), 'data'=>array('data' => $insert_id));
                    $this->response($result, REST_Controller::HTTP_OK);
                }
                else{
                    $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
        }

    }

    public function digitalContetList_get(){
        $data=$this->input->get();
        $data = tableOptions($data);
        if(isset($data['tags']) && $data['tags']!='')
        {
            $data['tags']= explode(",",$data['tags']);
        }
        $content_data=$this->Digitalcontent_model->getContentList($data);//echo $this->db->last_query();exit;
        $total_records=$content_data['total_records'];
        $content_data=$content_data['data'];
        foreach($content_data as $k=>$v){
            if(!empty($v['documents'])){
                $content_data[$k]['documents']=explode(",",$v['documents']); 
                foreach($content_data[$k]['documents'] as $l=>$m){
                    $document[$k][$l]['document_name']=$m;
                    $document[$k][$l]['document_url']=DOCUMENT_PATH."digitalcontent/".$m;
    
                }
                unset($content_data[$k]['documents'][$l]);
                $content_data[$k]['documents']=$document[$k];
            }
            if(!empty($v['tags'])){
                $tags_ids=json_decode($v['tags']);
                $tags_names=$this->User_model->check_record_where_in('GROUP_CONCAT(child_name) tags_names','master_child',array('master_id'=>4,'status'=>1),array('id'=>$tags_ids));
                $content_data[$k]['tags_names']=!empty($tags_names[0]['tags_names'])?$tags_names[0]['tags_names']:'';
                unset($tags_names);
            }
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('Success'), 'data'=>array('data' => $content_data,'total_records'=>$total_records));
        $this->response($result, REST_Controller::HTTP_OK);
        
    }
}
