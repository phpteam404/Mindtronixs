<?php
/**
 * Created by PhpStorm.
 * User: Paramesh
 * Date: 2/27/2018
 * Time: 11:28 AM
 */
class MY_Input extends CI_Input {
    var $CI;
    public function __construct()
    {
        parent::__construct();
    }
    public function post($index = NULL, $xss_clean = NULL)
    {
        $this->CI =& get_instance();
        $strip_tags=true;
        $xss_clean=false;
        if(in_array($this->CI->router->fetch_method(),array('testemailtemplate','emailTemplateUpdate'))){
            $strip_tags=false;
            $xss_clean=false;
        }

        return $this->_fetch_from_array($_POST, $index, $xss_clean,$strip_tags);

    }
    protected function _fetch_from_array(&$array, $index = NULL, $xss_clean = NULL,$strip_tags=NULL)
    {
        is_bool($xss_clean) OR $xss_clean = $this->_enable_xss;


        // If $index is NULL, it means that the whole $array is requested
        isset($index) OR $index = array_keys($array);

        // allow fetching multiple keys at once
        if (is_array($index))
        {
            $output = array();
            foreach ($index as $key)
            {
                $output[$key] = $this->_fetch_from_array($array, $key, $xss_clean,$strip_tags);
            }

            return $output;
        }

        if (isset($array[$index]))
        {
            $value = $array[$index];
        }
        elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        {
            $value = $array;
            for ($i = 0; $i < $count; $i++)
            {
                $key = trim($matches[0][$i], '[]');
                if ($key === '') // Empty notation will return the value as array
                {
                    break;
                }

                if (isset($value[$key]))
                {
                    $value = $value[$key];
                }
                else
                {
                    return NULL;
                }
            }
        }
        else
        {
            return NULL;
        }

        if($strip_tags === TRUE) {
            if (!is_array($value))
                $value = strip_tags($value,'<p><span>');
            if (is_array($value)) {
                while (list($key) = each($value)) {
                    if (!is_array($value[$key]))
                        $value[$key] = strip_tags($value[$key],'<p><span>');
                    if (is_array($value[$key])){
                        while (list($key1) = each($value[$key])) {
                            if (!is_array($value[$key][$key1]))
                                $value[$key][$key1] = strip_tags($value[$key][$key1],'<p><span>');
                        }
                    }
                }
            }
        }

        return ($xss_clean === TRUE)
            ? $this->security->xss_clean($value)
            : $value;
    }
}
