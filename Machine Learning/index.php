<?php
namespace Sci;

ini_set('display_errors','on');
ini_set('display_startup_errors','on');

require_once 'sci/sci.php';

Lib('db','html');

use Sci\Math\Matrix as Matrix;
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

    public function Training($Fn,$SamplesCount,$EpochCount,$ErrorThreshold,$Saturation=1.0,$Velocity=0.9,$InitialWeights=[],$RandomRangeType = '01') {
        /*
         * Start weights initialization
         */
        list($minW,$maxW) = $this->weightsRandRange($this->countInputs,$RandomRangeType);
        for($i=0;$i<$this->countWeights;$i++) {
            $this->Weights[$i] = isset($InitialWeights[$i]) ? $InitialWeights[$i] : $this->rnd($minW,$maxW);
        }
        $ErrorMse = 0.0;
        $it = 0;
        for($ep=0;($ep < $EpochCount) || ($ErrorMse >= $ErrorThreshold);$ep++) {

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

class MathModel {

    public $DataSet = [];
    public $Periods = [];

    public function AddSample(array $Visits,$view) {
        $first = reset($Visits);
        for($i=1;$i<count($Visits);$i++) {
            $period = $Visits[$i] - $first;
            $first = $Visits[$i];
            $this->DataSet[$view][$i] = $period;
            $this->Periods[$i][] = $view;
        }
    }

    public function GetSamples($NumVisit,&$Layer,&$Output=[]) {
        $Data = [];
        $Output = [];
        $Layer = [];
        if(isset($this->Periods[$NumVisit])) {
            foreach($this->Periods[$NumVisit] as $sample) {
                $Set = [];
                for($i=1;$i<=$NumVisit;$i++) {
                    $Set[] = $this->DataSet[$sample][$i];
                    !isset($Layer[$i][$this->DataSet[$sample][$i]]) && $Layer[$i][$this->DataSet[$sample][$i]] = 0;
                    $Layer[$i][$this->DataSet[$sample][$i]] += 1;
                }
                $Data[] = implode(',&nbsp;',$Set);
            }
        }
        return $Data;
    }

    public function TestCaseGetSamples($NumInput,$NumOutput,&$Input,&$Output) {
        $Input = [
            0=>[]
        ];
        static $TestSample = [
            [ -5,  2, -18,  -1, ],
            [  0,  5,  -9,  16, ],
            [  2, -4,  15, -18, ],
            [ -3,  1, -10,  -3, ],
            [  5,  0,  16,   1, ],
            [  1, -5,  14, -23, ],
            [ -3, -1,  -6, -11, ],
            [  2,  5,  -3,  18, ],
            [  4,  3,   7,  12, ],
            [  0, -2,   5, -12, ],
        ];
    }
}


\Html::Main(function(DB\Dsn $Db){
    ini_set('memory_limit', '2048M');

    $Data = new Matrix(4,0,0.0);
    $Normalized = new Matrix(4,0,0.0);
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

    for($i=0;$i<$Data->ColumnCount();$i++) {
        $col =& $Data[null][$i];
        $normValues = [];

        list($min,,$period) = MS::MinMaxRange($col);
        foreach($col as $v) {
            $normValues[] = Div( Float($v,4) - $min, $period,4);
        }
        $Normalized->SetColumn($i,$normValues);

    }

    $net = new NeuralNetworkLayer(2,2);
    $W = new Matrix(15,0,0.0);
    //\Html::Json([$net->i(0,0),$net->i(0,1),$net->i(1,0),$net->i(1,1),$net->i(2,0),$net->i(2,1)]);
    $net->Training(function($n,$e,$it,$net) use (&$W,&$Normalized) {
        $it && $W->AddRows($net->Weights);
        return $Normalized[$n];
    },10,10,0.01,1.0,0.9,[0.00,0.20,-0.40,-0.10,0.30,0.20]);
    $W->AddRows($net->Weights);


    \Html::Table('Dataset',['x{s|1}','x{|2}','y{|1}','y{|2}','~X{|1}','~X{|2}','~Y{|1}','~Y{|2}','w{|01}','w{|02}','w{|11}','w{|12}','w{|21}','w{|22}','s{|1}','s{|2}','F{s|1}','F{s|2}','&#8710;{|1}','&#8710;{|2}','E{~Y|1}','E{~Y|2}','E{|msa}'],$Data,$Normalized,$W);
    //\Html::Table('Network',['w{|01}','w{|01}','w{|11}','w{|12}','w{|21}','w{|22}'],$net);

    return;

    $model = new MathModel();
    $view = 0;

    $Db->SqlAssocRecordset('SELECT list_visits FROM data.data_set_1',function(&$r,&$rs) use (&$model,&$view){
        $model->AddSample(explode(',',$r['list_visits']),$view++);
    });

    printTable(['X<sub>0</sub>','X<sub>1</sub>','X<sub>2</sub>','Y<sub>1</sub>','Y<sub>1</sub>']);

    //print '<pre>'.json_encode($model->GetSamples(4,$Layer),JSON_PRETTY_PRINT).'</pre>';
    //print '<pre>'.json_encode($Layer,JSON_PRETTY_PRINT).' '.count($Layer[1]).' '.count($Layer[2]).' '.count($Layer[3]).'</pre>';


},Dsn('p:mysql','root','zsq@!wax','localhost','data'));
