<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author    EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright    Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license    http://opensource.org/licenses/MIT	MIT License
 * @link    http://codeigniter.com
 * @since    Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Array Helpers
 *
 * @package        CodeIgniter
 * @subpackage    Helpers
 * @category    Helpers
 * @author        EllisLab Dev Team
 * @link        http://codeigniter.com/user_guide/helpers/array_helper.html
 */

// ------------------------------------------------------------------------
/*if (!function_exists('generatePassword')) {
    function generatePassword($length)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}*/
if (!function_exists('generatePassword')) {
    function generatePassword($length = 8, $add_dashes = false, $available_sets = 'luds')
    {
        $sets = array();
        if(strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if(strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';
        $all = $password = '';
        foreach($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];
        $password = str_shuffle($password);
        if(!$add_dashes)
            return $password;
        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while(strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}
if (!function_exists('doUpload')) {
    function doUpload($data)
    {
       // print_r($data);exit;
        $temp_name = $data['temp_name'];
        $image = $data['image'];
        $upload_path = $data['upload_path'];
        //$folder = $data['folder'];

        $ext = pathinfo($image, PATHINFO_EXTENSION);
        if(!is_dir($upload_path)){ mkdir($upload_path); }

        // if($folder!='')
        //     $folder = $folder.'/';

        list($txt, $ext1) = explode(".", $image);
        $imageName = str_replace(' ','_',$txt) . "_" . time() . "." . $ext;
        move_uploaded_file($temp_name, $upload_path . $imageName);
        return $imageName;
    }
}
if (!function_exists('getImageUrl')) {
    function getImageUrl($image, $type='',$dimensions='',$path='uploads/')
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        if ($image != '') {
            if (file_exists($path . $image)) {
                if($dimensions!=''){
                    $image1 = explode('/',$image);
                    if(!isset($image1[1])){ $image1[1] = $image1[0]; $image1[0]=''; }
                    $image1 = $image1[0].'/_'.$dimensions.'_'.$image1[1];
                    if (file_exists($path . $image1)) {
                        $image = $image1;
                    }
                    //echo "<pre>"; print_r($image); exit;
                }
                //mime_content_type(REST_API_URL . $path . $image);
                $finfo = new finfo(FILEINFO_MIME);
                $mime  = $finfo->file($path . $image);
                //echo "<pre>";print_r(mime_content_type(REST_API_URL . $path . $image));echo "</pre>";exit;
                return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . $path . $image, false, stream_context_create($arrContextOptions)));
                //return REST_API_URL . $path . $image;
            }
        }

        if ($type == 'profile') {
            //return REST_API_URL . 'images/default-img.png';
            $finfo = new finfo(FILEINFO_MIME);
            $mime  = $finfo->file('images/default-img.png');
            return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . 'images/default-img.png', false, stream_context_create($arrContextOptions)));
        } else if ($type == 'company') {
            //return REST_API_URL . 'images/company-logo.png';
            $finfo = new finfo(FILEINFO_MIME);
            $mime  = $finfo->file('images/company-logo.png');
            return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . 'images/company-logo.png', false, stream_context_create($arrContextOptions)));
        } else if ($type == 'flag') {
            //return REST_API_URL . 'images/default-flag.png';
            $finfo = new finfo(FILEINFO_MIME);
            $mime  = $finfo->file('images/default-flag.png');
            return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . 'images/default-flag.png', false, stream_context_create($arrContextOptions)));
        }
        else{
            //return REST_API_URL . 'images/default-img.png';
            $finfo = new finfo(FILEINFO_MIME);
            $mime  = $finfo->file('images/images/default-img.png');
            return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . 'images/default-img.png', false, stream_context_create($arrContextOptions)));
        }
    }
}
if (!function_exists('getImageUrlSendEmail')) {
    function getImageUrlSendEmail($image, $type='',$dimensions='',$path='uploads/')
    {
        if ($image != '') {
            if (file_exists($path . $image)) {
                if($dimensions!=''){
                    $image1 = explode('/',$image);
                    if(!isset($image1[1])){ $image1[1] = $image1[0]; $image1[0]=''; }
                    $image1 = $image1[0].'/_'.$dimensions.'_'.$image1[1];
                    if (file_exists($path . $image1)) {
                        $image = $image1;
                    }
                    //echo "<pre>"; print_r($image); exit;
                }
                return REST_API_URL . $path . $image;
            }
        }

        if ($type == 'profile') {
            return REST_API_URL . 'images/default-img.png';
        } else if ($type == 'company') {
            return REST_API_URL . 'images/company-logo.png';
        } else if ($type == 'flag') {
            return REST_API_URL . 'images/default-flag.png';

        }
        else{
            return REST_API_URL . 'images/default-img.png';
        }
    }
}
if (!function_exists('getExactImageUrl')) {
    function getExactImageUrl($image)
    {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        if ($image != '') {
            if (file_exists('uploads/' . $image)) {
                //return REST_API_URL . 'uploads/' . $image;
                $finfo = new finfo(FILEINFO_MIME);
                $mime  = $finfo->file('uploads/' . $image);
                return 'data: '.$mime.';base64,'.base64_encode(file_get_contents(REST_API_URL . 'uploads/' . $image, false, stream_context_create($arrContextOptions)));
            }
            else{
                return '';
            }
        }
        else{
            return '';
        }
    }
}
if (!function_exists('getExactImageDirectoryUrl')) {
    function getExactImageDirectoryUrl($image)
    {
        if ($image != '') {
            if (file_exists('uploads/' . $image)) {
                return FCPATH . 'uploads/' . $image;
            }
            else if (file_exists(FILE_SYSTEM_PATH.'uploads/' . $image)) {
                return FILE_SYSTEM_PATH . 'uploads/' . $image;
            }
            else{
                return '';
            }
        }
        else{
            return '';
        }
    }
}

if (!function_exists('formatSizeUnits')) {
    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
}

if (!function_exists('currentDate')) {
    function currentDate()
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('currencyFormat')) {
    function currencyFormat($cur,$format){
        if($format=='EUR'){
            $cur = str_replace('.','$',$cur);
            $cur = str_replace(',','.',$cur);
            $cur = str_replace('$',',',$cur);
            return $cur;
        }
        else{
            return $cur;
        }
    }
}

if (!function_exists('getStatusText')) {
    function getStatusText($status){
        if((int)$status){
            return 'Active';
        }else{
            return 'Inactive';
        }
    }
}

if (!function_exists('getObjOnId')) {
    function getObjOnId($v,$bool){
        $v = explode('-',$v);
        if($bool){
            return array('label'=>$v[0],'value'=>(int)$v[1]);
        }else{
            return $v[0];
        }
    }
}

if (!function_exists('getStatusObj')) {
    function getStatusObj($status){
        if((int)$status){
            return array('label'=>'Active','value'=>1);
        }else{
            return array('label'=>'Inactive','value'=>0);
        }
    }
}

if (!function_exists('getUserBrowser')) {
    function getUserBrowser($u_agent)
    {
        //$u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";

        //First get the platform?



        $platform = getUserOS($u_agent);
        /*if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        }
        elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }*/

        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }
        elseif(preg_match('/Trident/i',$u_agent))
        { // this condition is for IE11
            $bname = 'Internet Explorer';
            $ub = "rv";
        }
        elseif(preg_match('/Edge/i',$u_agent))
        {
            $bname = 'Internet Explorer';
            $ub = "Edge";
        }
        elseif(preg_match('/Firefox/i',$u_agent))
        {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        }
        elseif(preg_match('/Chrome/i',$u_agent))
        {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        }
        elseif(preg_match('/Safari/i',$u_agent))
        {
            $bname = 'Apple Safari';
            $ub = "Safari";
        }
        elseif(preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Opera';
            $ub = "Opera";
        }
        elseif(preg_match('/Netscape/i',$u_agent))
        {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        // Added "|:"
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
            ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }
            else {
                $version= $matches['version'][1];
            }
        }
        else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {$version="?";}

        return $bname.'('.$version.') '.$platform;
        //return $browser = $ubrowser['name'].','.$ubrowser['version'].','.$ubrowser['platform'];
        /*return array(
            'userAgent' => $u_agent,
            'name'      => $bname,
            'version'   => $version,
            'platform'  => $platform,
            'pattern'    => $pattern
        );*/
    }
}
if (!function_exists('getUserOS')) {
function getUserOS($user_agent) {
    $os_platform    =   "Unknown OS Platform";

    $os_array       =   array(
        '/windows nt 10/i'     =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );

    foreach ($os_array as $regex => $value) {

        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }

    }

    return $os_platform;
}
}
if (!function_exists('arrayToTable')) {
    function arrayToTable($array_data, $docname = '')
    {
        $i = 0;
        $table_data = '<table border="1" style="border-collapse: collapse;border-color: #000000;margin-right: 10px;">';
        foreach ($array_data as $v) {
            if ($i == 0)
                $table_data .= '<tr style="font-weight: bold">';
            else
                $table_data .= '<tr>';
            foreach ($v as $vv) {
                if ($docname == 'termsheet') {
                    if ($vv != '' && is_numeric($vv)) {
                        $table_data .= '<td>' . number_format($vv) . '</td>';
                    } else {
                        $table_data .= '<td>' . $vv . '</td>';
                    }
                } else {
                    $table_data .= '<td>' . $vv . '</td>';
                }

            }
            $table_data .= '</tr>';
            $i++;
        }
        $table_data .= '</table>';
        return $table_data;
    }
}

if (!function_exists('saveMail')) {
    function saveMail($to,$subject,$message) {
        $CI =& get_instance();
        $CI->db->insert('mailer', array(
            'mail_to' => $to,
            'mail_subject' => $subject,
            'mail_message' => $message,
        ));
        return 1;
    }
}

if (!function_exists('tableOptions')) {
    function tableOptions($data) {
        if(isset($data['pagination']) && $data['pagination']!=''){
            if(!is_array($data['pagination']))
            {
                $data['pagination'] = (array)json_decode($data['pagination']);
            }

        }
        if(isset($data['search']) && $data['search']!=''){
            if(!is_array($data['search']))  $data['search'] = (array)json_decode($data['search'],true);
            if(isset($data['search']['predicateObject']) && isset($data['search']['predicateObject']['search_key'])){
                $data['search'] = $data['search']['predicateObject']['search_key'];
            }
            else{
                unset($data['search']);
            }
        }
        if(isset($data['sort']) && $data['sort']!=''){
            if(!is_array($data['sort']))  $data['sort'] = (array)json_decode($data['sort']);
            if(isset($data['sort']['reverse'])){
                $data['sort']['reverse'] = $data['sort']['reverse']==false?$data['sort']['reverse']='ASC':$data['sort']['reverse']='DESC';
            }
        }

        return $data;
    }
}

if (!function_exists('imageResize')) {
    function imageResize($image)
    {
        $size = array(SMALL_IMAGE,MEDIUM_IMAGE);
        for($s=0;$s<count($size);$s++)
        {
            $size_array = explode('x',$size[$s]);
            $image_array = explode('/',$image);
            $CI =& get_instance();
            // Configuration
            $img_array['image_library'] = 'gd2';
            $img_array['source_image'] = $image;
            if(count($image_array)>2)
                $img_array['new_image'] = $image_array[0].'/'.$image_array[1].'/'.'_'.$size[$s].'_'.$image_array[2];
            else
                $img_array['new_image'] = $image_array[0].'/'.'_'.$size[$s].'_'.$image_array[1];
            $img_array['create_thumb'] = FALSE;
            $img_array['maintain_ratio'] = TRUE;
            $img_array['width'] = $size_array[0];
            $img_array['height'] = $size_array[1];

            // Load the Library
            $CI->image_lib->clear();
            $CI->image_lib->initialize($img_array);

            // resize image
            $CI->image_lib->resize();
            // handle if there is any problem
            if (!$CI->image_lib->resize()) {
                echo $CI->image_lib->display_errors(); exit;
            }
        }
    }
}

if(!function_exists('deleteImage')){
    function deleteImage($image){
        if($image!='') {
            $imageName = explode('/', $image);
            if(count($imageName)<2){ $imageName[1] = $imageName[0]; $imageName[0] = ''; }
            if(file_exists('uploads/' . $image))
                unlink('uploads/' . $image);
            if(file_exists('uploads/' . $imageName[0] . '/_' . SMALL_IMAGE . '_' . $imageName[1]))
                unlink('uploads/' . $imageName[0] . '/_' . SMALL_IMAGE . '_' . $imageName[1]);
            if(file_exists('uploads/' . $imageName[0] . '/_' . MEDIUM_IMAGE . '_' . $imageName[1]))
                unlink('uploads/' . $imageName[0] . '/_' . MEDIUM_IMAGE . '_' . $imageName[1]);
        }
    }
}
if(!function_exists('deleteProfileImage')){
    function deleteProfileImage($image){
        if($image!='') {
            $imageName = explode('/', $image);
            if(count($imageName)<2){ $imageName[1] = $imageName[0]; $imageName[0] = ''; }
            if(file_exists('profile_images/' . $image))
                unlink('profile_images/' . $image);
            if(file_exists('profile_images/' . $imageName[0] . '/_' . SMALL_IMAGE . '_' . $imageName[1]))
                unlink('profile_images/' . $imageName[0] . '/_' . SMALL_IMAGE . '_' . $imageName[1]);
            if(file_exists('profile_images/' . $imageName[0] . '/_' . MEDIUM_IMAGE . '_' . $imageName[1]))
                unlink('profile_images/' . $imageName[0] . '/_' . MEDIUM_IMAGE . '_' . $imageName[1]);
        }
    }
}

if(!function_exists('getScore')){
    function getScore($topic_scores){
        //echo "<pre>";print_r($topic_scores);echo "</pre>";exit;
        $topic_scores_with_out_n_a = array_diff($topic_scores, array('n/a'));
        //echo "<pre>11";print_r($topic_scores_with_out_n_a);echo "</pre>";exit;
        /*if (count(array_unique($topic_scores)) === 1 && end($topic_scores) === 'n/a') {
            return 'N/A';
        }
        else if (count(array_unique($topic_scores_with_out_n_a)) === 1 && end($topic_scores_with_out_n_a) === 'green') {
            return 'Green';
        }
        else if ( count ( array_intersect($topic_scores_with_out_n_a, array('red')) ) > 0 ) {
            return 'Red';
        }
        else if ( count ( array_intersect($topic_scores_with_out_n_a, array('amber')) ) > 0 ) {
            return 'Amber';
        }
        else{
            return '';
        }*/
        if ( count ( array_intersect($topic_scores_with_out_n_a, array('red')) ) > 0 ) {
            return 'Red';
        }
        else if ( count ( array_intersect($topic_scores_with_out_n_a, array('amber')) ) > 0 ) {
            return 'Amber';
        }
        else if ( count ( array_intersect($topic_scores_with_out_n_a, array('green')) ) > 0 ) {
            return 'Green';
        }
        else if (count(array_unique($topic_scores)) === 1 && end($topic_scores) === 'n/a') {
            return 'N/A';
        }
        else{
            return '';
        }
    }
}

if(!function_exists('getScoreByCount')){
    function getScoreByCount($module_score_count){
        //if($module_score_count['topic_avg_weight_score']>0) {
            /*if ($module_score_count['no_answer_total'] > 0 && $module_score_count['red_total'] == 0 && $module_score_count['amber_total'] == 0 && $module_score_count['green_total'] == 0 & $module_score_count['na_total']==0) {
                return '';
            }
            if ($module_score_count['red_total'] > 0) {
                return 'Red';
            }
            if ($module_score_count['green_total'] > 0 && $module_score_count['red_total'] == 0 && $module_score_count['amber_total'] == 0) {
                return 'Green';
            }
            if ($module_score_count['na_total'] > 0 && $module_score_count['red_total'] == 0 && $module_score_count['amber_total'] == 0 && $module_score_count['green_total'] == 0) {
                return 'N/A';
            } else {
                return 'Amber';
            }*/
        if ($module_score_count['red_total'] > 0){
            return 'Red';
        }
        elseif($module_score_count['amber_total'] > 0){
            return 'Amber';
        }
        elseif($module_score_count['green_total'] > 0){
            return 'Green';
        }
        elseif($module_score_count['no_answer_total'] > 0){
            return '';
        }
        else{
            return '';
        }
        /*}
        else{
            return '';//no score due to questiun answers not filled
        }*/
    }
}
if(!function_exists('fileSizeFormat')) {
    function fileSizeFormat($size)
    {
        if ($size < 1024) {
            return $size . " bytes";
        } else if ($size < (1024 * 1024)) {
            $size = round($size / 1024, 1);
            return $size . " KB";
        } else if ($size < (1024 * 1024 * 1024)) {
            $size = round($size / (1024 * 1024), 1);
            return $size . " MB";
        } else {
            $size = round($size / (1024 * 1024 * 1024), 1);
            return $size . " GB";
        }

    }
}
if(!function_exists('dateFormat')) {
    function dateFormat($date)
    {
        return date('Y-m-d',strtotime($date));

    }
}
if(!function_exists('get_mime')) {
    function get_mime($file)
    {
        if (function_exists("finfo_file")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension

            $mime = finfo_file($finfo, $file);
            echo "<pre>";print_r($file);echo "</pre>";exit;
            finfo_close($finfo);
            return $mime;
        } else if (function_exists("mime_content_type")) {
            return mime_content_type($file);
        } else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
            // http://stackoverflow.com/a/134930/1593459
            $file = escapeshellarg($file);
            $mime = shell_exec("file -bi " . $file);
            return $mime;
        } else {
            return false;
        }
    }
}

if(!function_exists('pk_encrypt')){
    function pk_encrypt($response){
        if($response!=NULL) {
            $aesObj = new AES();
            $response = $aesObj->encrypt($response, 'JKj178jircAPx7h4CbGyY', 'The@1234');
        }
        return $response;
    }
}

if(!function_exists('pk_decrypt')){
    function pk_decrypt($response){
        if($response!=NULL && $response!='') {
            $aesObj = new AES();
            $response = $aesObj->decrypt($response,'JKj178jircAPx7h4CbGyY');
            if($response>=0){

            }
            else{
                $result = array('status'=>FALSE, 'message' => 'Invalid access.', 'data'=>array());
                echo json_encode($result);exit;
            }
        }
        return $response;
    }
}

if(!function_exists('validateDate')){
    function validateDate($date, $format = 'Y-m-d'){
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}

if (!function_exists('getTableHeads')) {
    function getTableHeads($table){
        switch($table){
            case 'franchilse_list':
                return array(
                    array('field' => 'franchise_code', 'header' => 'Franchise Code', 'alias' => 'franchise_code'),
                    array('field' => 'franchise_name', 'header' => 'Franchise Name', 'alias' => 'franchise_name'),
                    array('field' => 'email', 'header' => 'Contact Email', 'alias' => 'email'),
                    array('field' => 'contact_number', 'header' => 'Contact Number', 'alias' => 'contact_number'),
                    array('field' => 'city', 'header' => 'City', 'alias' => 'city'),
                    array('field' => 'created_on', 'header' =>'Created On', 'alias' => 'created_on'),
                    array('field' => 'status', 'header' =>'Status', 'alias' => 'status'),
                    array('field' => 'actions', 'header' => 'Actions', 'alias' => 'actions'),
                );
                break;
            case 'fee_structure_list':
                return array(
                     array('field'=> 'name', 'header'=> 'Fee Title','alias'=>'name' ),
                     array('field'=> 'amount', 'header'=> 'Fee Amount (₹)' ,'alias' =>'amount'),
                     array('field'=> 'term', 'header'=> 'Term' ,'alias' =>'term'),
                     array('field'=> 'discount', 'header'=> 'Discount (%)' ,'alias' =>'discount' ),
                     array('field'=> 'status', 'header'=> 'Status','alias'=>'status' ),
                     array('field'=> 'actions', 'header'=> 'Actions','alias'=>'actions' )
                );
                break;
            case 'ticket_list':
                return array(
                    array('field' => 'issue_id', 'header'=> 'Issue id' ,'alias'=>'issue_id' ),
                    array('field'=>'issue_title', 'header'=> 'Issue Title' ,'alias' =>'issue_title' ),
                    array('field'=> 'issue_type', 'header'=>'IssueType','alias'=>'issue_type' ),
                    array('field'=> 'created_by', 'header'=> 'Created By' ,'alias'=>'created_by'),
                    array('field'=> 'created_date', 'header'=> 'Created date','alais'=>'created_date' ),
                    array('field'=> 'status', 'header'=> 'Status','alais'=>'status' )
                );
                break;
            case  'students_invoice_list':
                return array(
                     array('field'=> 'name', 'header'=> 'Name' ,'alias'=>'name'),
                     array('field' => 'phone', 'header'=> 'Phone','alias'=> 'phone'),
                     array('field'=> 'month', 'header'=> 'Month','alias'=>'month' ),
                     array('field'=> 'bill', 'header'=> 'Bill' ,'alias'=>'bill'),
                     array('field'=> 'status', 'header'=> 'Status','alias'=>'status')
                );
                break;
            case 'online_user_invoice_list':
                return array(
                  array('field'=> 'name', 'header'=> 'Name' ,'alias'=>'name'),
                  array('field' => 'phone', 'header'=> 'Phone','alias'=> 'phone'),
                  array('field'=> 'month', 'header'=> 'Month','alias'=>'month' ),
                  array('field'=> 'bill', 'header'=> 'Bill' ,'alias'=>'bill'),
                  array('field'=> 'status', 'header'=> 'Status','alias'=>'status')
                );
                break;
            case 'franchise_invoice_list':
                return array(
                  array('field'=> 'name', 'header'=> 'Name' ,'alias'=>'name'),
                  array('field' => 'phone', 'header'=> 'Phone','alias'=> 'phone'),
                  array('field'=> 'month', 'header'=> 'Month','alias'=>'month' ),
                  array('field'=> 'bill', 'header'=> 'Bill' ,'alias'=>'bill'),
                  array('field'=> 'status', 'header'=> 'Status','alias'=>'status')
                );
                break;
            case 'school_mngmt_list' :
                return array(
                  array('field'=> 'code', 'header'=> 'School code','alias'=>'code' ),
                  array('field'=> 'name', 'header'=> 'School Name','alias'=>'name' ),
                  array('field'=> 'no_of_students','header'=> 'Number of Students','alias'=>'no_of_students' ),
                  array('field'=> 'phone', 'header'=> 'Phone no','alias'=>'phone' ),
                  array('field'=> 'email', 'header'=> 'Email' ,'alias'=>'email'),
                  array('field'=> 'actions', 'header'=> 'Actions','alias'=>'actions' )
                );
                break;
            case 'master_list':
               return array(
                  array('field'=> 'child_name', 'header'=> 'Master Name','alias'=>'name' ),
                  array('field'=> 'description', 'header'=> 'Master Description','alias'=>'description' ),
                  array('field'=> 'action', 'header'=> 'Actions','alias'=>'action')
               );
               break;
            case 'franchice_contact_info':
                return array(
                  array('field'=> 'contact_title', 'header'=> 'Contact Title' ,'alias' =>'contact_title'),
                  array('field'=> 'contact_name', 'header'=> 'Contact Name','alias'=>'contact_name' ),
                  array('field'=> 'contact_phone', 'header'=> 'Contact Phone' ,'alias'=>'contact_phone'),
                  array('field'=> 'actions', 'header'=> 'Actions' ,'alias'=>'actions')
                );
                break;
            case 'franchice_fee_structure_list':
                return arary(
                  array('field'=> 'name', 'header'=> 'Fee Title' ,'alias'=>'name'),
                  array('field'=> 'amount', 'header'=> 'Fee Amount (₹)','alias'=>'amount' ),
                  array('field'=> 'term', 'header'=> 'Term' ,'alias'=>'term' ),
                  array('field'=> 'discount', 'header'=> 'Discount (%)'  ,'alias'=>'discount'),
                  array('field'=> 'action', 'header'=> 'Actions' ,'alias'=>'action' )
                );
                break;
        case 'all_users_list':
                return array(
                   array('field' => 'user_name', 'header' => 'Name', 'alias' => 'user_name'),
                   array('field' => 'email', 'header' => 'Contact Email', 'alias' => 'email'),
                   array('field' => 'phone_no', 'header' => 'Contact Phone', 'alias' => 'phone_no'),
                   array('field' => 'user_role', 'header' => 'Role', 'alias' => 'user_role'),
                   array('field' => 'manager', 'header' => 'Franchise', 'alias' => 'manager'),
                   array('field' => 'status', 'header' => 'Status', 'alias' => 'status'),
                   array('field' => 'actions', 'header' => 'Actions', 'alias' => 'actions')
               );
               break;
            case 'students_list':
                return array(
                    array('field'=> 'student_name', 'header'=> 'Student Name','alias'=>'student_name'),
                    array('field'=> 'grade', 'header'=>'Grade' ,'alias' =>'grade'),
                    array('field'=> 'school_name', 'header'=> 'School','alias'=>'school' ),
                    array('field'=> 'franchise_name', 'header'=> 'Franchise','alias'=>'franchise' ),
                    array('field'=> 'contact_email', 'header'=> 'Contact Email','alias'=>'contact_email' ),
                    array('field'=> 'phone_no', 'header'=> 'Contact Number','alias'=>'phone_no' ),
                    array('field'=>'last_login', 'header'=> 'Last Login','alias' =>'last_login' ),
                    array('field'=> 'status', 'header'=> 'Status','alias'=>'status' ),
                    array( 'field'=> 'actions', 'header'=> 'Actions' ,'alias'=>'actions')
               );
               break;
            case 'online_users_list':
                return array(
                    array('field'=> 'name', 'header'=> 'Student Name','alias'=>'name'),
                    array('field'=>'manager', 'header'=> 'Parent Name','alias'=>'manager' ),
                    array('field'=> 'grade', 'header'=> 'Grade','alias'=>'grade' ),
                    array('field'=> 'city', 'header'=> 'City','alias'=>'city' ),
                    array('field'=> 'email', 'header'=>'Email','alias'=>'email' ),
                    array('field'=> 'phone', 'header'=> 'Phone','alias' =>'phone' ),
                    array('field'=> 'status', 'header'=> 'Status','alias'=>'status' ),
                    array('field'=> 'actions', 'header'=> 'Actions','alias'=>'actions' )
                );
                break;
                case 'trainer_schedule_list':
                    return array(
                        array('field'=> 'topic', 'header'=> 'Topic','alias'=>'topic'),
                        array('field'=>'date', 'header'=> 'Date','alias'=>'date' ),
                        array('field'=> 'from_time', 'header'=> 'From Time','alias'=>'from_time' ),
                        array('field'=> 'to_time', 'header'=> 'To Time','alias'=>'to_time' ),
                        array('field'=> 'actions', 'header'=> 'Actions','alias'=>'actions' )
                    );
                    break;
        }

    }
}
