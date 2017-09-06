<?php
namespace Sci\Math\Normalization;

abstract class Base extends \Sci\Math\Vector {
    public function __construct(\Sci\Math\Vector &$Vector,$Limit = '01') {
        $this->Default = $Vector->Default;
        $this->Dimension = $Vector->Dimension;
        $this->Rows = $Vector->Rows;
        $this->Values =& $Vector->Values;
        $this->__normalize = $Limit == '-11' ? [$this,'normalize_11'] : [$this,'normalize_01'];
        $this->__denormalize = $Limit == '-11' ? [$this,'denormalize_11'] : [$this,'denormalize_01'];
    }
    public function Value($i,$j=0,$val=null) {
        return $this->Normalize(parent::Value($i,$j,$val),$j * $this->Dimension + $i);
    }
    public function Denormalize($val) {
        return call_user_func($this->__denormalize,$val);
    }
    public function Normalize($val,$n=null) {
        return call_user_func($this->__normalize,$val,$n);
    }

    abstract protected function normalize_01($val,$n);
    abstract protected function denormalize_01($val,$n);

    abstract protected function normalize_11($val,$n);
    abstract protected function denormalize_11($val,$n);
}