<?php
namespace Sci;
require_once 'sci/sci.php';
$mysql = Dsn('p:mysql','root','zsq@!wax','localhost','event-pro');

$table = Table();
$table->fromSql($mysql,"select 1 as `v` union all select 2");
$table->AddColumns('norm');

foreach($table[null]['v'] as $i=>$v) {
    $table[$i]['norm'] = $v/10;
}

var_export($table);
//$table->fromSql($mysql,"");