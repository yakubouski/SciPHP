<?php
namespace Sci;

ini_set('display_errors','on');
ini_set('display_startup_errors','on');

require_once 'sci/sci.php';

Lib('db','html');

use Sci\Math\Matrix as Matrix;
use Sci\Math\Distance as Distance;
use Sci\Math\Statistics as MS;




class NeuralNetworkLayer {

    public $Weights = [];
    public $countInputs,$countOutputs,$countWeights;

    public function __construct($nInputs,$nOutputs) {
        $this->countInputs = $nInputs;
        $this->countOutputs = $nOutputs;
        $this->countWeights = $this->weightsCount($nInputs,$nOutputs);
        $this->reset();
    }

    private function reset() {
        $this->Weights = array_fill(0,$this->countWeights + $this->countOutputs + $this->countOutputs + $this->countOutputs + $this->countOutputs + 1,0.0);
    }

    private function _w($in,$out,$v=null) {
        !is_null($v) && ($this->Weights[$in * $this->countInputs + $out] = $v);
        return $this->Weights[$in * $this->countInputs + $out];
    }
    private function _S($out,$v=null) {
        !is_null($v) && ($this->Weights[$this->countWeights + $out] = $v);
        return $this->Weights[$this->countWeights + $out];
    }
    private function _F($out,$v=null) {
        !is_null($v) && ($this->Weights[$this->countWeights + $this->countOutputs + $out] = $v);
        return $this->Weights[$this->countWeights + $this->countOutputs + $out];
    }
    private function _D($out,$v=null) {
        !is_null($v) && ($this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $out] = $v);
        return $this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $out];
    }
    private function _E($out,$v=null) {
        !is_null($v) && ($this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $this->countOutputs + $out] = $v);
        return $this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $this->countOutputs + $out];
    }
    private function _Emse($v=null) {
        !is_null($v) && ($this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $this->countOutputs + $this->countOutputs] = $v);
        return $this->Weights[$this->countWeights + $this->countOutputs + $this->countOutputs + $this->countOutputs + $this->countOutputs];
    }


    /**
     * Calculate number of weights
     * @param int $nInputs
     * @param int $nOutputs
     * @return integer
     */
    public function weightsCount($nInputs,$nOutputs) {
        return ($nInputs + 1) * $nOutputs;
    }
    /**
     * Calculate range for first weights random initialization
     * @param int $nInputs
     * @param '01'|'-11' $RangeType
     * @return [min,max]
     */
    public function weightsRandRange($nInputs,$RangeType = '01') {
        switch($RangeType) {
            case '-11': return [-1.0/\sqrt($nInputs),1.0/\sqrt($nInputs)];
            default:
                return [0.5-(1.0/\sqrt($nInputs)),0.5+(1.0/\sqrt($nInputs))];
        }
    }

    private function s($nOut,&$XY) {
        $Sum = $this->_w(0,$nOut);
        for($i=1;$i<=$this->countInputs;$i++) {
            $Sum += $this->_w($i,$nOut) * $XY[$i-1];
        }
        return $Sum;
    }
    private function ExpSigmoid($s,$Saturation) {
        return 1.0/(exp(-$Saturation*$s) + 1);
    }
    private function HypTanSigmoid($s,$Saturation) {
        return (exp($s/$Saturation) - exp(-($s/$Saturation)))/(exp($s/$Saturation) + exp(-($s/$Saturation)));
    }
    private function RationalSigmoid($s,$Saturation) {
        return $s/(abs($s) + $Saturation);
    }
    /**
     * Производная
     */
    private function Derivative($v) {
        return ( $v * (1 - $v) );
    }

    private function D($nOut,&$XY,$Fs) {
        return $XY[$this->countInputs + $nOut] - $Fs;
    }
    private function BackPropagationCorr($s,&$XY,$Delta,$Velocity) {
        for($i=0;$i<=$this->countInputs;$i++) {
            $x = ($i ? $XY[$i-1] : 1.0);
            $w = $this->_w($i,$s);
            $_w = $w + $Delta * $Velocity * $x;
            $this->_w($i,$s,$_w);
        }
    }

    public function Guess($X,$Saturation,$Y) {
        $Output = array_fill(0,$this->countInputs,0.0);
        for($s=0;$s<$this->countOutputs;$s++) {
            $Output[$s] = $Y[$s][0] + $Y[$s][2]*$this->ExpSigmoid($this->s($s,$X),$Saturation);
        }
        return $Output;
    }

    public function Training($Fn,$SamplesCount,$EpochCount,$ErrorThreshold,$Saturation=1.0,$Velocity=0.9,$InitialWeights=[],$RandomRangeType = '01') {
        /*
         * Start weights initialization
         */
        list($minW,$maxW) = $this->weightsRandRange($this->countInputs,$RandomRangeType);
        for($i=0;$i<$this->countWeights;$i++) {
            $this->Weights[$i] = isset($InitialWeights[$i]) ? $InitialWeights[$i] : $this->rnd($minW,$maxW);
        }
        $ErrorMse = 1.0;
        $it = 0;
        for($ep=0;($ep < $EpochCount) && ($ErrorMse >= $ErrorThreshold);$ep++) {

            /*
             * create data sequence
             */
            $SeqXY = [];
            foreach(($seq = $this->sequence($SamplesCount,0)) as $n) {
                $SeqXY[$n] = $XY = $Fn($n,$ep,$it++,$this);
                for($s=0;$s<$this->countOutputs;$s++) {
                    $this->_S($s,$S = $this->s($s,$XY));
                    $this->_F($s,$Fs = $this->ExpSigmoid($S,$Saturation));
                    $this->_D($s,$D = $this->D($s,$XY,$Fs));
                    $this->BackPropagationCorr($s,$XY,$D,$Velocity);
                }
            }
            $EpochError = array_fill(0,$this->countOutputs,0.0);
            foreach($seq as $n) {
                $XY =& $SeqXY[$n];
                for($s=0;$s<$this->countOutputs;$s++) {
                    $EpochError[$s] += pow($this->ExpSigmoid($this->s($s,$XY),$Saturation) - $XY[$this->countInputs + $s],2);
                }
            }
            $ErrorMse = 0.0;
            for($s=0;$s<$this->countOutputs;$s++) {
                $ErrorMse += $this->_E($s,sqrt($EpochError[$s]/$SamplesCount));
            }
            $this->_Emse($ErrorMse/$s);
        }
    }

    private function sequence($NumSamples,$shiffle=1) {
        $numbers = range(0, $NumSamples-1);
        $shiffle && shuffle($numbers);
        return $numbers;
    }

    protected function rnd($min,$max,$multipler=10000) {
        return (random_int(round($min*$multipler),round($max*$multipler))/$multipler);
    }
}

function Run($Data) {

    $Normalized = new Matrix(4,0,0.0);

    for($i=0;$i<$Data->ColumnCount();$i++) {
        $col =& $Data[null][$i];
        $normValues = [];

        list($min,,$period) = MS::MinMaxRange($col);
        foreach($col as $v) {
            $normValues[] = $period ? Div( Float($v,4) - $min, $period,4) : 1.0;
        }
        $Normalized->SetColumn($i,$normValues);

    }

    $net = new NeuralNetworkLayer(2,2);
    $W = new Matrix(15,0,0.0);

    $net->Training(function($n,$e,$it,$net) use (&$W,&$Normalized) {
        ($it && ($it % 10)==0) && $W->AddRows($net->Weights);
        return $Normalized[$n];
    },count($Data),40,0.01,1.0,0.9,[0.00,0.20,-0.40,-0.10,0.30,0.20]);

    $W->AddRows($net->Weights);

    $Guess = new Matrix(2,0,0.0);
    $Y = [MS::MinMaxRange($Data[null][2]),MS::MinMaxRange($Data[null][3])];
    foreach($Normalized as $d) {
        $Guess->AddRows($net->Guess($d,1.0,$Y));
    }

    \Html::Table('Dataset',['x{s|1}','x{|2}','y{|1}','y{|2}','~X{|1}','~X{|2}','~Y{|1}','~Y{|2}','Y\'','Y\'','w{|01}','w{|02}','w{|11}','w{|12}','w{|21}','w{|22}','s{|1}','s{|2}','F{s|1}','F{s|2}','&#8710;{|1}','&#8710;{|2}','E{~Y|1}','E{~Y|2}','E{|msa}'],$Data,$Normalized,$Guess,$W);
}

function getSamples($Db,$NumVisit,$Distinct=0) {
    $view = 0;
    $Periods = [];
    $DataSet = [];
    $Data = [];

    $Db->SqlAssocRecordset('SELECT list_visits FROM data.data_set_1',function(&$r,&$rs) use (&$DataSet,&$Periods,&$view){
        $Visits = explode(',',$r['list_visits']);
        $first = reset($Visits);
        for($i=1;$i<count($Visits);$i++) {
            $period = $Visits[$i] - $first;
            $first = $Visits[$i];
            $DataSet[$view][$i] = $period;
            $Periods[$i][] = $view;
        }
        $view ++;
    });

    if(isset($Periods[$NumVisit])) {
        foreach($Periods[$NumVisit] as $sample) {
            $Set = [];
            for($i=1;$i<=$NumVisit;$i++) {
                $Set[] = $DataSet[$sample][$i];
                !isset($Layer[$i][$DataSet[$sample][$i]]) && $Layer[$i][$DataSet[$sample][$i]] = 0;
            }
            $Data[] = implode(',',$Set);
        }
    }
    $Distinct && ($Data = array_unique($Data));
    sort($Data,SORT_NATURAL);
    return array_map(function($d){return explode(',',$d);},array_values($Data));
}

function Run1($Samples,$Limit=null) {

    is_null($Limit) && ($Limit = count($Samples));
    /*
    $Sim = new Matrix($Limit,$Limit,0.0);

    for($c=0;$c<$Limit;$c++) {
        for($r=0;$r<$Limit;$r++) {
            $Sim->Cell($r,$c,Distance::Cosine($Samples[$c],$Samples[$r]));
        }
    }
    */
    //$clust = new \Sci\Math\Clusterization\KMeans($Sim->Matrix);

    $clust = new \Sci\Math\Clusterization\KMeans(array_slice($Samples,0,$Limit));

    $clust->cluster(9);

    $Clusters = array_filter($clust->getClusteredIndexes());

    $Args = ['Cosine distance',range(1,4*count($Clusters))];
    foreach($Clusters as $n => $idx) {
        $Args[2+$n] = new Matrix(4,0,0.0);
        foreach($idx as $i) {
            $Args[2+$n] ->AddRows($Samples[$i]);
        }
    }

    call_user_func_array('\Html::Table',$Args);
}


use Sci\Math\Vector as Vector;

\Html::Main(function(DB\Dsn $Db){
    ini_set('memory_limit', '2048M');

    $v = new Vector(4,[ -5,  2, -18,  -1, ]);
    $nv = $v->LinearNormalization();
    $sv = $v->SigmoidNormalizetion();

    printVector('Vector',['X{|1}','X{|2}','X{|3}','X{|4}','~X{lin|1}','~X{lin|2}','~X{lin|3}','~X{lin|4}','~X{sig|1}','~X{sig|2}','~X{sig|3}','~X{sig|4}',
        'X{~lin|1}','X{~lin|2}','X{~lin|3}','X{~lin|4}','X{~sig|1}','X{~sig|2}','X{~sig|3}','X{~sig|4}'],
        $v,$nv,$sv,
        new Vector(4,[$nv->Denormalize($nv[0]),$nv->Denormalize($nv[1]),$nv->Denormalize($nv[2]),$nv->Denormalize($nv[3])]),
        new Vector(4,[$sv->Denormalize($sv[0]),$sv->Denormalize($sv[1]),$sv->Denormalize($sv[2]),$sv->Denormalize($sv[3])]));

    return;

    $Data = new Matrix(4,0,0.0);
    /*
    $Data->AddRows(
            [ -5,  2, -18,  -1, ],
            [  0,  5,  -9,  16, ],
            [  2, -4,  15, -18, ],
            [ -3,  1, -10,  -3, ],
            [  5,  0,  16,   1, ],
            [  1, -5,  14, -23, ],
            [ -3, -1,  -6, -11, ],
            [  2,  5,  -3,  18, ],
            [  4,  3,   7,  12, ],
            [  0, -2,   5, -12, ]);
*/
    //

    $Samples = getSamples($Db,4,1);


    foreach($Samples as $n=>$d) {
        $Data->AddRows($d);
        if($n ==20) break;
    }

    Run($Data);


    //Run1($Samples,500);

    //$nn = new \Sci\Math\NeurialNetwork\Machine();

    var_export($nn);

    return;



    //print '<pre>'.json_encode($model->GetSamples(4,$Layer),JSON_PRETTY_PRINT).'</pre>';
    //print '<pre>'.json_encode($Layer,JSON_PRETTY_PRINT).' '.count($Layer[1]).' '.count($Layer[2]).' '.count($Layer[3]).'</pre>';


},Dsn('p:mysql','root','zsq@!wax','localhost','data'));
