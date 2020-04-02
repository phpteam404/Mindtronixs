<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
require 'ImageFactory.php';
require APPPATH . '/libraries/phpqrcode/qrlib.php'; 

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
        // $this->load->library('phpqrcode/qrlib');
        // $this->load->helper('url');
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
        // if(!empty($data['tags'])){
        //     $data['tags']='['.$data['tags'].']';
        // }
        // print_r($data);print_r($_FILES);exit;

        // print_r(json_encode($data));exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('name', array('required' => $this->lang->line('content_name_req')));
        $this->form_validator->add_rules('category', array('required' => $this->lang->line('category_req')));
        $this->form_validator->add_rules('sub_category', array('required' => $this->lang->line('sub_category_req')));
        $this->form_validator->add_rules('tags', array('required' => $this->lang->line('tags_req')));
        // $this->form_validator->add_rules('grade', array('required' => $this->lang->line('grade_req')));
        // $this->form_validator->add_rules('content_level', array('required' => $this->lang->line('content_level_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $content_data=array(
            'content_name'=>!empty($data['name'])?$data['name']:'',
            'content_description'=>!empty($data['description'])?$data['description']:'',
            'category'=>!empty($data['category'])?$data['category']:'',
            'sub_category'=>!empty($data['sub_category'])?$data['sub_category']:'',
            'tags'=>!empty($data['tags'])?$data['tags']:'',
            'expiry_date'=>!empty($data['expiry_date'])?$data['expiry_date']:'', 
            'grade'=>!empty($data['grade'])?$data['grade']:'',
            'content_level'=>!empty($data['content_level'])?$data['content_level']:'',
            'status'=>isset($data['status'])?$data['status']:'1',
            'pre_url'=>isset($data['pre_url'])?$data['pre_url']:'',
            'post_url'=>isset($data['post_url'])?$data['post_url']:'',
        );
        // print_r(explode(",",$data['tags']));exit;
        if(!empty($_FILES)){
            $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png','application/pdf','video/mp4','video/quicktime'); 
            $no_of_files=count($_FILES['files']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = $_FILES['files']['type'][$i];
            }
            $intersect_data=array_intersect($extensions,$allowed_types);
            // print_r($extensions);exit;
            // print_r($intersect_data);exit;
            for($i=0; $i <$no_of_files ; $i++) { 
                if($extensions==$intersect_data)
                {
                    //   print_r($_FILES['files']['tmp_name'][$i]);exit;
                    $path='uploads/digitalcontent/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['files']['tmp_name'][$i],
                        'image' => $_FILES['files']['name'][$i],
                        'upload_path' => $path,''
                    )); 
                    // print_r($_FILES['files']['name'][$i]);exit;
                    $image_extensions=array('gif','jpg','jpeg','png');
                    if(in_array(pathinfo($_FILES['files']['name'][$i],PATHINFO_EXTENSION),$image_extensions)){  
                        if(!is_dir('uploads/digitalcontent/small_images/'))
                        { mkdir('uploads/digitalcontent/small_images/'); }
                        $ImageMaker =   new ImageFactory();
                        // Here is just a test landscape sized image
                        $image_target   =   "uploads/digitalcontent/".$imageName;
                        // if(!is_dir($small_images_destination)){ mkdir($small_images_destination); }
                            // This will save the file to disk. $destination is where the file will save and with what name
                            $small_images_destination    =   "uploads/digitalcontent/small_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,65,65,$small_images_destination);//this is used to resize image with 65X65 resolution
                            if(!is_dir('uploads/digitalcontent/medium_images/')){ mkdir('uploads/digitalcontent/medium_images/'); }
                            $medium_images_destination    =   "uploads/digitalcontent/medium_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,150,150,$medium_images_destination);//this is used to resize image with 150X150 resolution
                    }
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
        if(empty($_FILES)){
            if(!empty($data['digital_content_management_id'])){
                $content_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $content_data['updated_on']=currentDate();
                $is_update=$this->User_model->update_data('digital_content_management',$content_data,array('id'=>$data['digital_content_management_id']));
                if(isset($is_update)){
                    $result = array('status'=>TRUE, 'message' => $this->lang->line('content_update'), 'data'=>array('data' => $data['digital_content_management_id']));
                    if(!empty($data['external_urls'])){
                        $this->addDigitalContentUrls($data['digital_content_management_id'],$data['external_urls']);
                    }
                    $this->response($result, REST_Controller::HTTP_OK);
                }
                else{
                    $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK); 
                }
            }
            else{
                $content_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $content_data['created_on']=currentDate();
                $insert_id = $this->User_model->insertdata('digital_content_management',$content_data);
                if($insert_id>0){
                    $this->generateQrCode($insert_id,'www.google.com');
                    $this->User_model->insert_data('documents',array('module_type_id'=>$insert_id,'document_name'=>$insert_id.'.png','module_type'=>'qr_code','created_by'=>!empty($this->session_user_id)?$this->session_user_id:0,'created_on'=>currentDate()));
                    if(!empty($data['external_urls'])){
                        $this->addDigitalContentUrls($insert_id,$data['external_urls']);
                    }
                    $result = array('status'=>TRUE, 'message' => $this->lang->line('content_add'), 'data'=>array('data' => $insert_id));
                    $this->response($result, REST_Controller::HTTP_OK);
                }
                else{
                    $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
        }
        //print_r(count($document_id));exit;
        if(!empty($_FILES) && !empty($document_id)){   
            if(isset($data['digital_content_management_id']) && $data['digital_content_management_id']>0){
                $content_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $content_data['updated_on']=currentDate();
                $is_update=$this->User_model->update_data('digital_content_management',$content_data,array('id'=>$data['digital_content_management_id']));
                if(!empty($data['external_urls'])){
                    $this->addDigitalContentUrls($data['digital_content_management_id'],$data['external_urls']);
                }
                $this->User_model->update_where_in('documents',array('module_type_id'=>$data['digital_content_management_id']),array('id'=>$document_id));
                if(isset($is_update)){
                    $result = array('status'=>TRUE, 'message' => $this->lang->line('content_update'), 'data'=>array('data' => $data['digital_content_management_id']));
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            else{
                $content_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $content_data['created_on']=currentDate();
                $insert_id = $this->User_model->insertdata('digital_content_management',$content_data);
                if($insert_id>0){
                    $this->User_model->update_where_in('documents',array('module_type_id'=>$insert_id),array('id'=>$document_id));
                    $this->generateQrCode($insert_id,'www.google.com');
                    $this->User_model->insert_data('documents',array('module_type_id'=>$insert_id,'document_name'=>$insert_id.'.png','module_type'=>'qr_code','created_by'=>!empty($this->session_user_id)?$this->session_user_id:0,'created_on'=>currentDate()));
                    if(!empty($data['external_urls'])){
                        $this->addDigitalContentUrls($insert_id,$data['external_urls']);
                    }
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
        $content_list=$this->Digitalcontent_model->getContentList($data);//echo $this->db->last_query();exit;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $content_list['data'],'total_records'=>$content_list['total_records'],'table_headers'=>getTableHeads('digital_content_management_list')));
        $this->response($result, REST_Controller::HTTP_OK);
        
    }
    public function digitalContentInfo_get(){
        $data=$this->input->get();
        // print_r($_SERVER['HTTP_AUTHORIZATION']);exit;
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, 'http://www.google.com');
        // curl_exec($ch);
            
       if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('digital_content_management_id', array('required'=>$this->lang->line('digital_content_management_id_req')));
        $this->form_validator->add_rules('request_type', array('required'=>$this->lang->line('type_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if($data['request_type']=='view'){
            $content_info=$this->Digitalcontent_model->getDigitalContentInfo($data);//echo $this->db->last_query();exit;
            // print_r($data['digital_content_management_id']);exit;
            $content_info[0]['no_of_views']=!empty($content_info[0]['no_of_views'])?$content_info[0]['no_of_views']:0;
            $documents=$this->Digitalcontent_model->getDocuments(array('module_type_id'=>$data['digital_content_management_id'],'module_type'=>array('digital_content','url')));//echo $this->db->last_query();exit;
            if(!empty($documents)){
                foreach($documents as $k=>$v){
                    $documents[$k]['document_url']=DOCUMENT_PATH.'digitalcontent/'.$v['document_name'];
                }
            }
            $qr_document=$this->User_model->check_record('documents',array('module_type_id'=>$data['digital_content_management_id'],'module_type'=>'qr_code'));
            // print_r($qr_document);exit;
            // print_r($qr_document);exit;
            if(!empty($qr_document)){
                $content_info[0]['qr_code']=DOCUMENT_PATH.'digitalcontent/qrcodes/'.$qr_document[0]['document_name'];
            }
            else{
                $content_info[0]['qr_code']=array();
            }
            $content_info[0]['documents']=!empty($documents)?$documents:array();
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $content_info));
        }
        if($data['request_type']=='edit'){
            $content_info=$this->Digitalcontent_model->getDigitalContentInfo($data);
            $content_info[0]['category']= getObjOnId($content_info[0]['category'],!empty($content_info[0]['category'])?true:false);
            $content_info[0]['sub_category']= getObjOnId($content_info[0]['sub_category'],!empty($content_info[0]['sub_category'])?true:false);
            $content_info[0]['content_level']= getObjOnId($content_info[0]['content_level'],!empty($content_info[0]['content_level'])?true:false);
            $content_info[0]['grade']= getObjOnId($content_info[0]['grade'],!empty($content_info[0]['grade'])?true:false);
            //$content_info[0]['tags']= getObjOnId($content_info[0]['tags'],!empty($content_info[0]['tags'])?true:false);
            $content_info[0]['status']= getStatusObj($content_info[0]['status']);
            // print_r($content_info);exit;
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $content_info));
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }

    function generateQrCode($id,$codeContents){//this function is used to generate qr code for digital content
        $name=$id.'.png';
        
        if(!is_dir('uploads/digitalcontent/qrcodes/'))
         { mkdir('uploads/digitalcontent/qrcodes/'); }
         $path='uploads/digitalcontent/qrcodes/';
        // $tempDir = EXAMPLE_TMP_URLRELPATH; 
	    // $codeContents='http://bit.ly/2uwV5jv';
        QRcode::png($codeContents, $path.$name, QR_ECLEVEL_H, 5); 
    }
    public function addDigitalContentDocuments_post()//this function is used for add documents to digital content management
    {
        // print_r($_FILES);exit;
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('digital_content_management_id', array('required'=>$this->lang->line('digital_content_management_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(!empty($_FILES)){
            $allowed_types=array('image/gif','image/jpg','image/jpeg','image/png','application/pdf','video/mp4','video/quicktime'); 
            $no_of_files=count($_FILES['files']['name']);
            for ($i=0; $i <$no_of_files ; $i++) {
                $extensions[] = $_FILES['files']['type'][$i];
            }
            $intersect_data=array_intersect($extensions,$allowed_types);
            // print_r($intersect_data);exit;
            for($i=0; $i <$no_of_files ; $i++) { 
                if($extensions==$intersect_data)
                {
                    //   print_r($_FILES['files']['tmp_name'][$i]);exit;
                    $path='uploads/digitalcontent/';
                    $imageName = doUpload(array(
                        'temp_name' => $_FILES['files']['tmp_name'][$i],
                        'image' => $_FILES['files']['name'][$i],
                        'upload_path' => $path,''
                    )); 
                    // print_r($_FILES['files']['name'][$i]);exit;
                    $image_extensions=array('gif','jpg','jpeg','png');
                    if(in_array(pathinfo($_FILES['files']['name'][$i],PATHINFO_EXTENSION),$image_extensions)){  
                        if(!is_dir('uploads/digitalcontent/small_images/'))
                        { mkdir('uploads/digitalcontent/small_images/'); }
                        $ImageMaker =   new ImageFactory();
                        // Here is just a test landscape sized image
                        $image_target   =   "uploads/digitalcontent/".$imageName;
                        // if(!is_dir($small_images_destination)){ mkdir($small_images_destination); }
                            // This will save the file to disk. $destination is where the file will save and with what name
                            $small_images_destination    =   "uploads/digitalcontent/small_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,65,65,$small_images_destination);//this is used to resize image with 65X65 resolution
                            if(!is_dir('uploads/digitalcontent/medium_images/')){ mkdir('uploads/digitalcontent/medium_images/'); }
                            $medium_images_destination    =   "uploads/digitalcontent/medium_images/".$imageName ;
                            $ImageMaker->Thumbnailer($image_target,150,150,$medium_images_destination);//this is used to resize image with 150X150 resolution
                    }
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
            if(!empty($document_id)){
                
                $this->User_model->update_where_in('documents',array('module_type_id'=>$data['digital_content_management_id']),array('id'=>$document_id));
                $result = array('status'=>TRUE, 'message' => $this->lang->line('docs_add'), 'data'=>array('data' =>$data['digital_content_management_id']));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_doc_format'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
    }
    public function mapContentToFranchise_post(){
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('content_id', array('required'=>$this->lang->line('digital_content_management_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // Deleting the past content mappings
        $this->User_model->delete_data('content_maping',array('content_id' => $data['content_id']));

        // Explodeing comma separated into arrys.
        $data['exclude_franchise'] = explode(',',$data['exclude_franchise']);
        $data['exclude_school'] = explode(',',$data['exclude_school']);

        // echo '<pre>'.print_r($data);
        // Finding the Max count of arrays
        $max_array_size = max(count($data['exclude_franchise']),count($data['exclude_school']));

        // Appending with 0's if the array sizes are differnt each other
        if(count($data['exclude_school']) < $max_array_size)
            $data['exclude_school'] = array_pad($data['exclude_school'],$max_array_size,0);
        if(count($data['exclude_franchise']) < $max_array_size)
            $data['exclude_franchise'] = array_pad($data['exclude_franchise'],$max_array_size,0);

        $insert_batch_array = array();
        foreach($data['exclude_franchise'] as $k => $v){
            // Preparing insert batch array
            $insert_batch_array[$k] = array(
                'content_id' => $data['content_id'],
                'all_franchise' => isset($data['all_franchise'])?$data['all_franchise']:0,
                'exclude_franchise' => $data['exclude_franchise'][$k],
                'all_schools' => isset($data['all_schools'])?$data['all_schools']:0,
                'exclude_school' => $data['exclude_school'][$k],
                'created_by' => $this->session_user_id,
                'created_on' => CurrentDate()
            );
        }
        $url_insert=$this->User_model->insertbatch('content_maping',$insert_batch_array);

        $result = array('status'=>TRUE, 'message' => $this->lang->line('content_mapping_saved'), 'data'=>[]);
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function mapContentToFranchise_get(){
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('content_id', array('required'=>$this->lang->line('digital_content_management_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $result = $this->User_model->check_record('content_maping',array('content_id'=>$data['content_id']));
        $response = array('all_franchise'=>0,'all_schools'=>0,'content_id'=>0,'exclude_franchise'=>0,'exclude_school'=>0);
        $exclude_franchise = $exclude_school = array();
        foreach($result as $k => $v){
            $response['all_franchise'] = $v['all_franchise'];
            $response['all_schools'] = $v['all_schools'];
            $response['content_id'] = $v['content_id'];
            $exclude_franchise[] = $v['exclude_franchise'];
            $exclude_school[] = $v['exclude_school'];
        }
        $response['exclude_franchise'] = implode(',',array_diff($exclude_franchise,array(0)));
        $response['exclude_school'] = implode(',',array_diff($exclude_school,array(0)));

        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$response);
        $this->response($result, REST_Controller::HTTP_OK);
    }

    function addDigitalContentUrls($content_id,$urls){
        $list_urls=explode(",",$urls);
        foreach($list_urls as $k=>$v){
            $insert_batch_array[$k] = array(
                'module_type_id'=>$content_id,
                'document_name'=>$v,
                'created_by'=>$this->session_user_id,
                'created_on'=>currentDate(),
                'module_type'=>'url'
            );
        }
       $url_insert=$this->User_model->insertbatch('content_maping',$insert_batch_array);
       return $url_insert;
    }
    
}
