<?php
namespace Sci\Data;

class TableCursor implements \ArrayAccess,\Iterator,\Countable {
    private $Row;
    private $Col;
    private $Table;
    private $It;
    private $Fn;

    private function rowExists($offset) {return isset($this->Table->Columns()[$offset]);}
    private function rowGet($offset) { return @($this->Table->Cell($this->Row,$offset)); }
    private function rowSet($offset, $value) { $this->Table->Cell($this->Row,$offset,$value); }
    private function rowCurrent() { return null; }
    private function rowKey() { return null; }
    private function rowNext() { }
    private function rowRewind() { }
    private function rowValid() { return false; }
    private function rowCount() { return 0; }

    private function colExists($offset) { return $offset < $this->Table->count(); }
    private function colGet($offset) { return $this->Table->Cell($offset,$this->Col); }
    private function colSet($offset, $value) { $this->Table->Cell($offset,$this->Col,$value); }
    private function colCurrent() { return $this->Table->Cell($this->It,$this->Col); }
    private function colKey() { return $this->It; }
    private function colNext() { $this->It ++; }
    private function colRewind() { $this->It = 0; }
    private function colValid() { return $this->It < $this->Table->count(); }
    private function colCount() { return $this->Table->count(); }

    public function __construct($Row,$Col,&$Table) {
        $this->Row = $Row;
        $this->Col = $Col;
        $this->Table =& $Table;
        $this->It = 0;
        if(!is_null($Row)) {
            $this->Fn = [
                'offsetExists'=>[$this,'rowExists'],
                'offsetGet'=>[$this,'rowGet'],
                'offsetSet'=>[$this,'rowSet'],
                'current'=>[$this,'rowCurrent'],
                'key'=>[$this,'rowKey'],
                'next'=>[$this,'rowNext'],
                'rewind'=>[$this,'rowRewind'],
                'valid'=>[$this,'rowValid'],
                'count'=>[$this,'rowCount'],
            ];
        }
        else {
            $this->Fn = [
                'offsetExists'=>[$this,'colExists'],
                'offsetGet'=>[$this,'colGet'],
                'offsetSet'=>[$this,'colSet'],
                'current'=>[$this,'colCurrent'],
                'key'=>[$this,'colKey'],
                'next'=>[$this,'colNext'],
                'rewind'=>[$this,'colRewind'],
                'valid'=>[$this,'colValid'],
                'count'=>[$this,'colCount'],
            ];
        }
    }

    #region ArrayAccess Members
    public function offsetExists($offset) { return call_user_func($this->Fn['offsetExists'],$offset); }
    public function offsetGet($offset) { return call_user_func($this->Fn['offsetGet'],$offset); }
    public function offsetSet($offset, $value) { call_user_func($this->Fn['offsetSet'],$offset,$value); }
    public function offsetUnset($offset) { }
    #endregion

    #region Iterator Members
    public function current() { return call_user_func($this->Fn['current']); }
    public function key() { return call_user_func($this->Fn['key']); }
    public function next() { call_user_func($this->Fn['next']); }
    public function rewind() {call_user_func($this->Fn['rewind']); }
    public function valid() { return call_user_func($this->Fn['valid']); }
    #endregion

    #region Countable Members
    public function count() { return call_user_func($this->Fn['count']); }
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
        $this->ColumnsList = new class ($this) implements \ArrayAccess {
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
    /**
     * @ignore
     */
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
    /**
     * Import data from Sql database
     * @param \Sci\Db\Dsn $Db
     * @param string $SqlQuery
     * @param array $Args
     * @return Table
     */
    public function fromSql(\Sci\Db\Dsn $Db,$SqlQuery,...$Args) {
        $Stmnt = $Db->SqlExecute($SqlQuery,$Args);
        $this->__recordset($Stmnt);
        return $this;
    }
    /**
     * Get table row by index
     * @param int $No
     * @return \null|TableCursor
     */
    public function Row($No) {
        if($No < \count($this->Rows)) {
            return new TableCursor($No,null,$this);
        }
        return null;
    }

    /**
     * Get table column by column name
     * @param string $Column
     * @return TableCursor
     */
    public function Column($Column) {
        return new TableCursor(null,$Column,$this);
    }

    /**
     * Get\Set column value
     * @param int $Row
     * @param string $Column
     * @param mixed $Val
     * @return mixed
     */
    public function Cell($Row,$Column,$Val=null) {
        !is_null($Val) && $this->Rows[$Row][$this->Columns[$Column]] = $Val;
        return $this->Rows[$Row][$this->Columns[$Column]];
    }

    /**
     * Get columns list
     * @return array
     */
    public function &Columns() {
        return $this->Columns;
    }

    /**
     * Add new column to table
     * @param mixed $Columns
     * @return Table
     */
    public function AddColumns(...$Columns) {
        foreach($Columns as $c) {
            $this->Columns[$c] = $this->ColumnIndex ++;
        }
        return $this;
    }

    public function AddNumRow(...$ColumnValues) {
    }

    public function SetRows(&$Rows) {
        $this->Rows = $Rows;
        return $this;
    }

    public function Html($TableName='',$ShowRowNumber=true,$ShowColumns=true) {
        ob_start();
        $tblBlocks = [];
        if(!empty($TableName)) {
            $tblBlocks[] = "<caption>{$TableName}</caption>";
        }
        if($ShowColumns) {
            $tblBlocks[] = "<thead><tr>";
            if($ShowRowNumber) {
                $tblBlocks[] = "<th>№</th>";
            }
            foreach($this->Columns as $c=>$i) {
                $tblBlocks[] = "<th>{$c}</th>";
            }
            $tblBlocks[] = "</tr></thead>";
        }
        $tblBlocks[] = "<tbody>";
        $no = 0; foreach($this->Rows as $r) {
            $tblBlocks[] = "<tr>";
            if($ShowRowNumber) {
                $no ++;
                $tblBlocks[] = "<td class='th'>{$no}</td>";
            }
            foreach($r as $c) {
                $tblBlocks[] = "<td>{$c}</td>";
            }
            $tblBlocks[] = "</tr>";
        }
        $tblBlocks[] = "</tbody>";
        $tblBlocks = implode(PHP_EOL,$tblBlocks);
        print <<<"TABLE"
<table>
    $tblBlocks
</table>
TABLE;
        return ob_get_clean();
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
