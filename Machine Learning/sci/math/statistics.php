<?php
namespace Sci\Math {

	class Statistics {
        /**
         * Summary of Min
         * @param mixed $Values array or \Traversable object
         * @return \double|integer
         */
        static public function Min($Values) {
            return call_user_func_array('\min',is_array($Values) ? $Values : iterator_to_array($Values,false));
        }
        /**
         * Summary of Max
         * @param mixed $Values array or \Traversable object
         * @return \double|integer
         */
        static public function Max($Values) {
            return call_user_func_array('\max',is_array($Values) ? $Values : iterator_to_array($Values,false));
        }
        /**
         * Summary of Range
         * @param mixed $Min
         * @param mixed $Max
         * @return \double|integer
         */
        static public function Range($Min,$Max) {
            return $Max-$Min;
        }
        /**
         * Summary of MinMaxRange
         * @param mixed $Values array or \Traversable object
         * @return array[min,max,range]
         */
        static public function MinMaxRange($Values) {
            $_ = null;
            if(is_array($Values)) { $_ =& $Values; }
            else { $_ = iterator_to_array($Values,false); }

            $min = call_user_func_array('\min',$_);
            $max = call_user_func_array('\max',$_);
            return [$min,$max,$max-$min];
        }
	}
}