<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');


/* Define API Credentials*/
define('API_USERNAME','apiuser@CHSPharm');
define('API_PASSWORD','rtgi2015!');
define('TOTAL_ADDRESSES_PROCESSED_PER_REQUEST',80);
define('WIPUserTaskReasonSerializeArray', serialize(array(113,171,173,174)));  //Ready For Shipping WIP Status of sales order
define('FieldStorageNumber_For_Customfield_Order_Labels_Required',8);  // FieldStorageNumber of Custom-field Order Labels Required
define('Default_Value_Of_Customfield_Order_Labels_Required',3);  // FieldStorageNumber of Custom-field Order Labels Required


/* End of file constants.php */
/* Location: ./application/config/constants.php */