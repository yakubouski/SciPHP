<?php
namespace Sci;
require_once 'sci/sci.php';
$mysql = Dsn('p:mysql','root','zsq@!wax','localhost','event-pro');

//$arr = $mysql->SqlRecordset("select 1 as `v` union all select 2");

$table = Table();
$table->
$table->fromSql($mysql,"select 1 as `v` union all select 2");

$c = $table[0];
var_export($c['v']);
//$table->fromSql($mysql,"");