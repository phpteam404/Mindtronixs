<?php
/**
 * Created by PhpStorm.
 * User: Paramesh
 * Date: 2/28/2018
 * Time: 11:01 AM
 */

Class LdapAuthentication{

    protected $host='';
    protected $port='';
    protected $dc='';
    function __construct($params=array())
    {
        $this->host=isset($params['host'])?$params['host']:'';
        $this->port=isset($params['port'])?$params['port']:'';
        $this->dc=isset($params['dc'])?$params['dc']:'';
        $this->dc=implode(',',array_map(function($v){ return 'dc='.$v; },explode(',',$this->dc)));
    }
    /*function login($username='',$password=''){
        $ldap_dn = "uid=".$username.",".$this->dc."";
        try {
            $ldap_con = ldap_connect($this->host, $this->port);
            ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

            if (@ldap_bind($ldap_con, $ldap_dn, $password))
                return true;
            else
                return false;
        }catch(Exception $e){
            return false;
        }

    }*/
    function login($username='',$password=''){
        $ldap_status=false;$ldap_message='Something went wrong! Please try again';
        $ldap_dn = "uid=".$username.",".$this->dc."";
        try {
            $ldap_con = @ldap_connect($this->host, $this->port);
            if($ldap_con) {
                ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);

                //if (@ldap_bind($ldap_con, $ldap_dn, $password))
                if (@ldap_bind($ldap_con, $username, $password)) {
                    $ldap_status = true;
                    $ldap_message = 'Success';
                }
                else{
                    $ldap_status=false;
                    $ldap_message=ldap_error($ldap_con);
                }
            }
            else{
                $ldap_status=false;
                $ldap_message=ldap_error($ldap_con);
            }
        }catch(Exception $e){
            $ldap_status=false;
            $ldap_message='Something went wrong! Please try again';
        }
        return array('status'=>$ldap_status,'message'=>$ldap_message);
    }
    function logintest($username='',$password=''){
        $username='testuserscp@with-services.com';
        $password='Source20181';
        $ldap_dn = "ou=AADDC Users,".$this->dc."";
        echo "<pre>";
        echo "Host: ".$this->host."<br>";
        echo "Port: ".$this->port."<br>";
        echo "DC: ".$this->dc."<br>";
        echo "Username: ".$username."<br>";
        echo "Password: ".$password."<br>";
        echo "Bind RDN: ".$ldap_dn."<br>";
        $ldaptree    = "ou=AADDC Users,".$this->dc."";
        echo "Tree: ".$ldaptree."<br>";
        //ldapsearch -x -H ldaps://ldaps.with-services.com -w Source2018! -D " testuserscp@with-services.com " -x -vvvv -b "ou=AADDC Users,dc=with-services,dc=com" cn=testus*
        try {
            $ldap_con = @ldap_connect($this->host);
            if($ldap_con) {
                ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
                echo "<pre>Response BIND:" . "<br>";
                // $ldap_dn,$password

                if (@ldap_bind($ldap_con, 'testuserscp@with-services.com', 'Source2018!')) {
                    $result = ldap_search($ldap_con, $ldaptree, "(cn=test*)") or die ("Error in search query: " . ldap_error($ldap_con));
                    $data = ldap_get_entries($ldap_con, $result);
                    echo "<pre>Result:" . "<br>";
                    var_dump($data);

                    exit;
                }
                else{
                    echo "LDAP-Errno: " . ldap_errno($ldap_con) . "<br />\n";
                    echo "LDAP-Error: " . ldap_error($ldap_con) . "<br />\n";
                    die("Argh!<br />\n");
                }
                exit;
                if (@ldap_bind($ldap_con, $ldap_dn, $password))
                    return true;
                else
                    return false;
            }
            else{
                echo "LDAP-Errno: " . ldap_errno($ldap_con) . "<br />\n";
                echo "LDAP-Error: " . ldap_error($ldap_con) . "<br />\n";
                die("Argh!<br />\n");
            }
        }catch(Exception $e){
            return false;
        }

    }
}