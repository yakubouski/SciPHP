<?php
ini_set('memory_limit', '2048M');
ini_set('display_errors','on');
ini_set('display_startup_errors','on');
set_time_limit(0);
ignore_user_abort(1);

require_once 'sci/sci.php';

\Sci\Lib('db','testcase');

$Params = [
    'Db'=>\Sci\Dsn('p:mysql','root','zsq@!wax','localhost','data')
];

#\TestCase::Run('test/test-vector.php',$Params);
#\TestCase::Run('test/test-multilayer.php',$Params);
#\TestCase::Run('test/test-kohonen.php',$Params);
#\TestCase::Run('test/test-kohonen - wifi.php',$Params);
#\TestCase::Run('test/test-decisiontree - wifi.php',$Params);
\TestCase::Run('test/test-multilayer - wifi.php',$Params);
