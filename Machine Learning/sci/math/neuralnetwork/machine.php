<?php
namespace Sci\Math\NeuralNetwork;
use Sci\Math\Vector as Vector;

class Weights {

    public $Weights;
    public $Inputs;

    public function __construct($numNeurons,$numInput,&$WeightsInit,&$nWeight,&$nNeuron) {
        $this->Weights = [];
        $this->Inputs = $numInput;

        for($n=1;$n<=$numNeurons;$n++) {
            $this->Weights[0][++$nNeuron] = $WeightsInit[$nWeight++]??0.0;
            for($i=1;$i<=$numInput;$i++) {
                $this->Weights[$i][$nNeuron] = $WeightsInit[$nWeight++]??0.0;
            }
        }
    }

}

class Machine
{

    public $Weights,$Neurons,$MSE;
    public $NumInputs,$NumOutputs,$NumWeights,$NumNeurons;

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
        $this->Neurons = [];
        $this->MSE = 0;
        $NeuronIt = 1;
        array_push($HiddenLayers,$NumOutputs);
        foreach($HiddenLayers as $l=>$numNeurons) {
            $this->Weights[] = new Weights($numNeurons,$NumInputs,$InitialWeights,$this->NumWeights,$this->NumNeurons);
            for($i=1;$i<=$numNeurons;$i++) {
                $this->Neurons[$l][$i] = [$NumInputs,$NeuronIt ++,$l,'S'=>0.0,'Y'=>0.0,'E'=>0.0,'d'=>0.0];
            }
            $NumInputs = $numNeurons;
        }
    }

    private function Y(&$Neuron,&$I,$Saturation) {
        foreach($Neuron as &$n) {

            $n['S'] = 0.0;
            $n['Y'] = 0.0;
            $n['E'] = 0.0;
            $n['d'] = 0.0;

            list($NumInputs,$nNeuron,$nLayer) = $n;

            $n['S'] = 1.0 * $this->Weights[$nLayer]->Weights[0][$nNeuron];

            for($i=1;$i<=$NumInputs;$i++) {
                $n['S'] += ($nLayer ? $this->Neurons[$nLayer-1][$i]['Y'] : $I[$i-1]) * $this->Weights[$nLayer]->Weights[$i][$nNeuron];
            }

            $n['Y'] = $this->mathExpSigmoid($n['S'],$Saturation);
        }
    }

    private function mathDerivative($F) {
        return $F * (1.0 - $F);
    }

    private function E(&$Neuron,&$I,&$O,$Saturation) {
        foreach($Neuron as $i=>&$n) {
            list(,$nNeuron,$nLayer) = $n;
            if(isset($this->Neurons[$nLayer+1])) {
                foreach($this->Neurons[$nLayer+1] as $nNext) {
                    $n['d'] += $nNext['d'] * $this->Weights[$nLayer+1]->Weights[$i][$nNext[1]];
                }
                $n['d'] *= $Saturation * $this->mathDerivative($n['Y']);
            }
            else {
                $n['E'] = $O[$i-1] - $n['Y'];
                $n['d'] = $Saturation * $this->mathDerivative($n['Y']) * $n['E'];
            }
        }
    }

    private function W(&$Neuron,&$I,$Velocity) {
        foreach($Neuron as &$n) {
            list($NumInputs,$nNeuron,$nLayer) = $n;

            $this->Weights[$nLayer]->Weights[0][$nNeuron] =
                $this->Weights[$nLayer]->Weights[0][$nNeuron] + $Velocity * $n['d'] ;

            for($i=1;$i<=$NumInputs;$i++) {
                $this->Weights[$nLayer]->Weights[$i][$nNeuron] =
                    $this->Weights[$nLayer]->Weights[$i][$nNeuron] + (($nLayer ? $this->Neurons[$nLayer-1][$i]['Y'] : $I[$i-1] ) * $Velocity * $n['d']);
            }
        }
    }

    public function getWeights() {
        $Weights = [];
        foreach($this->Weights as $l=>$ns) {
            foreach($this->Neurons[$l] as $ne) {
                foreach($ns->Weights as $n=>$ws) {
                    $Weights["{$n}:{$ne[1]}"] = $ws[$ne[1]];
                }
            }
        }

        return $Weights;
    }

    public function Compute($Input,$Saturation) {
        $Neurons = [];
        foreach($this->Neurons as $laNs) {
            foreach($laNs as $ni=>$Ne) {
                list($NumInputs,$nNeuron,$nLayer) = $Ne;

                $Charge = 0.0;
                $Charge = 1.0 * $this->Weights[$nLayer]->Weights[0][$nNeuron];

                for($i=1;$i<=$NumInputs;$i++) {
                    $Charge += ($nLayer ? $Neurons[$nLayer-1][$i] : $Input[$i-1]) * $this->Weights[$nLayer]->Weights[$i][$nNeuron];
                }

                $Neurons[$nLayer][$ni] = $this->mathExpSigmoid($Charge,$Saturation);
            }
        }
        return end($Neurons);

        /*
        $Output = array_fill(0,$this->countInputs,0.0);
        for($s=0;$s<$this->countOutputs;$s++) {
            $Output[$s] = $Y[$s][0] + $Y[$s][2]*$this->ExpSigmoid($this->s($s,$X),$Saturation);
        }
        return $Output;*/
    }

    public function Training($Fn,$SamplesCount,$EpochCount,$ErrorThreshold=0.05,$Saturation=1.0,$Velocity=0.9,$PrintFn=null) {
        $this->MSE = 1.0;
        for($ep=0;($ep < $EpochCount) && ($this->MSE >= $ErrorThreshold);$ep++) {
            $EpochSamples = [];
            foreach($this->sequence($SamplesCount,0) as $n) {
                list($I,$O) = $Fn($n,$ep,$this);
                $EpochSamples[] = [$I,$O];

                /* Forward */
                for($l=0;$l<count($this->Neurons);$l++) {
                    $this->Y($this->Neurons[$l],$I,$Saturation);
                }

                /* Back propagation */
                for($l=count($this->Neurons)-1;$l>=0;$l--) {
                    $this->E($this->Neurons[$l],$I,$O,$Saturation);
                }

                /* Weights correction */
                for($l=0;$l<count($this->Neurons);$l++) {
                    $this->W($this->Neurons[$l],$I,$Velocity);
                }

                !is_null($PrintFn) && $PrintFn($n,$ep,$this);
            }
            /*
            $this->MSE = 0.0;
            //$EpochError = array_fill(0,count($Y),0.0);
            foreach($EpochSamples as list($I,$O)) {
                $s = 0;
                $Y = $this->Compute($I,$Saturation);
                echo '';
                //foreach($Y as $n) {
                //    $EpochError[$s] += pow($this->ExpSigmoid($this->s($s,$XY),$Saturation) - $XY[$this->countInputs + $s],2);
                //}
            }*/
            //$this->_Emse($ErrorMse/$s);
        }
    }

    protected function mathExpSigmoid($s,$Saturation) {
        return 1.0/(exp(-$Saturation*$s) + 1);
    }

    public function sequence($NumSamples,$shiffle=1) {
        $numbers = range(0, $NumSamples-1);
        $shiffle && shuffle($numbers);
        return $numbers;
    }

    public function rnd($min,$max,$multipler=10000) {
        return (random_int(round($min*$multipler),round($max*$multipler))/$multipler);
    }
}

class Machine1
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
     * –ациональный сигмоид
     */
    protected function mathRationalSigmoid($s,$Saturation) {
        return $s/(abs($s) + $Saturation);
    }
    /**
     * ѕроизводна€
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
                $DS = [];

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

                for($o=0;$o<$this->NumOutputs;$o++) {
                    $y = current($Y);
                    $DS[$o] = $O[$o] - $Y[$o];
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