<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);

class Externalcontroller extends CI_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $language = 'en';
        if(isset($_SERVER['HTTP_LANG']) && $_SERVER['HTTP_LANG']!=''){
            $language = $_SERVER['HTTP_LANG'];
            if(is_dir('application/language/'.$language)==0){
                $language = $this->config->item('rest_language');
            }
        }
        $this->lang->load('rest_controller', $language);
        $this->load->model('DigitalContent_model');
        $this->load->model('User_model');

    }

    //* digital content view for online user  start *//
    public function digitalcontentonlineuser(){
        $sample_responce='{"digital_content_management_id":"117","content_name":"on line user digital content","description":"<p>digital content description</p>","expiry_date":"2020-04-13","category":"Required","sub_category":"Sub Categories","content_level":"high content","grade":"VII","tags":"test","no_of_views":0,"status":"Active","pre_url":"https://www.youtube.com/watch?v=sGymqz_1K5Y","post_url":"https://www.youtube.com/watch?v=pby0jqNriiM","documents":[{"document_id":"478","document_name":"working_icon_1586759266.png","module_type":"files","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/working_icon_1586759266.png"},{"document_id":"477","document_name":"Google_Pay_-_Pay_your_electricity_bill_in_just_a_few_taps_-_#MoneyMadeSimple_1586759266.mp4","module_type":"files","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/Google_Pay_-_Pay_your_electricity_bill_in_just_a_few_taps_-_#MoneyMadeSimple_1586759266.mp4"},{"document_id":"476","document_name":"https://www.youtube.com/watch?v=pby0jqNriiM","module_type":"url","document_url":"http://192.168.43.57/Mindtronixs/rest/uploads/digitalcontent/https://www.youtube.com/watch?v=pby0jqNriiM"}]}';
        //echo $sample_responce;
        $data = $this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'message'=>$this->lang->line('invalid_data'),'data'=>'1');
            echo json_encode($result); exit;
        }
        if(!empty($data['digital_content_management_id'])){
            $data['request_type']='view';
            $content_info=$this->Digitalcontent_model->getDigitalContentInfo($data);
            $content_info[0]['no_of_views']=!empty($content_info[0]['no_of_views'])?$content_info[0]['no_of_views']:0;
            $documents=$this->Digitalcontent_model->getDocuments(array('module_type_id'=>$data['digital_content_management_id'],'module_type'=>array('digital_content','url')));//echo $this->db->last_query();exit;
            if(!empty($documents)){
                foreach($documents as $k=>$v){
                    $documents[$k]['document_url']=DOCUMENT_PATH.'digitalcontent/'.$v['document_name'];
                }
            }
            $content_info[0]['documents']=!empty($documents)?$documents:array();
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $content_info));
            echo json_encode($result);exit;
        }
        else{
            $result = array('status'=>FALSE,'message'=>$this->lang->line('digital_content_management_id_req'),'data'=>'2');
            echo json_encode($result); exit;
        }
    }
    //* digital content view for online user  end *//


    //* online user registration start *//
    // public function addOnlineUser(){
    //     $data=$this->input->post();
    //     $user_table_data=array(
    //         'first_name'=>!empty($data['name'])?$data['name']:'',
    //         'last_name'=>!empty($data['last_name'])?$data['last_name']:' ',
    //         'email'=>!empty($data['email'])?$data['email']:'',
    //         'password'=>!empty($data['password'])?md5($data['password']):'',
    //         'user_role_id'=>9,
    //         'phone_no'=>!empty($data['phone_no'])?$data['phone_no']:'',
    //         'created_by'=>0,
    //         'created_on'=>currentDate()
    //     );
    //     $user_id=$this->User_model->inset_data('user',$user_table_data);
    //     $student_table_data=array(
    //         'user_id'=>$user_id,
    //         'grade'=>!empty($data['grade'])?$data['grade']:'',
    //         'parent'=>!empty($data['parent_name'])?$data['parent_name']:'',
    //         'lead_source'=>!empty($data['parent_name'])?$data['parent_name']:'',
            
    //     );
    // }
    //* online user registration end *//
}
