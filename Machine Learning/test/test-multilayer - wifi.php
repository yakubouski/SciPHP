<?php
use Sci\Math\NeuralNetwork\Multilayer as Machine;

$Dir = __DIR__.DIRECTORY_SEPARATOR."data/";
$Name = basename(__FILE__.'.php');
$DataSetName = __DIR__.DIRECTORY_SEPARATOR."data/{$Name}-dataset.json";
$WeightsSetName = __DIR__.DIRECTORY_SEPARATOR."data/{$Name}-weights.json";

/*
class BigDataTable implements \ArrayAccess, \Countable {


}
*/

$nn = new Machine(9,7,[5]);

if(!file_exists($DataSetName)) {

    $PrevCache = [];

    function divide($a,$b) {
        return $b ? ($a/$b) : 0.0;
    }

    function NormalizeTable(&$Table,$Columns) {
        $Columns = array_fill_keys($Columns,[null,null,null]);
        foreach($Table as $row) {
            foreach($Columns as $c=>$minmax) {
                (is_null($minmax[0]) || $minmax[0] > $row[$c]) && ($minmax[0] = $row[$c]);
                (is_null($minmax[1]) || $minmax[1] < $row[$c]) && ($minmax[1] = $row[$c]);
                $minmax[2] = $minmax[1] - $minmax[0];
                $Columns[$c] = $minmax;
            }
        }

        foreach($Table as &$row) {
            foreach($Columns as $c=>$minmax) {
                $row[$c] = divide($row[$c] - $minmax[0],$minmax[2]);
            }
        }

        return $Columns;
    }

    $DataSet = $Db->SqlAssocRecordset("
	        SELECT
                uid,region+0 `region`,sess_period,sess_time-1 `sess_time`,sess_length `sess_length`,ROUND(sess_download/1024) `sess_download`, ROUND(sess_upload/1024) sess_upload
            FROM data.data_set_2 s
            ORDER BY uid LIKE '375%' DESC, sess_period ASC",function($r,&$rs) use (&$PrevCache){


        !isset($PrevCache[$r['uid']]) && ($PrevCache[$r['uid']] = ['no'=>1,'prev_length'=>0,'sess_period'=>$r['sess_period'],'prev_visit'=>null]);

        $vi = [
                'sess_no'=>$PrevCache[$r['uid']]['no'] ++,
                'region'=>intval($r['region']),
                'sess_ampm'=>intval($r['sess_time']),
                'sess_time_prev'=>intval($PrevCache[$r['uid']]['prev_length']),
                'sess_time'=>intval($r['sess_length']),
                'sess_period'=>intval($r['sess_period'] - $PrevCache[$r['uid']]['sess_period']),
                'sess_download'=>intval($r['sess_download']),
                'sess_upload'=>intval($r['sess_upload']),
                'sess_dwupratio'=> intval(intval($r['sess_download']) > intval($r['sess_upload'])),
                'next_visit_period'=>[1,0,0,0,0,0,0],
                'uid'=>$r['uid'],
           ];
        $PrevCache[$r['uid']]['sess_period'] = $r['sess_period'];
        $PrevCache[$r['uid']]['prev_period'] = $vi['sess_period'];
        $PrevCache[$r['uid']]['prev_length'] = $vi['sess_time'];

        !is_null($PrevCache[$r['uid']]['prev_visit']) && $rs[$PrevCache[$r['uid']]['prev_visit']]['next_visit_period'] = [
            0,
            intval($vi['sess_period']<=2),
            intval($vi['sess_period']>=3 && $vi['sess_period']<=4),
            intval($vi['sess_period']>=5 && $vi['sess_period']<=7),
            intval($vi['sess_period']>=8 && $vi['sess_period']<=15),
            intval($vi['sess_period']>=16 && $vi['sess_period']<=30),
            intval($vi['sess_period']>30),
        ];
        $PrevCache[$r['uid']]['prev_visit'] = count($rs);
        $rs[] = $vi;
    });

    NormalizeTable($DataSet,['sess_no','region','sess_ampm','sess_time_prev','sess_time','sess_period','sess_download','sess_upload','sess_dwupratio']);

    file_put_contents($DataSetName,json_encode($DataSet,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

    $nn->RandomWeights(-0.1,0.35);
}
else {
    $DataSet = json_decode(file_get_contents($DataSetName),true);
    if(file_exists($WeightsSetName)) {
        list(,,$InitialWeights) = json_decode(file_get_contents($WeightsSetName),true);
        $nn->SetWeights($InitialWeights);
    }
    else {
        $nn->RandomWeights(-0.1,0.35);
    }
}

//printTable('',[1,2,3,4,5,6,7,8,9,10,11],$DataSet);

if(1) {

    $time_start = microtime(true);
    $ePrev = -1;

    $nn->Training(function($n,$epoch,$nn)use(&$DataSet,&$ePrev,$Dir,$time_start,$WeightsSetName){

        if($ePrev != $epoch) {
            $ePrev = $epoch;
            file_put_contents($Dir.'training.log',sprintf("%d - %.2f - %.3f\n",$epoch,(microtime(true) - $time_start),$nn->MSE),FILE_APPEND);
            file_put_contents($WeightsSetName,json_encode([$epoch,$nn->getWeights()],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        }

        return [
            [
                $DataSet[$n]['sess_no'],$DataSet[$n]['region'],$DataSet[$n]['sess_ampm'],$DataSet[$n]['sess_time_prev'],
                $DataSet[$n]['sess_time'],$DataSet[$n]['sess_period'],$DataSet[$n]['sess_download'],$DataSet[$n]['sess_upload'],$DataSet[$n]['sess_dwupratio']
            ],
            $DataSet[$n]['next_visit_period']
            ];
    },count($DataSet),2,0.01,1,0.25);

    file_put_contents($Dir.'training.log',sprintf("%d - %.2f - %.3f\n",$nn->NumEpoch,(microtime(true) - $time_start),$nn->MSE),FILE_APPEND);
    file_put_contents($WeightsSetName,json_encode([$nn->NumEpoch,$nn->MSE,$nn->getWeights()],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

    /*
    $WNames = ['N{|epoch}','Err{|MSE}','N{|neurons}','N{|weights}','N{|inputs}','N{|outputs}',];

    foreach(($W = $nn->getWeights()) as $k=>$w) {
        $WNames[] = "w{|$k}";
    }
    printTable('Dataset',$WNames,[[$nn->NumEpoch,$nn->MSE,$nn->NumNeurons,$nn->NumWeights,$nn->NumInputs,$nn->NumOutputs]],[$W]);
*/
}
$O = [];
$R = [];

for($i=0;$i<5;$i++) {
    $n = random_int(0,count($DataSet)-1);
    $O[] = $DataSet[$n]['next_visit_period'];
    $R[] = $nn->Compute([
            $DataSet[$n]['sess_no'],$DataSet[$n]['region'],$DataSet[$n]['sess_ampm'],$DataSet[$n]['sess_time_prev'],
            $DataSet[$n]['sess_time'],$DataSet[$n]['sess_period'],$DataSet[$n]['sess_download'],$DataSet[$n]['sess_upload'],$DataSet[$n]['sess_dwupratio']
        ],2.0);
}

printTable('Dataset',[
    'O{|0}','O{|1-2}','O{|3-4}','O{|5-7}','O{|8-15}','O{|16-30}','O{|>30}',
    'Y{|0}','Y{|1-2}','Y{|3-4}','Y{|5-7}','Y{|8-15}','Y{|16-30}','Y{|>30}'
    ],$O,$R);
