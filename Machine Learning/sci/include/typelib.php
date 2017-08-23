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
     * @param mixed $decimals round
     * @return double
     */
    function Float($val,$decimals=null) {
        $val = is_float($val) ? $val : floatval(is_string($val) ? str_replace(',','.',$val) : $val );
        !is_null($decimals) && ($val = round($val,$decimals));
        return $val;
    }
    /**
     * Create table manipulation object
     * @return Data\Table
     */
    function Table() {
        return new Data\Table();
    }

    function dim() {}

    /**
     * Create PDO database connection
     * @return Db\Dsn
     */
    function Dsn($DbDriver,$DbUser,$DbPwd,$DbHost='localhost',$DbName=null,$DpOpt=[]) {
        return new Db\Dsn($DbDriver,$DbUser,$DbPwd,$DbHost,$DbName,$DpOpt);
    }
}