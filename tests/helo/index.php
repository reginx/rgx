<?php
/*
  +----------------------------------------------------------------------
  + www app entry file
  + ---------------------------------------------------------------------
  + @date      2017-12-13 11:29:42
  + @generator RGX v1.0.0.20171212_RC
  + @cmd       php ./build.php --prefix=../tests/helo
  +----------------------------------------------------------------------
*/
define('IN_RGX', true);

// app run mode, value is debug, normal, full
define('RUN_MODE', 'debug');

// app version
define('APP_VER', '1.0.0');

// abbr
define('DS', DIRECTORY_SEPARATOR);

// app ID
define('APP_ID', 'default');

// app name
define('APP_NAME', 'www');

// app root path
define('APP_PATH', realpath('./') . DS);

// includes table models, helper classes, phar and other files
define('INC_PATH', realpath('include') . DS);

// includes runtime, template cache, data cache, attachments and other files
define('DATA_PATH', realpath('data') . DS);

// app namespace
define('NS', 'com\\www_default');

// load bootstrap file
include('../../rgx/rgx.php');