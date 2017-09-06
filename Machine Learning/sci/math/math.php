<?php
namespace Sci\Math {
    /**
     * Вычисление факториала
     * @param int $N
     * @param bool $Aprox Высичление приблизительного значения факторила по формуле Стирлинга
     */
    function Factorial($N,$Aprox=false) {
	    if($N == 0) return 0;
	    if($N == 1) return 1;
	    if(!$Aprox) {
	        $Factorial = 1;
	        for($i=2;$i<=$N;$i++) $Factorial *= $i;
	        return $Factorial;
	    }
	    else {
	        return intval(sqrt( M_PI * 2 * $N ) * pow($N / M_E,$N) * pow(M_E,1/(12*$N)));
	    }
    }

    /**
     * Вычисление числа Фибоначчи для заданного $N
     * @param int $N
     * @return int число Фибонначи
     */
    function Fibonacci($N) {
	    if($N == 0) return 0;
	    if ($N <= 2) return 1;
	    $x = 1;
	    $y = 1;
	    $Fibonacci = 0;
	    for ($i = 2; $i < $N; $i++)
	    {
		    $Fibonacci = $x + $y;
		    $x = $y;
		    $y = $Fibonacci;
	    }
	    return $Fibonacci;
    }
}
