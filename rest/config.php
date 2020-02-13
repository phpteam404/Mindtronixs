<?php
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors','1');
ini_set('memory_limit','256M');
define('ENV','DEV');
$base_host = "http://192.168.0.63/test_mindtronix/";
define('WEB_BASE_URL', $base_host);
define('REST_API_URL', $base_host.'rest/');
define('PAGING_LIMIT', '10');
define('LOG_AUTH_KEY', '');
define('AES_KEY', '');
define('DATA_ENCRYPT',FALSE);
define('EXCEL_UPLOAD_SIZE','5097152');
define('IMAGE_UPLOAD_SIZE','5097152');
define('PASSWORD_EXPIRY_DAYS',17);
define('PASSWORD_NOTIFICATION_DAYS',10);
define('SITE_ACCESS_TOKEN_EXPIRY',600);//in seconds
switch(ENV)
{
case 'DEV':
/* database configuration ends */
define('DB_HOST', '192.168.0.63');//10.102.148.233
define('DB_USERNAME', 'naresh');//admin
define('DB_PASSWORD', '123456');//the@123
define('DB_NAME', 'mindtronics');
/* database configuration ends*/
break;

case 'PROD':

define('DB_HOST', '139.59.59.231');//Here you need to mention your ip address
define('DB_USERNAME', 'admin');//admin
define('DB_PASSWORD', 'the@123');//the@123
define('DB_NAME', 'bk_prd_oct_15'); // Here you need to give your database name
//define('LOG_DB_NAME', '');
break;


case 'PRASAD':

define('DB_HOST', '');//Here you need to mention your ip address
define('DB_USERNAME', '');//admin
define('DB_PASSWORD', '');//the@123
define('DB_NAME', ''); // Here you need to give your database name
define('LOG_DB_NAME', '');
break;

case 'PARVATHI':
define('DB_HOST', '');//Here you need to mention your ip address
define('DB_USERNAME', '');//admin
define('DB_PASSWORD', '');//the@123
define('DB_NAME', ''); // Here you need to give your database name
//define('LOG_DB_NAME', '');
break;

case 'NARESH':
define('DB_HOST', '');//Here you need to mention your ip address
define('DB_USERNAME', '');//admin
define('DB_PASSWORD', '');//the@123
define('DB_NAME', ''); // Here you need to give your database name
define('LOG_DB_NAME', '');
break;

}

?>