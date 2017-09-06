<?php
namespace Sci\Math;
/**
 * distance short summary.
 *
 * distance description.
 *
 * @version 1.0
 * @author Asus
 */
class Distance
{
    /**
     * Косинусная мера сходства между объектами $A и $B
     * @param array $A значения признаков объекта A
     * @param array $B значения признаков объекта B
     * @param array $W - весовые коэффициенты i-го признака
     * @return float
     */
    static public function Cosine($A,$B,$W=null) {
        $SumX1 = $SumX2 = $Sum = 0.0;
        if(!empty($W)) {
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += $W[$i] * $A[$i] * $W[$i] * $B[$i];
                $SumX1 += $W[$i] * $A[$i] * $W[$i] * $A[$i];
                $SumX2 += $W[$i] * $B[$i] * $W[$i] * $B[$i];
            }
        }
        else {
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += $A[$i] * $B[$i];
                $SumX1 += $A[$i]*$A[$i];
                $SumX2 += $B[$i]*$B[$i];
            }
        }
        return $Sum / sqrt($SumX1*$SumX2);
    }
    /**
     * Манхэттеновское расстояние между объектами $A и $B
     * @param array $A значения признаков объекта A
     * @param array $B значения признаков объекта B
     * @param array $W - весовые коэффициенты i-го признака
     * @return float
     */
    static public function Manhattan($A,$B,$W=null) {
        $Sum = 0.0;
        if(!empty($W)) {
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += $W[$i] * abs($A[$i]-$B[$i]);
            }
        }else{
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += abs($A[$i]-$B[$i]);
            }
        }
        return $Sum;
    }
    /**
     * Евклидово расстояние между объектами $A и $B
     * @param array $A значения признаков объекта A
     * @param array $B значения признаков объекта B
     * @param array $W - весовые коэффициенты i-го признака
     * @return float
     */
    static public function Euclidean($A,$B,$W=null) {
        $Sum = 0.0;
        if(!empty($W)) {
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += $W[$i] * pow($A[$i]-$B[$i],2);
            }
        }
        else {
            for($i=0;$i<min(count($A),count($B));$i++) {
                $Sum += pow($A[$i]-$B[$i],2);
            }
        }
        return sqrt($Sum);
    }
}