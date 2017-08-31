<?php
namespace Sci {
    /**
     * Convert $val to integer
     * @param mixed $val
     * @return integer
     */
    function Int($val) {
        return is_int($val) ? intval($val) : intval($val);
    }
    /**
     * Convert $val to float
     * @param mixed $val
     * @param integer $decimals round
     * @return double
     */
    function Float($val,$decimals=null) {
        $val = is_float($val) ? $val : floatval(is_string($val) ? str_replace(',','.',$val) : $val );
        !is_null($decimals) && ($val = round($val,$decimals));
        return $val;
    }
    /**
     * Safe divide
     * @param \double|integer $val
     * @param \double|integer $divider
     * @param integer $decimals
     * @return \double|integer
     */
    function Div($val,$divider,$decimals=null) {
        return !empty($divider) && !empty($val) ? (is_null($decimals) ? ($val/$divider) : round($val/$divider,$decimals) ) :
            (is_null($decimals) ? 0.0 : round(0.0,$decimals) );
    }
}