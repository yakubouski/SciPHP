<?php
namespace Sci\Math {

    class Vector implements \ArrayAccess, \Iterator,\Countable
    {
        public $Values;
        public $Default;
        public $Dimension;
        public $Rows;
        private $It = 0;
        public function __construct($Dimension,$Vector=[],$Rows=1,$Default=0.0) {
            $this->Default = $Default;
            $this->Dimension = $Dimension;
            $this->Rows = max($Rows,1);
            $this->Values = !empty($Vector) ? $Vector : [];
        }

        public function __invoke($i,$j=0,$val=null) {
            return $this->Value($i,$j,$val);
        }

        public function Value($i,$j=0,$val=null) {
            $n = $j * $this->Dimension + $i;
            !is_null($val) && $this->Values[$n] = $val;
            return isset($this->Values[$n]) ? $this->Values[$n] : $this->Default;
        }

        public function Set($Vector,$Dimension=null,$Rows=null) {
            !is_null($Dimension) && ($this->Dimension = $Dimension);
            !is_null($Rows) && ($this->Rows = max($Rows,1));
            $this->Values = $Vector;
            return $this;
        }

        public function FillRandom($Min,$Max) {
            foreach($this as $i=>$v) {
                $this[$i] = (random_int(round($Min*1000000),round($Max*1000000))/1000000);
            }
            return $this;
        }

        public function Fill($Value) {
            foreach($this as &$v) {
                $v = $Value;
            }
            return $this;
        }
        /**
         * Summary of LinearNormalization
         * @param '01'|'-11' $Limit
         * @return Normalization\Linear
         */
        public function LinearNormalization($Limit='01') {
            return new Normalization\Linear($this,$Limit);
        }
        /**
         * Summary of SingularNormalization
         * @param array $Weights
         * @return Normalization\Scale
         */
        public function ScaleNormalization($Weights=[]) {
            return new Normalization\Scale($this,$Weights);
        }

        /**
         * Summary of SigmoidNormalizetion
         * @param float $Alpha 0.0 < $Alpha < 1.0
         * @param '01'|'-11' $Limit
         * @return Normalization\Sigmoid
         */
        public function SigmoidNormalizetion($Alpha=1.0, $Limit='01') {
            return new Normalization\Sigmoid($this,$Alpha,$Limit);
        }

        #region Countable Members

        /**
         * Count elements of an object
         * This method is executed when using the count() function on an object implementing Countable .
         *
         * @return int
         */
        function count()
        {
            return $this->Dimension * $this->Rows;
        }

        #endregion

        #region Iterator Members

        /**
         * Return the current element
         * Returns the current element.
         *
         * @return mixed
         */
        function current()
        {
            return isset($this->Values[$this->It]) ? $this->Value($this->It) : $this->Default;
        }

        /**
         * Return the key of the current element
         * Returns the key of the current element.
         *
         * @return scalar
         */
        function key()
        {
            return $this->It;
        }

        /**
         * Move forward to next element
         * Moves the current position to the next element.
         *
         * @return void
         */
        function next()
        {
            ++$this->It;
        }

        /**
         * Rewind the Iterator to the first element
         * Rewinds back to the first element of the Iterator.
         *
         * @return void
         */
        function rewind()
        {
            $this->It = 0;
        }

        /**
         * Checks if current position is valid
         * This method is called after Iterator::rewind() and Iterator::next() to check if the current position is valid.
         *
         * @return bool
         */
        function valid()
        {
            return $this->It < $this->count();
        }

        #endregion

        #region ArrayAccess Members

        /**
         * Whether an offset exists
         * Whether or not an offset exists.
         *
         * @param mixed $offset An offset to check for.
         *
         * @return bool
         */
        function offsetExists($offset)
        {
            return $offset < $this->count();
        }

        /**
         * Offset to retrieve
         * Returns the value at specified offset.
         *
         * @param mixed $offset The offset to retrieve.
         *
         * @return mixed
         */
        function offsetGet($offset)
        {
            return isset($this->Values[$offset]) ? $this->Value($offset) : $this->Default;
        }

        /**
         * Assign a value to the specified offset
         * Assigns a value to the specified offset.
         *
         * @param mixed $offset The offset to assign the value to.
         * @param mixed $value The value to set.
         *
         * @return void
         */
        function offsetSet($offset, $value)
        {
            $this->Values[$offset] = $value;
        }

        /**
         * Unset an offset
         * Unsets an offset.
         *
         * @param mixed $offset The offset to unset.
         *
         * @return void
         */
        function offsetUnset($offset)
        {
            // TODO: implement the function ArrayAccess::offsetUnset
        }

        #endregion
    }

}