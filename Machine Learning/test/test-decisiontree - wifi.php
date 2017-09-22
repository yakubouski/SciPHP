<?php
use Sci\Math\Classification\DecisionTree as Machine;
use Sci\Math\Vector as Vector;

$DataSet = [];

$Dir = __DIR__.DIRECTORY_SEPARATOR."data/";
$DataSetName = __DIR__.DIRECTORY_SEPARATOR."data/decisiontree.json";

if(1 || !file_exists($DataSetName)) {
    $NormDataRange = [];
    $DataSet = $Db->SqlAssocRecordset("
        SELECT
	        id,region `region`, round(log(time)) `time`, round(log(avg_time)) `avg`,
            round(log2(download)) `download`,
            round(log2(upload)) `upload`,
	        (num_visits) `num_visits`,list_visits
        FROM data.data_set_1 /* ORDER BY RAND() */ LIMIT 1000;",function($r,&$rs){

        $vi = [
                'time'=>floatval($r['time']),
                'avg'=>floatval($r['avg']),
                'download'=>floatval($r['download']),
                'upload'=>floatval($r['upload']),
                'num_visits'=>floatval($r['num_visits']),
                'period'=>0,
                'region'=>intval($r['region']),
                'id'=>$r['id']];

        $Visits = explode(',',$r['list_visits']);
        $first = reset($Visits);

        for($i=1;$i<count($Visits);$i++) {
            $period = $Visits[$i] - $first;
            $first = $Visits[$i];
            $vi['period'] += $period;
        }
        $vi['period'] = floor($vi['period']/count($Visits));
        $rs[] = $vi;


    });

    file_put_contents($DataSetName,json_encode([
        $DataSet
    ],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
else {
    list($DataSet) = json_decode(file_get_contents($DataSetName),true);
}

$nn = new Machine();

$data =[
    ['person'=>'Homer', 'hairLength'=>0, 'weight'=>250, 'age'=>36, 'sex'=>'male'],
    ['person'=>'Marge', 'hairLength'=>10, 'weight'=>150, 'age'=>34, 'sex'=>'female'],
    ['person'=>'Bart', 'hairLength'=>2, 'weight'=>90, 'age'=>10, 'sex'=>'male'],
    ['person'=>'Lisa', 'hairLength'=>6, 'weight'=>78, 'age'=>8, 'sex'=>'female'],
    ['person'=>'Maggie', 'hairLength'=>4, 'weight'=>20, 'age'=>1, 'sex'=>'female'],
    ['person'=>'Abe', 'hairLength'=>1, 'weight'=>170, 'age'=>70, 'sex'=>'male'],
    ['person'=>'Selma', 'hairLength'=>8, 'weight'=>160, 'age'=>41, 'sex'=>'female'],
    ['person'=>'Otto', 'hairLength'=>10, 'weight'=>180, 'age'=>38, 'sex'=>'male'],
    ['person'=>'Krusty', 'hairLength'=>6, 'weight'=>200, 'age'=>45, 'sex'=>'male']
];

$nn->Training($DataSet,['time','avg','download','upload','num_visits','period','region'],'period',4);

$Tree = [
    ['id','parent','percent'],
    [['v'=>'0-',  'f'=>"{$nn->TreeNodes['Attribute']}{$nn->TreeNodes['Predict']}{$nn->TreeNodes['Pivot']}"],'',0]
];

function TraverseTree(&$Tree,$Parent,$Leaf,$Index) {

    if(isset($Leaf['Left']['Branch'])) {
        $Tree[] = [['v'=>($idLeft = $Parent.'.'.$Index++),  'f'=>"{$Leaf['Left']['Branch']['Attribute']}{$Leaf['Left']['Branch']['Predict']}{$Leaf['Left']['Branch']['Pivot']}"],$Parent,round($Leaf['Left']['Branch']['%']*100)];
    }
    else {
        $Tree[] = [['v'=>($Parent.'.'.$Index++),  'f'=>implode(',',array_keys($Leaf['Left']['Category']['Frequencies']))],$Parent,1];
    }
    if(isset($Leaf['Right']['Branch'])) {
        $Tree[] = [['v'=>($idRight = $Parent.'.'.$Index++),  'f'=>"{$Leaf['Right']['Branch']['Attribute']}{$Leaf['Right']['Branch']['Predict']}{$Leaf['Right']['Branch']['Pivot']}"],$Parent,round($Leaf['Right']['Branch']['%']*100)];
    }
    else {
        $Tree[] = [['v'=>($Parent.'.'.$Index++),  'f'=>implode(',',array_keys($Leaf['Right']['Category']['Frequencies']))],$Parent,1];
    }


    if(isset($Leaf['Left']['Branch'])) {
        TraverseTree($Tree,$idLeft,$Leaf['Left']['Branch'],$Index);
    }
    elseif(isset($Leaf['Left']['Category'])) {
        //$Tree[] = [implode(', ',array_keys($Leaf['Left']['Category']['Frequencies'])),$Parent,];
    }
    if(isset($Leaf['Right']['Branch'])) {
        TraverseTree($Tree,$idRight,$Leaf['Right']['Branch'],$Index);
    }
    elseif(isset($Leaf['Right']['Category'])) {
       // $Tree[] = [implode(', ',array_keys($Leaf['Right']['Category']['Frequencies'])),$Parent,];
    }

    if(isset($Leaf['Category'])) {
        $Tree[] = [['v'=>($Parent.'.'.$Index++),  'f'=>implode(',',array_keys($Leaf['Category']['Frequencies']))],$Parent,0];
    }
}

TraverseTree($Tree,'0-',$nn->TreeNodes,0);

printChartTree($Tree);
//$nn->Training($data,['hairLength','weight','age',['sex','==']],'sex');
