<?php
use Sci\Math\NeuralNetwork\Kohonen as Machine;
use Sci\Math\Vector as Vector;

$NormalizedSet = [];
$DataSet = [];
$InitialWeights = [];

$NumInput = 7;
$NumClusters = 5;
$NumWeights = $NumInput * $NumClusters;
$Dir = __DIR__.DIRECTORY_SEPARATOR."data/";
$DataSetName = __DIR__.DIRECTORY_SEPARATOR."data/dataset-{$NumInput}x{$NumClusters}.json";
$WeightsSetName = __DIR__.DIRECTORY_SEPARATOR."data/weights-{$NumInput}x{$NumClusters}.json";

if(!file_exists($DataSetName)) {
    $NormDataRange = [];
    $DataSet = $Db->SqlAssocRecordset("
        SELECT
	        id,region=2 `region`, log(time) `time`, log(avg_time) `avg`,
            log2(download) `download`,
            log2(upload) `upload`,
	        log(num_visits) `num_visits`,list_visits
        FROM data.data_set_1 ORDER BY RAND();",function($r,&$rs) use (&$NormDataRange){

        $vi = [floatval($r['time']),floatval($r['avg']),floatval($r['download']),floatval($r['upload']),floatval($r['num_visits']),0,intval($r['region']),$r['id']];

        $Visits = explode(',',$r['list_visits']);
        $first = reset($Visits);

        for($i=1;$i<count($Visits);$i++) {
            $period = $Visits[$i] - $first;
            $first = $Visits[$i];
            $vi[5] += $period;
        }
        $vi[5] = log(1 + ($vi[5]/count($Visits)));
        $rs[] = $vi;

        (!isset($NormDataRange[0]['min']) || $NormDataRange[0]['min'] > $vi[0]) && $NormDataRange[0]['min'] = $vi[0];
        (!isset($NormDataRange[0]['max']) || $NormDataRange[0]['max'] < $vi[0]) && $NormDataRange[0]['max'] = $vi[0];

        (!isset($NormDataRange[1]['min']) || $NormDataRange[1]['min'] > $vi[1]) && $NormDataRange[1]['min'] = $vi[1];
        (!isset($NormDataRange[1]['max']) || $NormDataRange[1]['max'] < $vi[1]) && $NormDataRange[1]['max'] = $vi[1];

        (!isset($NormDataRange[2]['min']) || $NormDataRange[2]['min'] > $vi[2]) && $NormDataRange[2]['min'] = $vi[2];
        (!isset($NormDataRange[2]['max']) || $NormDataRange[2]['max'] < $vi[2]) && $NormDataRange[2]['max'] = $vi[2];

        (!isset($NormDataRange[3]['min']) || $NormDataRange[3]['min'] > $vi[3]) && $NormDataRange[3]['min'] = $vi[3];
        (!isset($NormDataRange[3]['max']) || $NormDataRange[3]['max'] < $vi[3]) && $NormDataRange[3]['max'] = $vi[3];

        (!isset($NormDataRange[4]['min']) || $NormDataRange[4]['min'] > $vi[4]) && $NormDataRange[4]['min'] = $vi[4];
        (!isset($NormDataRange[4]['max']) || $NormDataRange[4]['max'] < $vi[4]) && $NormDataRange[4]['max'] = $vi[4];

        (!isset($NormDataRange[5]['min']) || $NormDataRange[5]['min'] > $vi[5]) && $NormDataRange[5]['min'] = $vi[5];
        (!isset($NormDataRange[5]['max']) || $NormDataRange[5]['max'] < $vi[5]) && $NormDataRange[5]['max'] = $vi[5];

        (!isset($NormDataRange[6]['min']) || $NormDataRange[6]['min'] > $vi[6]) && $NormDataRange[6]['min'] = $vi[6];
        (!isset($NormDataRange[6]['max']) || $NormDataRange[6]['max'] < $vi[6]) && $NormDataRange[6]['max'] = $vi[6];

    });

    foreach($DataSet as $d) {
        $nd = [];
        for($i=0;$i<$NumInput;$i++) {
            if($i==4) {
                $nd[] = ($d[$i]/$NormDataRange[$i]['max']);
            }
            else {
                $nd[] = ($d[$i] - $NormDataRange[$i]['min']) / ($NormDataRange[$i]['max'] - $NormDataRange[$i]['min']);
            }

        }
        $NormalizedSet[] = $nd;
    }

    file_put_contents($DataSetName,json_encode([
        $InitialWeights = (new Vector($NumWeights))->FillRandom(0.1,0.6)->Values,
        $NormalizedSet,
        $DataSet
    ],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
else {
    list($InitialWeights,$NormalizedSet,$DataSet) = json_decode(file_get_contents($DataSetName),true);
    if(file_exists($WeightsSetName)) {
        list(,$InitialWeights) = json_decode(file_get_contents($WeightsSetName),true);
    }
}

$nn = new Machine($NumInput,$NumClusters,$InitialWeights);

if(!file_exists($WeightsSetName)) {

    $time_start = microtime(true);
    $ePrev = -1;
    $nn->Training(function($n,$epoch,$nn)use(&$NormalizedSet,$time_start,&$ePrev,$Dir){
        if($ePrev != $epoch) {
            $ePrev = $epoch;
            file_put_contents($Dir.'training.log',sprintf("%d - %.2f\n",$epoch,(microtime(true) - $time_start)),FILE_APPEND);
        }
        return $NormalizedSet[$n];
    },count($DataSet),0.20,0.005);

    $time = round((microtime(true) - $time_start),2);
    echo "<div>Обучение {$nn->NumEpoch} эпохи - {$time} сек.</div>";

    file_put_contents($WeightsSetName,json_encode([
            $InitialWeights,
            $nn->Weights
    ],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

else {

    $R = [];
    $Gr = [];

    foreach($NormalizedSet as $n=>$i) {
        $c = $nn->Cluster($i,$Clusters);
        $R[$c][] = $n+1;

        $Gr[$c]['Общее время'] = ($Gr[$c]['Общее время']??0.0) + $DataSet[$n][0];
        $Gr[$c]['Среднее время'] = ($Gr[$c]['Среднее время']??0.0) + $DataSet[$n][1];
        $Gr[$c]['Download'] = ($Gr[$c]['Download']??0.0) + $DataSet[$n][2];
        $Gr[$c]['Upload'] = ($Gr[$c]['Upload']??0.0) + $DataSet[$n][3];
        $Gr[$c]['Сессий'] = ($Gr[$c]['Сессий']??0.0) + $DataSet[$n][4];
        $Gr[$c]['Интервал сессий'] = ($Gr[$c]['Интервал сессий']??0.0) + $DataSet[$n][5];
        $Gr[$c]['Регион'][$DataSet[$n][6]] = $DataSet[$n][6];
    }

    foreach($Gr as $c=>$D) {
        $cnt = count($R[$c]);
        printf('Кластер №%d (%d)<br>',$c,$cnt);
        foreach($D as $nm=>$v) {
            if($nm !== 'Регион') {
                echo str_repeat('&nbsp;',4); printf('%s=(%.2f)<br>',$nm,exp($v/$cnt));
            }
            else {
                echo str_repeat('&nbsp;',4); printf('%s=(%s)<br>',$nm,implode(',',$v));
            }

        }
        echo '<br>';
    }
}


return;



$R = [];
$Gr = [];

foreach($DataSet as $n=>$i) {
    $c = $nn->Cluster($i,$Clusters);
    $R[$c][] = $n+1;
    $Gr[$c]['Пол'][] = $Data[$n][2];
    $Gr[$c]['Все сдано'][] = $Data[$n][3];
    !isset($Gr[$c]['Средний бал']) && $Gr[$c]['Средний бал'] = 0.0;
    !isset($Gr[$c]['Коэф. степендии']) && $Gr[$c]['Коэф. степендии'] = 0.0;
    $Gr[$c]['Средний бал'] += ($Data[$n][4] + $Data[$n][5] + $Data[$n][6] + $Data[$n][7] + $Data[$n][8]) / 5;
    $Gr[$c]['Коэф. степендии'] += $Data[$n][9];
}



printJson($R);