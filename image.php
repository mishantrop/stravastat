<?php
//Set the Content Type
header('Content-type: image/jpeg');

// Create Image From Existing File
$image = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'].'/assets/images/examples/climb.jpg');

// Allocate A Color For The Text
$fontColor = imagecolorallocate($image, 255, 0, 0);

// Set Path to Font File
$fontPath = $_SERVER['DOCUMENT_ROOT'].'/assets/fonts/Rubik-Medium.ttf';

// Set Text to Be Printed On Image
$text = $_GET['name'];

// Print Text On Image
// array imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
imagettftext($image, 60, 0, 75, 300, $fontColor, $fontPath, $text);

// TODO
// http://php.net/manual/ru/function.imagettfbbox.php

// Send Image to Browser
imagejpeg($image);

// Clear Memory
imagedestroy($image);