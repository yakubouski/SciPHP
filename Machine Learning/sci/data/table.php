<?php
namespace Sci\Data;


class TableRowCursor implements \ArrayAccess {
    private $RowNo;
    private $Table;
    public function __construct($No,&$Table) {
        $this->RowNo = $No;
        $this->Table =& $Table;
    }

    #region ArrayAccess Members
    public function offsetExists($offset) { return isset($this->Table->Columns()[$offset]); }
    public function offsetGet($offset) { return @($this->Table->Cell($this->RowNo,$offset)); }
    public function offsetSet($offset, $value) { $this->Table->Cell($this->RowNo,$offset,$value); }
    public function offsetUnset($offset) { }
    #endregion
}

class TableColumnCursor implements \ArrayAccess,\Iterator,\Countable {
    private $Column;
    private $Table;
    private $RowIt;
    public function __construct($Column,&$Table) {
        $this->Column = $Column;
        $this->Table =& $Table;
        $this->RowIt = 0;
    }

    #region ArrayAccess Members
    public function offsetExists($offset) { return $offset < $this->Table->count(); }
    public function offsetGet($offset) {
        return $this->Table->Cell($offset,$this->Column);
    }
    public function offsetSet($offset, $value) { $this->Table->Cell($offset,$this->Column,$value); }
    public function offsetUnset($offset) { }
    #endregion

    #region Iterator Members
    public function current() { return $this->Table->Cell($this->RowIt,$this->Column); }
    public function key() { return $this->RowIt; }
    public function next() { $this->RowIt ++; }
    public function rewind() { $this->RowIt = 0; }
    public function valid() { return $this->RowIt < $this->Table->count(); }
    #endregion

    #region Countable Members
    public function count() { return $this->Table->count(); }

    #endregion
}

/**
 * Table data manipulation
 *
 * @version 1.0
 * @author iroke
 */
class Table implements \Countable, \Iterator, \ArrayAccess
{
    private $ColumnIndex,$Columns,$Rows;
    private $RowIt;
    private $ColumnsList;

    public function __construct()
    {
        $this->ColumnIndex = 0;
        $this->Columns = [];
        $this->Rows = [];
        $this->RowIt = 0;
        $this->ColumnsList = new class implements \ArrayAccess {
            private $Table;
            public function __construct(Table &$Table) { $this->Table =& $Table; }
            #region ArrayAccess Members
            public function offsetExists($offset) { return isset($this->Table->Columns()[$offset]); }
            public function offsetGet($offset) { return $this->Table->Column($offset); }
            public function offsetSet($offset, $value) {}
            public function offsetUnset($offset) {}
            #endregion
        };
    }
    private function __recordset(&$Stmnt) {
        for($i=0;$i<$Stmnt->columnCount();$i++) {
            $this->Columns[$Stmnt->getColumnMeta($i)['name']] = $this->ColumnIndex ++;
        }
        for($r=0;$row = $Stmnt->fetch(\PDO::FETCH_ASSOC);$r++) {
            foreach($row as $a => $value) {
                $this->Rows[$r][$this->Columns[$a]] = $value;
            }
        }
        $Stmnt->closeCursor();
    }

    public function fromSql(\Sci\Db\Dsn $Db,$SqlQuery,...$Args) {
        $Stmnt = $Db->SqlExecute($SqlQuery,$Args);
        $this->__recordset($Stmnt);
        return $this;
    }

    public function Row($No) {
        if($No < \count($this->Rows)) {
            return new TableRowCursor($No,$this);
        }
        return null;
    }

    public function Column($Column) {
        return new TableColumnCursor($Column,$this);
    }

    public function Cell($Row,$Column,$Val=null) {
        return $this->Rows[$Row][$this->Columns[$Column]];
    }

    public function &Columns() {
        return $this->Columns;
    }

    #region Iterator Members
    public function current() { return $this->Row($this->RowIt); }
    public function key() { return $this->RowIt; }
    public function next() { $this->RowIt ++; }
    public function rewind() { $this->RowIt = 0; }
    public function valid() { return $this->RowIt<\count($this->Rows); }
    #endregion

    #region ArrayAccess Members
    public function offsetExists($offset) { return is_null($offset) || (is_int($offset) && $offset < \count($this->Rows)); }
    public function offsetGet($offset) { return !is_null($offset) ? $this->Row($offset) : $this->ColumnsList; }
    public function offsetSet($offset, $value) {}
    public function offsetUnset($offset) { }
    #endregion

    #region Countable Members
    public function count() { return \count($this->Rows); }
    #endregion
}
