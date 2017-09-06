<?php
use Sci\Math\Vector as Vector;

$v = new Vector(4,[ -5,  2, -18,  -1, ]);
$nv = $v->LinearNormalization();
$sv = $v->SigmoidNormalizetion();
$siv = $v->ScaleNormalization();

printVector('Source vector',['X{|1}','X{|2}','X{|3}','X{|4}'],$v);
printVector('Linear normal vector',['~X{lin|1}','~X{lin|2}','~X{lin|3}','~X{lin|4}','X{lin|1}','X{lin|2}','X{lin|3}','X{lin|4}'],$nv,new Vector(4,[$nv->Denormalize($nv[0]),$nv->Denormalize($nv[1]),$nv->Denormalize($nv[2]),$nv->Denormalize($nv[3])]));

printVector('Sigmoid normal vector',['~X{sig(1.0)|1}','~X{sig(1.0)|2}','~X{sig(1.0)|3}','~X{sig(1.0)|4}','X{sig(1.0)|1}','X{sig(1.0)|2}','X{sig(1.0)|3}','X{sig(1.0)|4}'],$sv,new Vector(4,[$sv->Denormalize($sv[0]),$sv->Denormalize($sv[1]),$sv->Denormalize($sv[2]),$sv->Denormalize($sv[3])]));

printVector('Scale normal vector',['~X{scale|1}','~X{scale|2}','~X{scale|3}','~X{scale|4}','X{scale|1}','X{scale|2}','X{scale|3}','X{scale|4}'],$siv,new Vector(4,[$siv->Denormalize($siv[0]),$siv->Denormalize($siv[1]),$siv->Denormalize($siv[2]),$siv->Denormalize($siv[3])]));

$matrix = new Vector(4,[
    -5,  2, -18,  -1,
     0,  5,  -9,  16,
     2, -4,  15, -18,
    -3,  1, -10,  -3,
     5,  0,  16,   1,
     1, -5,  14, -23,
    -3, -1,  -6, -11,
     2,  5,  -3,  18,
     4,  3,   7,  12,
     0, -2,   5, -12,
],10);

printVector('Vector as matrix',['X{|1}','X{|2}','X{|3}','X{|4}'],$matrix->LinearNormalization());
