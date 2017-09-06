<?php
namespace Sci\Math {
    /**
     * ���������� ����������
     * @param int $N
     * @param bool $Aprox ���������� ���������������� �������� ��������� �� ������� ���������
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
     * ���������� ����� ��������� ��� ��������� $N
     * @param int $N
     * @return int ����� ���������
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
