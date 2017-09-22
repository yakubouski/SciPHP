<?php
namespace Sci\Math\NeuralNetwork;

class Kohonen
{
    public $Weights;
    public $NumInputs,$NumClusters,$NumWeights,$NumEpoch;

    /**
     * Summary of __construct
     * @param int $NumInputs количество входов
     * @param int $NumClusters количество выходов
     * @param array $HiddenLayers массив скрытых слоев с количеством нейронов в каждом слое
     * @param array $InitialWeights массив начальных весов
     */
    public function __construct($NumInputs,$NumClusters=0,$InitialWeights = []) {
        $this->NumInputs = $NumInputs;
        $this->NumClusters = $NumClusters;
        $this->NumWeights = $NumInputs * $NumClusters;
        $this->NumEpoch = 0;
        $this->Weights = [];
        $this->setWeights($InitialWeights);

    }

    public function setWeights($Weights) {
        for($i=0; $i< $this->NumWeights; $i++) {
            $this->Weights[$i] = $Weights[$i] ?? 0.0;
        }
    }

    private function w($x,$k,$v=null) {
        !is_null($v) && $this->Weights[($k * $this->NumInputs) + $x] = $v;
        return $this->Weights[($k * $this->NumInputs) + $x];
    }

    private function mathEuclidDistance(&$I,$K) {
        $R = 0.0;
        for($i=0; $i<$this->NumInputs; $i++) {
            $R += pow($I[$i] - $this->w($i,$K),2);
        }
        return sqrt($R);
    }

    private function updateWeights(&$I,$K,$Velocity) {
        for($i=0; $i<$this->NumInputs; $i++) {
            $w = $this->w($i,$K);
            $this->w($i,$K,$w + $Velocity * ($I[$i] - $w));
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
    public function Training($Fn,$SamplesCount,$Velocity=0.9,$VelocitySpeedDown=0.05,$EuclidDistanceThreshold=null) {

        $Clusters = array_fill(0,$this->NumClusters,0.0);

        /* Training until exceed epoch count or MSE less threshold */
        for($this->NumEpoch=0;$Velocity > 0.0; $Velocity -= $VelocitySpeedDown,$this->NumEpoch++) {
            foreach($this->sequence($SamplesCount,1) as $n) {
                $I = $Fn($n,$this->NumEpoch,$this);
                $Rmin = 0.0;
                $Nmin = null;

                /* Forward */
                for($k=0;$k<$this->NumClusters;$k++) {
                    $Clusters[$k] = $R = $this->mathEuclidDistance($I,$k);
                    /* Calc distance and find min */
                    if(is_null($Nmin) || $R < $Rmin) {
                        $Rmin = $R;
                        $Nmin = $k;
                    }
                }

                /* Weights correction */
                $this->updateWeights($I,$Nmin,$Velocity);
            }
        }
    }

    /**
     * Get current weights
     * @return array
     */
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


    /**
     * Summary of Compute
     * @param array $Input input signals
     * @param float $Saturation
     * @return array output signals
     */
    public function Cluster($Input,&$Clusters=[]) {
        $Clusters = [];
        $cIndex = null;
        $cMax = null;
        for($c=0;$c<$this->NumClusters;$c++) {
            $Clusters[$c] = 0.0;
            for($i=0;$i<$this->NumInputs;$i++) {
                $Clusters[$c] += pow($Input[$i] - $this->w($i,$c),2);
            }
            $Clusters[$c] = sqrt($Clusters[$c]);
            if(is_null($cIndex) || $Clusters[$c]>=$cMax) {
                $cMax = $Clusters[$c];
                $cIndex = $c;
            }
        }
        return $cIndex;
    }

    protected function sequence($NumSamples,$shiffle=1) {
        $numbers = range(0, $NumSamples-1);
        $shiffle && shuffle($numbers);
        return $numbers;
    }

    protected function rnd($min,$max,$multipler=10000) {
        return (random_int(round($min*$multipler),round($max*$multipler))/$multipler);
    }
}