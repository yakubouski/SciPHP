<?php
namespace Sci;
require_once 'sci/sci.php';

Lib('db','html');

function rowAdd($rNo,&$Arr) {
    !isset($Arr[$rNo]) && $Arr[$rNo] = [$rNo,0,0,];
}

\Html::Main(function(DB\Dsn $Db){

    $Rs = $Db->SqlAssocRecordset('SELECT first_visit,list_visits FROM data.data_set_1',function(&$r,&$rs){

        $visits = explode(',',$r['list_visits']);
        $prev = intval($r['first_visit']);
        $first = intval($r['first_visit']);
        for($i=0;$i<count($visits);$i++) {
            rowAdd($i+1,$rs);
            $rs[$i+1][1] += 1;
        }
        $rs[$i][2] += 1;
    });

    ksort($Rs);

    $table = Table();

    $table->AddColumns('Visit','Count','Exists');
    $table->SetRows($Rs);

    print $table->Html("TestTable");

},Dsn('p:mysql','root','zsq@!wax','localhost','data'));
