<?php
namespace Sci\Data;

abstract class DataSet  implements \Countable, \Iterator, \ArrayAccess {

    private $It = 0;
    private $ColumnsList = null;

    abstract public function Row($No);
    abstract public function RowCount();
    abstract public function RowExists($No);
    abstract public function Column($No);
    abstract public function ColumnExists($No);
    abstract public function ColumnCount();
    abstract public function Cell($Row,$Col,$Val=null);

    private function columnsList() {
        if(is_null($this->ColumnsList)) {
            $this->ColumnsList = new class ($this) implements \ArrayAccess {
                private $DataSet;
                public function __construct(DataSet &$DataSet) { $this->DataSet =& $DataSet; }
                #region ArrayAccess Members
                public function offsetExists($offset) { return $this->DataSet->ColumnExists($offset); }
                public function offsetGet($offset) { return $this->DataSet->Column($offset); }
                public function offsetSet($offset, $value) {}
                public function offsetUnset($offset) {}
                #endregion
            };
        }
        return $this->ColumnsList;
    }

    #region Iterator Members
    public function current() { return $this->Row($this->It); }
    public function key() { return $this->It; }
    public function next() { $this->It ++; }
    public function rewind() { $this->It = 0; }
    public function valid() { return $this->It<$this->RowCount(); }
    #endregion

    #region ArrayAccess Members
    public function offsetExists($offset) { return is_null($offset) || ($offset < $this->RowCount()); }
    public function offsetGet($offset) { return !is_null($offset) ? $this->Row($offset) : $this->columnsList(); }
    public function offsetSet($offset, $value) {}
    public function offsetUnset($offset) { }
    #endregion

    #region Countable Members
    public function count() { return $this->RowCount(); }
    #endregion
}

/**
 * Class1 short summary.
 *
 * Class1 description.
 *
 * @version 1.0
 * @author iroke
 */
abstract class DataCursorBase implements \ArrayAccess,\Iterator,\Countable
{
    abstract protected function _Exists($offset);
    abstract protected function _Get($offset);
    abstract protected function _Set($offset, $value);
    abstract protected function _Current();
    abstract protected function _Key();
    abstract protected function _Next();
    abstract protected function _Rewind();
    abstract protected function _Valid();
    abstract protected function _Count();
    abstract public function Index();

    #region ArrayAccess Members
    public function offsetExists($offset) { return $this->_Exists($offset); }
    public function offsetGet($offset) { return $this->_Get($offset); }
    public function offsetSet($offset, $value) { $this->_Set($offset,$value); }
    public function offsetUnset($offset) { }
    #endregion

    #region Iterator Members
    public function current() { return $this->_Current(); }
    public function key() { return $this->_Key(); }
    public function next() { $this->_Next(); }
    public function rewind() {$this->_Rewind(); }
    public function valid() { return $this->_Valid(); }
    #endregion

    #region Countable Members
    public function count() { return $this->_Count(); }
    #endregion
}

class DataCursorRow extends DataCursorBase {

    private $Row;
    private $DataSet;
    private $It;

    public function __construct($Row,DataSet &$Set) {
        $this->DataSet =& $Set;
        $this->Row = $Row;
        $this->It = 0;
    }

    protected function _Exists($offset) {return $this->DataSet->ColumnExists($offset);}
    protected function _Get($offset) { return @($this->DataSet->Cell($this->Row,$offset)); }
    protected function _Set($offset, $value) { $this->DataSet->Cell($this->Row,$offset,$value); }
    protected function _Current() { return @($this->DataSet->Cell($this->Row,$this->It)); }
    protected function _Key() { return $this->It; }
    protected function _Next() { ++ $this->It;}
    protected function _Rewind() { $this->It = 0;}
    protected function _Valid() { return $this->DataSet->ColumnExists($this->It); }
    protected function _Count() { return $this->DataSet->ColumnCount(); }

    public function Index() { return $this->Row; }
}

class DataCursorColumn extends DataCursorBase {

    private $Col;
    private $DataSet;
    private $It;

    public function __construct($Col,DataSet &$Set) {
        $this->DataSet =& $Set;
        $this->Col = $Col;
        $this->It = 0;
    }

    public function Index() { return $this->Col; }

    protected function _Exists($offset) { return $this->DataSet->RowExists($offset); }
    protected function _Get($offset) { return $this->DataSet->Cell($offset,$this->Col); }
    protected function _Set($offset, $value) { $this->DataSet->Cell($offset,$this->Col,$value); }
    protected function _Current() { return $this->DataSet->Cell($this->It,$this->Col); }
    protected function _Key() { return $this->It; }
    protected function _Next() { $this->It ++; }
    protected function _Rewind() { $this->It = 0; }
    protected function _Valid() { return $this->It < $this->DataSet->RowCount(); }
    protected function _Count() { return $this->DataSet->RowCount(); }
}