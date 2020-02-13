<?php
/**
 * Created by PhpStorm.
 * User: VENKATESH.B
 * Date: 28/12/16
 * Time: 4:54 PM
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH .'/libraries/sendgrid/vendor/autoload.php';
class Sendgridlibrary  {
    public $sg='';
    public function __construct() {
        $this->sg = new \SendGrid(base64_decode(SEND_GRID_API_KEY));
    }
    public function sendemail($from_name='',$from_email='',$subject='',$body='',$to_name='',$to_email='',$substitutions=array(),$mailer_id=''){

        $to_email = "parvathi.s@thresholdsoft.com1";
        $sg = $this->sg;
        $type='text/html';
        $request['content'][0]['type']=$type;
        $request['content'][0]['value']=$body;

        $request['from']=['email'=>$from_email,'name'=>$from_name];
        $request['personalizations'][0]['subject']=$subject;
        $request['personalizations'][0]['to'][0]=['email'=>$to_email,'name'=>$to_name];
        foreach($substitutions as $key=>$val){
            $request['personalizations'][0]['substitutions'][$key]=$val;
        }
        if($mailer_id!='')
            $request['custom_args']=['mailer_id'=>$mailer_id];
        //return 1; // use this in local to not to send emails
        //use this in dev, production
        $response = $sg->client->mail()->send()->post($request);
        if(isset($response) && in_array($response->statusCode(),array(200,201,202)))
            return 1;
        else
            return 0;
    }
    public function sendemailwithtemplate($from_name='',$from_email='',$subject='',$template_full_path='',$to_name='',$to_email='',$substitutions=array()){

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $response_temp = file_get_contents($template_full_path, false, stream_context_create($arrContextOptions));
        $template_content = trim($response_temp);
        $template_content = str_replace("\0","",$template_content);
        $template_content = str_replace("\u0","",$template_content);
        $template_content_data = str_replace("\"","'",$template_content);
        $output = str_replace(array("\r\n", "\r"), "\n", $template_content_data);
        $lines = explode("\n", $output);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if(!empty($line))
                $new_lines[] = trim($line);
        }
        $body = implode($new_lines);

        $sg = $this->sg;
        $type='text/html';

        $request['content'][0]['type']=$type;
        $request['content'][0]['value']=$body;

        $request['from']=['email'=>$from_email,'name'=>$from_name];
        $request['personalizations'][0]['subject']=$subject;
        $request['personalizations'][0]['to'][0]=['email'=>$to_email,'name'=>$to_name];
        foreach($substitutions as $key=>$val){
            $request['personalizations'][0]['substitutions'][$key]=$val;
        }

        $response = $sg->client->mail()->send()->post($request);
        return $response;

    }

    public function sendemailwithtemplatebulk($from_name='',$from_email='',$subject='',$template_full_path='',$to=array(),$substitutions=array()){

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $response_temp = file_get_contents($template_full_path, false, stream_context_create($arrContextOptions));
        $template_content = trim($response_temp);
        $template_content = str_replace("\0","",$template_content);
        $template_content = str_replace("\u0","",$template_content);
        $template_content_data = str_replace("\"","'",$template_content);
        $output = str_replace(array("\r\n", "\r"), "\n", $template_content_data);
        $lines = explode("\n", $output);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if(!empty($line))
                $new_lines[] = trim($line);
        }
        $body = implode($new_lines);

        $sg = $this->sg;
        $type='text/html';

        $request['content'][0]['type']=$type;
        $request['content'][0]['value']=$body;

        $request['from']=['email'=>$from_email,'name'=>$from_name];
        $request['personalizations'][0]['subject']=$subject;
        $in_counter=0;
        foreach($to as $pkt=>$pvt){
            $request['personalizations'][0]['to'][$in_counter]=['email'=>$pvt['to_email'],'name'=>$pvt['to_name']];
            $in_counter=$in_counter+1;
        }
        foreach($substitutions as $key=>$val){
            $request['personalizations'][0]['substitutions'][$key]=$val;
        }

        $response = $sg->client->mail()->send()->post($request);
        return $response;

    }
    /*public function sendemailwithtemplatebulk($from_name='',$from_email='',$personalizations=array(),$template_full_path=''){


        $template_content = trim(file_get_contents($template_full_path));
        $template_content_data = str_replace("\"","'",$template_content);
        $output = str_replace(array("\r\n", "\r"), "\n", $template_content_data);
        $lines = explode("\n", $output);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if(!empty($line))
                $new_lines[] = trim($line);
        }
        $body = implode($new_lines);

        $sg = $this->sg;
        $type='text/html';

        $request['content'][0]['type']=$type;
        $request['content'][0]['value']=$body;

        $request['from']=['email'=>$from_email,'name'=>$from_name];
        $counter=0;
        foreach($personalizations as $pk=>$pv){
            $in_counter=0;
            $request['personalizations'][$counter]['subject']=$pv['subject'];
            foreach($pv['to'] as $pkt=>$pvt){
                $request['personalizations'][$counter]['to'][$in_counter]=['email'=>$pvt['to_email'],'name'=>$pvt['to_name']];
                $in_counter=$in_counter+1;
            }
            foreach($pv['substitutions'] as $keys=>$vals){
                $request['personalizations'][$counter]['substitutions'][$keys]=$vals;
            }
            $counter=$counter+1;
        }
        echo "<pre>";print_r($request);echo "</pre>";
        $response = $sg->client->mail()->send()->post($request);
        echo "<pre>";print_r($response);echo "</pre>";
        //return $response;

    }*/

    public function createtransactiontemplate($template_name,$template_full_path=''){
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $response_temp = file_get_contents($template_full_path, false, stream_context_create($arrContextOptions));
        $template_content = trim($response_temp);
        $template_content = str_replace("\0","",$template_content);
        $template_content = str_replace("\u0","",$template_content);
        $template_plain_content = trim(strip_tags($template_content));
        $template_content_data = str_replace("\"","'",$template_content);
        $output = str_replace(array("\r\n", "\r"), "\n", $template_content_data);
        $lines = explode("\n", $output);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if(!empty($line))
                $new_lines[] = trim($line);
        }
        $body = implode($new_lines);
            $sg = $this->sg;
            $request_body['name']=$template_name;
            $response = $sg->client->templates()->post($request_body);
            if($response->statusCode()=='201'){
                $result=json_decode($response->body());
                $template_id=$result->id;
                $request_body_version['active']=1;
                $request_body_version['html_content']=$body;
                $request_body_version['name']=$template_name.'_V_'.date('U');
                $request_body_version['plain_content']=$template_plain_content;
                $request_body_version['subject']='--subject--';
                $request_body_version['template_id']=$template_id;
                $response_version = $sg->client->templates()->_($template_id)->versions()->post($request_body_version);
                return $template_id;
                /*echo $response_version->statusCode();
                echo $response_version->body();
                echo $response_version->headers();*/
            }
            else{
                return false;
            }
    }
    public function deletetransactiontemplate($template_id=''){
        $sg = $this->sg;
        if($template_id!='') {
        $response_templates = $sg->client->templates()->_($template_id)->get();
        if($response_templates->statusCode()==200){
            $result=json_decode($response_templates->body());
            foreach($result->versions as $kv=>$vv){
                $sg->client->templates()->_($template_id)->versions()->_($vv->id)->delete();
            }
        }
            $response = $sg->client->templates()->_($template_id)->delete();
        }
    }
    public function createbatch(){
        $sg = $this->sg;
        $response_batch = $sg->client->mail()->batch()->post();
        $result_batch=json_decode($response_batch->body());
        if(isset($result_batch->batch_id))
        $batch_id=$result_batch->batch_id;
        return $result_batch->batch_id;
    }
    public function sendemailwithtransactiontemplate($from_name='',$from_email='',$subject='',$transaction_template_id='',$presonalizations='',$send_at_date='',$send_at_time='',$batch_id='',$campaign_type='campaign',$timezone='IST'){

        $sg = $this->sg;
        //echo date('Y-m-d H:i:s',strtotime($send_at_date.' '.$send_at_time)).'/';
        //date_default_timezone_set("America/Belize");
        //date_default_timezone_set("UTC");
        //echo date('Y-m-d H:i:s',strtotime($send_at_date.' '.$send_at_time));
        $dd=date('Y-m-d H:i:s',strtotime($send_at_date.' '.$send_at_time));
        if($timezone=='IST')
            $timezone='Asia/Kolkata';
        $date = new DateTime($dd, new DateTimeZone($timezone));
        /*echo 'America/New_York: '.$date->format('Y-m-d H:i:s').'<br />'."\r\n";
        echo 'America/New_York: '.$date->getTimestamp().'<br />'."\r\n";*/
        $send_at_unixtimestamp=$date->getTimestamp();
        //$send_at_unixtimestamp=strtotime(date('Y-m-d H:i:s',strtotime($send_at_date.' '.$send_at_time)));
        //$send_at_unixtimestamp=strtotime(date('Y-m-d H:i:s',strtotime('+2 minutes')));
        //date_default_timezone_set('Asia/Kolkata');
        if($campaign_type=='campaign')
            $request['send_at']=$send_at_unixtimestamp;
        if($campaign_type=='reminder')
            $request['send_at']=$send_at_unixtimestamp;

        if($batch_id!=''){
            $request['batch_id']=$batch_id;
        }

        $request['from']=['email'=>$from_email,'name'=>$from_name];
        $request['template_id']=$transaction_template_id;
        $request['custom_args']=['batch_id'=>$batch_id,'campaign_type'=>$campaign_type];
        $request['personalizations']=$presonalizations;
        $response = $sg->client->mail()->send()->post($request);
        return $response;
    }
    public function getUXtimestamp(){
        date_default_timezone_set("UTC");
    }



}