<?php

$arrProcessedData = [];

$intCtr = 1;
while ($intCtr <= 100) 
{
    # code...
    $intModuloThree = $intCtr % 3;
    $intModuloFive = $intCtr % 5;

    if ($intModuloThree == 0 && $intModuloFive != 0)
    {
        $arrProcessedData[] = 'foo';
    }
    else if ($intModuloFive == 0  && $intModuloThree != 0)
    {
        $arrProcessedData[] = 'bar';
    }
    else if ($intModuloThree == 0 && $intModuloFive == 0)
    {
        $arrProcessedData[] = 'foobar';
    }
    else
    {
        $arrProcessedData[] = $intCtr;
    }

    $intCtr++;
}

$strResult = implode(', ', $arrProcessedData);

echo $strResult;