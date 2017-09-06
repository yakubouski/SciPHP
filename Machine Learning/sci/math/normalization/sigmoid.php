<?php
namespace Sci\Math\Normalization;
require_once 'base.php';

class Sigmoid extends Base {

    public $Min,$Max,$Center,$Alpha;

    public function __construct(\Sci\Math\Vector &$Vector,float $Alpha=1.0,$Limit = '01') {
        parent::__construct($Vector,$Limit);
        $this->Min = min($this->Values);
        $this->Max = max($this->Values);
        $this->Center = ($this->Max + $this->Min)/2.0;
        $this->Alpha = max(abs($Alpha),1.0);
    }

    protected function normalize_01($val,$n=null) {
        return 1.0 / (exp( -$this->Alpha * ($val - $this->Center) ) + 1);
    }
    protected function denormalize_01($val,$n=null) {
        return $this->Center - (1.0/$this->Alpha) * log(1.0/$val - 1.0);
    }

    protected function normalize_11($val,$n=null) {
        return (exp( $this->Alpha * ($val - $this->Center) ) - 1) / (exp( $this->Alpha * ($val - $this->Center) ) + 1);
    }
    protected function denormalize_11($val,$n=null) {
        return $this->Center - (1.0/$this->Alpha) * log((1.0 - $val) / (1.0 + $val));
    }
}