<?php
namespace Sci\Math\Normalization;
require_once 'base.php';

class Scale extends Base {

    public $Weights;

    public function __construct(\Sci\Math\Vector &$Vector,$Weights=[]) {
        parent::__construct($Vector);
        $this->Weights = $Weights;
    }

    protected function normalize_01($val,$n=null) {
        return isset($this->Weights[$n]) ? $this->Weights[$n] * \Sci\Div(1.0,$val) : \Sci\Div(1.0,$val);
    }
    protected function denormalize_01($val,$n=null) {
        return isset($this->Weights[$n]) ? $this->Weights[$n] * \Sci\Div(1.0,$val) : \Sci\Div(1.0,$val);
    }

    protected function normalize_11($val,$n=null) {
        return isset($this->Weights[$n]) ? $this->Weights[$n] * \Sci\Div(1.0,$val) : \Sci\Div(1.0,$val);
    }
    protected function denormalize_11($val,$n=null) {
        return isset($this->Weights[$n]) ? $this->Weights[$n] * \Sci\Div(1.0,$val) : \Sci\Div(1.0,$val);
    }
}