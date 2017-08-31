<?php
namespace Sci\Math;
require_once __DIR__.'/../data/cursor.php';

class MatrixRow extends \Sci\Data\DataCursorRow {

}

class MatrixColumn extends \Sci\Data\DataCursorColumn {

}

class Matrix extends \Sci\Data\DataSet
{
    public $Matrix;
    public $NumCols;
    public $Default;
    public $NumRows;
    public function __construct($nCols,$nRows=0,$Default=0) {
        $this->Default = $Default;
        $this->Matrix = [];
        $this->NumCols = $nCols;
        $this->NumRows = $nRows;
    }

    public function Row($No) {
        if($No >=0 && $No < $this->NumRows) {
            return new MatrixRow($No,$this);
        }
        return null;
    }

    public function RowExists($No) {
        return $No >= 0 && $No < $this->NumRows;
    }

    public function RowCount() {
        return $this->NumRows;
    }

    public function Column($No) {
        return new MatrixColumn($No,$this);
    }

    public function ColumnExists($No) {
        return $No >=0 && $No < $this->NumCols;
    }

    public function ColumnCount() {
        return $this->NumCols;
    }

    public function Cell($Row,$Col,$Val=null) {
        if(!is_null($Val)) {
            !isset($this->Matrix[$Row][$Col]) && $this->Matrix[$Row][$Col] = $this->Default;
            $this->Matrix[$Row][$Col] = $Val;
            $this->NumRows = max($Row,$this->NumRows,count($this->Matrix));
        }
        return isset($this->Matrix[$Row][$Col]) ? $this->Matrix[$Row][$Col] : $this->Default;
    }

    public function AddRow(...$ColumnValues) {
        $this->Matrix[] = (count($ColumnValues)===1 && is_array($ColumnValues[0])) ? $ColumnValues[0] : $ColumnValues;
        $this->NumRows = count($this->Matrix);
        return $this;
    }
    public function AddRows(...$ColumnValues) {
        foreach($ColumnValues as $r) {
            $this->Matrix[] = $r;
        }
        $this->NumRows = count($this->Matrix);
        return $this;
    }

    public function SetRow($Row,$ColumnValues) {
        if($this->RowExists($Row)) {
            $this->Matrix[$Row] = $ColumnValues;
        }
        else {
            $this->Matrix[] = $ColumnValues;
            $this->NumRows = count($this->Matrix);
        }
        return $this;
    }
    public function AddColumn($RowValues=[]) {
        $this->NumCols += 1;
        !empty($RowValues) && $this->SetColumn($this->NumCols - 1,$RowValues);
        return $this;
    }
    public function SetColumn($Column,$RowValues) {
        ($Column >= $this->NumCols) && ($this->NumCols = $Column + 1);
        foreach($RowValues as $nRow=>$cVal) {
            ($nRow >= $this->NumRows) && ($this->NumRows = $nRow + 1);
            $this->Cell($nRow,$Column,$cVal);
        }
        return $this;
    }
}