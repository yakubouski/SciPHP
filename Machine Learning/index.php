<?php
namespace Sci;
require_once 'sci/sci.php';

Lib('db','html');

\Html::Main(function($Db){

    $table = Table();
    $table->fromSql($Db,"select 1 as `v` union all select 2");
    $table->AddColumns('norm');

    foreach($table[null]['v'] as $i=>$v) {
        $table[$i]['norm'] = $v/10;
    }

    print $table->Html("TestTable");

},Dsn('p:mysql','root','zsq@!wax','localhost','event-pro'));
