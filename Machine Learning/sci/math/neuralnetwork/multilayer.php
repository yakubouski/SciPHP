<?php
namespace Sci\Math\NeuralNetwork;

class MultilayerWeights {
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

/**
 * Multilayer neural network class
 */
class Multilayer
{
    public $Weights,$Neurons,$MSE,$NumEpoch;
    public $NumInputs,$NumOutputs,$NumWeights,$NumNeurons;

    /**
     * Summary of __construct
     * @param int $NumInputs количество входов
     * @param int $NumOutputs количество выходов
     * @param array $HiddenLayers массив скрытых слоев с количеством нейронов в каждом слое
     * @param array $InitialWeights массив начальных весов
     */
    public function __construct($NumInputs,$NumOutputs,$HiddenLayers = [],$InitialWeights = []) {
        $this->NumInputs = $NumInputs;
        $this->NumOutputs = $NumOutputs;
        $this->NumWeights = 0;
        $this->NumNeurons = 0;
        $this->NumEpoch = 0;
        $this->Neurons = [];
        $this->MSE = 0;
        $NeuronIt = 1;
        array_push($HiddenLayers,$NumOutputs);
        foreach($HiddenLayers as $l=>$numNeurons) {
            $this->Weights[] = new MultilayerWeights($numNeurons,$NumInputs,$InitialWeights,$this->NumWeights,$this->NumNeurons);
            for($i=1;$i<=$numNeurons;$i++) {
                $this->Neurons[$l][$i] = [$NumInputs,$NeuronIt ++,$l,'S'=>0.0,'Y'=>0.0,'E'=>0.0,'d'=>0.0];
            }
            $NumInputs = $numNeurons;
        }
    }

    public function RandomWeights($min,$max) {
        $multipler=100000;
        foreach($this->Weights as &$ns) {
            foreach($ns->Weights as &$ws) {
                foreach($ws as &$w) {
                    $w = (random_int(round($min*$multipler),round($max*$multipler))/$multipler);
                }
            }
        }
    }

    /**
     * Summary of Training
     * @param callable $Fn
     * @param int $SamplesCount number training  samples
     * @param int $EpochCount number of epoch
     * @param float $ErrorThreshold MSE error threshold
     * @param float $Saturation saturation paramter for sigmoid function
     * @param float $Velocity learning speed
     */
    public function Training($Fn,$SamplesCount,$EpochCount,$ErrorThreshold=0.05,$Saturation=1.0,$Velocity=0.9) {
        $this->MSE = 1.0;
        /* Training until exceed epoch count or MSE less threshold */
        for($this->NumEpoch=0;($this->NumEpoch < $EpochCount) && ($this->MSE >= $ErrorThreshold);$this->NumEpoch++) {
            $EpochSamples = [];
            /* shiffle current epoch samples index */
            foreach($this->sequence($SamplesCount,1) as $n) {
                list($I,$O) = $Fn($n,$this->NumEpoch,$this);
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
            }

            /* Calclulate current epoch MSE */
            $this->MSE = 0.0;
            foreach($EpochSamples as list($I,$O)) {
                $o = 0;
                $Error = 0.0;
                $Y = $this->Compute($I,$Saturation);
                foreach($Y as $val) {
                    $Error += pow($val - $O[$o++],2);
                }
                $this->MSE += $Error / count($EpochSamples);
            }
            $this->MSE = sqrt($this->MSE);
        }
    }

    /**
     * Get current weights
     * @return array
     */
    public function GetWeights() {
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

    /**
     * Set weights
     * @return array
     */
    public function SetWeights(array $Weights) {
        reset($Weights);
        foreach($this->Weights as &$ns) {
            foreach($ns->Weights as &$ws) {
                foreach($ws as &$w) {
                    $w = current($Weights);
                    next($Weights);
                }
            }
        }
    }

    /**
     * Get current MSE
     * @return float
     */
    public function getMSE() {
        return $this->MSE;
    }

    /**
     * Summary of Compute
     * @param array $Input input signals
     * @param float $Saturation
     * @return array output signals
     */
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
    }

    protected function mathDerivative($F) {
        return $F * (1.0 - $F);
    }

    protected function mathExpSigmoid($s,$Saturation) {
        return 1.0/(exp(-$Saturation*$s) + 1);
    }

    protected function sequence($NumSamples,$shiffle=1) {
        $numbers = range(0, $NumSamples-1);
        $shiffle && shuffle($numbers);
        return $numbers;
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

    private function E(&$Neuron,&$I,&$O,$Saturation) {
        foreach($Neuron as $i=>&$n) {
            list(,,$nLayer) = $n;
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
}