<?php
error_reporting(0);
ini_set('display_errors', 0);
$currentCookieParams = session_get_cookie_params();
session_set_cookie_params(
    $currentCookieParams["lifetime"],
    $currentCookieParams["path"],
    $currentCookieParams['domain'],
    true,
    $currentCookieParams["httponly"]
);
define('ENV','DEV');
switch(ENV)
{
    case 'DEV':
        $base_host = sprintf('%s://%s/',$_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http',$_SERVER['SERVER_NAME'])."sourcing-cockpit/";
        /* site urls start */
        define('WEB_BASE_URL', $base_host.'html/');
        define('REST_API_URL', $base_host.'rest/');
        define('PAGING_LIMIT', '10');
        /* site urls ends */

        /*database configuration starts*/ //
        //define('DB_HOST', 'VlFJczlYTDRMQ1Q1UVpJc2dtTHZxUT09'); //0.4
        //define('DB_HOST', 'eFhVT0JSL1NDMHFpQ1FUbHpwMHQ2QT09'); //local
        //define('DB_USERNAME', 'MjBoZjJ6MGhoOUg1eHpaOCs0cDFuUT09'); //root
        //define('DB_PASSWORD', 'VzBBdW5qRU43aXQ4UXZUdUtZSUd6QT09');
        define('DB_HOST', 'NjAyT1VsWlNsL2FhT3orUzdzQ3dXZz09'); //0.3
        define('DB_USERNAME', 'LzNmUTk4c2hyWkZsUERqL1dJNWxvdz09'); //app_user
        define('DB_PASSWORD', 'OUUzSVVQWnhBMTRYdCtUcTNMbUFlZz09'); //123456
        //define('DB_NAME', 'TzJ3cHJTWVVQaWdmZ3V2UG1PZXpBejB6NmxSM1ZWaUV4ZFlxZ3JaWkdkND0='); //june28 :dev
        define('DB_NAME', 'Mk9GSXZJZlYrUDEvMVd0VmVEQWVhS0NZaVdkeHBHZ2JpdVpVd3o4TUlyRT0='); //withdev_nov28 13 :dev
        //define('DB_NAME', 'cEJPeTgxY0gxUmZHOE4vUUo1MkNmdz09'); //withdevjuly19 :dev
        /* database configuration ends*/
        define("SITE_ACCESS_TOKEN_EXPIRY","600");
        
        /* Image sizes */
        define('SMALL_IMAGE','70x33');
        define('MEDIUM_IMAGE','500x500');
        /* Image sizes */

        /*mongo server urls starts*/
        define('MONGO_SERVICE_URL', 'http://183.82.97.231:9085/');
        define('LOG_AUTH_KEY', 'F%DTBh*nY9Kq@QdWc');
        /*mongo server urls ends*/

        /* aes encryption configuration starts */
        //define('AES_KEY', 'nwXcTJVzFDQIEpKWSO88m73ElDJFJ1a5YJVWDYsG');//old
        define('AES_KEY', 'JKj178jircAPx7h4CbGyYVV6u0A1JF7YN5GfWDWx');
        define('DATA_ENCRYPT',FALSE);
        /* aes encryption configuration ends */

        define('EXCEL_UPLOAD_SIZE','10485760');
        define('IMAGE_UPLOAD_SIZE','10485760');
        //File system path
        //define('FILE_SYSTEM_PATH','/var/www/app_files/');
        define('FILE_SYSTEM_PATH','http://192.168.0.63/Mindtronixs/rest/');
        define('MAX_INVALID_PASSWORD_ATTEMPTS',5);

        define('SMTP_MAIL_FROM','app.mazic@gmail.com');
        define('SMTP_MAIL_PASSWORD','app_mazic.');
        define('SMTP_MAIL_NOREPLY','app.mazic@gmail.com');

        define('SEND_GRID_API_KEY', 'U0cuVms2Z1laSjdSdXlkUHU0OFhPVjJJUS4yZDgxNG12UW9SeTFWVjFCSlF0Y0xpX2NzWVE5LWd5SGY5dkxFbDNGakhN');//new code
        define('SEND_GRID_FROM_EMAIL', 'app.mazic@gmail.com');
        define('SEND_GRID_FROM_NAME', 'Sourcing Cockpit');
        define('DOCUMENT_PATH', 'http://192.168.0.63/Mindtronixs/rest/uploads/');

                    define('EMAIL_HEADER_CONTENT', "<!doctype html>
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
<title>Email Template</title>
</head>
<body style=\"padding:0;margin:0; font-family:arial;\">
<table style=\"width: 100%;text-align: left;\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\">
<tbody>
<tr>
<td><img src=\"{logo}\" alt=\"banner\" style=\"width: 100px;\"></td>
</tr>
<tr>
<td colspan=\"2\"  style=\"opacity:0.4;\"><hr color=\"#e04826;\" size=\"1\"></td>        </tr>
<tr><td style=\"font-size: 12px;text-align: left;padding-left:10px;\">");
                    define('EMAIL_FOOTER_CONTENT', "</td></tr>

<tr><td colspan=\"2\" style=\"opacity:0.4;\"><hr color=\"#e04826;\" size=\"1\"></td>
</tr><tr><td colspan=\"2\" style=\"font-size: 11px;color: #757575;float: left;padding-left:10px;\"><i></i><p style=\"color: #757575;\">© Copyright 2017<br>with BVBA (HQ)Jan Van Rijswijcklaan 135<br>2018 Antwerp Belgium<br>Parking: Nationale Bank<br>+32 (0)477 77 25 12</p></td></tr></tbody></table>
</body>
</html>");

        break;
}

?>
