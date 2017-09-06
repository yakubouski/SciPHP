<?php
ini_set('memory_limit', '2048M');
ini_set('display_errors','on');
ini_set('display_startup_errors','on');

require_once 'sci/sci.php';

\Sci\Lib('db','testcase');

$Params = [
    'Db'=>\Sci\Dsn('p:mysql','root','zsq@!wax','localhost','data')
];

//\TestCase::Run('test/test-vector.php',$Params);
\TestCase::Run('test/test-machine.php',$Params);
