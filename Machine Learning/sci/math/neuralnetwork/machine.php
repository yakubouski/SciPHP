<?php
namespace Sci\Math\NeuralNetwork;
use Sci\Math\Vector as Vector;

class Machine
{

    public $Weights,$Layers;
    public $NumInputs,$NumOutputs,$NumWeights;

    /**
     * Summary of __construct
     * @param int $NumInputs количество входов
     * @param int $NumOutputs количество выходов
     * @param array $HiddenLayers массив скрытых слоев с количеством нейронов в каждом слое
     * @param array $InitialWeights массив начальных весов
     */
    public function __construct($NumInputs,$NumOutputs,$HiddenLayers = [],$InitialWeights = [],$RandomRangeType = '01') {
        $this->NumInputs = $NumInputs;
        $this->NumOutputs = $NumOutputs;
        $this->NumWeights = 0;
        $this->NumNeurons = 0;
        $this->Weights = [];
        $this->Layers = [];
        $this->calcWeightMatrix($HiddenLayers,$InitialWeights,$RandomRangeType);
    }

    private function calcWeightMatrix($HiddenLayers,$InitialWeights,$RandomRangeType) {

        array_unshift($HiddenLayers,$this->NumInputs);
        array_push($HiddenLayers,$this->NumOutputs);

        list($Left,$Right) = $this->calcWeightsBounds($RandomRangeType);

        $this->NumNeurons = 1;
        for($l=0;$l<count($HiddenLayers)-1;$l++,$this->NumNeurons += $HiddenLayers[$l]) {
            for($j=0;$j<$HiddenLayers[$l+1];$j++) {
                for($i=0;$i<$HiddenLayers[$l] + 1;$i++,++$this->NumWeights) {
                    $this->Weights[$i.':'.($this->NumNeurons + $j)] = isset($InitialWeights[$this->NumWeights]) ? $InitialWeights[$this->NumWeights] : $this->rnd($Left,$Right);
                    $this->Layers[$l][$j][] = $i.':'.($this->NumNeurons + $j);
                }
            }
        }
        //($NumInputs + 1) * $HiddenLayers[0] + $Nh + ($HiddenLayers[$i-1] + 1) * $NumOutputs;
    }

    /**
     * Calculate range for first weights random initialization
     * @param int $nInputs
     * @param '01'|'-11' $BoundType
     * @return [min,max]
     */
    protected function calcWeightsBounds($BoundType = '01') {
        switch($BoundType) {
            case '-11': return [-1.0/\sqrt($this->NumInputs),1.0/\sqrt($this->NumInputs)];
            case '01': return [0.5-(1.0/\sqrt($this->NumInputs)),0.5+(1.0/\sqrt($this->NumInputs))];
        }
        return [0.0,0.0];
    }

    public function sequence($NumSamples,$shiffle=1) {
        $numbers = range(0, $NumSamples-1);
        $shiffle && shuffle($numbers);
        return $numbers;
    }

    public function rnd($min,$max,$multipler=10000) {
        return (random_int(round($min*$multipler),round($max*$multipler))/$multipler);
    }

    protected function S($y,$Saturation) {
        return $this->mathExpSigmoid($y,$Saturation);
    }

    protected function mathExpSigmoid($s,$Saturation) {
        return 1.0/(exp(-$Saturation*$s) + 1);
        return $this->mathDerivative(1.0/(exp(-$Saturation*$s) + 1));
    }
    protected function mathHypTanSigmoid($s,$Saturation) {
        return (exp($s/$Saturation) - exp(-($s/$Saturation)))/(exp($s/$Saturation) + exp(-($s/$Saturation)));
    }
    /**
     * Рациональный сигмоид
     */
    protected function mathRationalSigmoid($s,$Saturation) {
        return $s/(abs($s) + $Saturation);
    }
    /**
     * Производная
     */
    protected function mathDerivative($v,$a=1.0) {
        return $a * ( $v * (1 - $v) );
    }

    public function Training($Fn,$SamplesCount,$EpochCount,$ErrorThreshold=0.05,$Saturation=1.0,$Velocity=0.9) {
        $ErrorMse = 1.0;
        $it = 0;
        for($ep=0;($ep < $EpochCount) && ($ErrorMse >= $ErrorThreshold);$ep++) {
            foreach($this->sequence($SamplesCount,0) as $n) {
                list($I,$O) = $Fn($n,$ep,$it++,$this);
                $S = [0=>&$I];
                $Y = [];
                $D = [];

                foreach($this->Layers as $nl=>$layer) {
                    $X[$nl+1] = [];
                    foreach($layer as $neurons) {
                        $y = 0.0;
                        $nOut = 0;
                        foreach($neurons as $neuron) {
                            list($nInp,$nOut) = explode(':',$neuron,2);
                            $x = $nInp ? $S[$nl][$nInp-1] : 1.0;
                            $y += $x * $this->Weights[$neuron];
                        }
                        $S[$nl+1][$nOut-1] = $this->S($y,$Saturation);
                    }
                    $Y =& $S[$nl+1];
                }
                /*
                for($s=0;$s<$this->countOutputs;$s++) {
                    $this->_S($s,$S = $this->s($s,$XY));
                    $this->_F($s,$Fs = $this->ExpSigmoid($S,$Saturation));
                    $this->_D($s,$D = $this->D($s,$XY,$Fs));
                    $this->BackPropagationCorr($s,$XY,$D,$Velocity);
                }
                */
            }
            /*
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
            */
        }
    }
}