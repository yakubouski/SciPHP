<?php
namespace Sci\Math\Normalization;
require_once 'base.php';

class Linear extends Base {

    public $Min,$Max,$Range;

    public function __construct(\Sci\Math\Vector &$Vector,$Limit = '01') {
        parent::__construct($Vector,$Limit);
        $this->Min = min($this->Values);
        $this->Max = max($this->Values);
        $this->Range = $this->Max - $this->Min;
    }

    protected function normalize_01($val,$n=null) {
        return \Sci\Div($val - $this->Min,$this->Range);
    }
    protected function denormalize_01($val,$n=null) {
        return $this->Min + $val * $this->Range;
    }

    protected function normalize_11($val,$n=null) {
        return 2.0 * \Sci\Div($val - $this->Min,$this->Range) - 1;
    }
    protected function denormalize_11($val,$n=null) {
        return $this->Min + (($val + 1) * $this->Range) / 2.0;
    }
}