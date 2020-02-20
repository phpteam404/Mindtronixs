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
        // print_r(PATHINFO_EXTENSION);exit;
        // print_r(json_decode($data['tags']));exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('category', array('required' => $this->lang->line('category_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // $extensions[] = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
        // print_r($_FILES);exit;

        $content_data=array(
            'category'=>!empty($data['category'])?$data['category']:'',
            'sub_category'=>!empty($data['sub_category'])?$data['sub_category']:'',
            'tags'=>!empty($data['tags'])?$data['tags']:'',
            'grade'=>!empty($data['grade'])?$data['grade']:'',
            'status'=>!empty($data['status'])?$data['status']:'1'
        );

        if(!empty($_FILES)){
            $allowed_types=array('gif','jpg','jpeg','png','pdf','mp4','xlsx','xls');   
            $no_of_files=count($_FILES['document']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = pathinfo($_FILES['document']['name'][$i], PATHINFO_EXTENSION);
            }
            // $extensions_unique=array_unique($extensions); 
            // $extensions=array_unique($extensions);
            $intersect_data=array_intersect($extensions,$allowed_types);
            // $intersect_data =array_unique($intersect_data);
            //  print_r($extensions);
            //  print_r($intersect_data);exit;

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
        $data['tags'][0]=1;
        $data['tags'][1]=5;
        $data['tags'][2]=7;
        $data['category']=1;
        // $data['sub_category']=2;
        // print_r(DOCUMENT_PATH);exit;
        if(isset($data['tag']) && $data['tag']!='')
        {
            $data['tag']= explode(",",$data['tag']);
        }
        $content_data=$this->Digitalcontent_model->getContentList($data);
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

        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('Success'), 'data'=>array('data' => $content_data));
        $this->response($result, REST_Controller::HTTP_OK);
        
    }
}