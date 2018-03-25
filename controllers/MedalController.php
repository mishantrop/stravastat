<?php
define('TEMPLATES_PATH', BASE_PATH.'/assets/images/templates/');

use GDText\Box;
use GDText\Color;

$disciplines = [
	'avgspeed' => [
		'image' => TEMPLATES_PATH.'avgspeed.png',
		'color' => [43, 43, 0],
	],
    'climb' => [
        'image' => TEMPLATES_PATH.'climb.png',
        'color' => [238, 43, 122],
    ],
    'maxdistance' => [
        'image' => TEMPLATES_PATH.'maxdistance.png',
        'color' => [0, 175, 254],
    ],
    'maxspeed' => [
        'image' => TEMPLATES_PATH.'maxspeed.png',
        'color' => [237, 26, 35],
    ],
    'totaldistance' => [
        'image' => TEMPLATES_PATH.'totaldistance.png',
        'color' => [0, 176, 0],
    ],
];

$name = isset($_GET['name']) ? (string)$_GET['name'] : 'Вася Пупкин';
$value = isset($_GET['value']) ? (string)$_GET['value'] : '228';
$units = isset($_GET['units']) ? (string)$_GET['units'] : 'км/ч';
$discipline = isset($_GET['discipline']) ? (string)$_GET['discipline'] : '';
if (!array_key_exists($discipline, $disciplines)) {
    $discipline = 'maxspeed';
}
$value .= ' '.mb_strtoupper($units);
$name = mb_strtoupper($name);

// Create Image From Existing File
$image = imagecreatefrompng($disciplines[$discipline]['image']);
$color = new Color(
    $disciplines[$discipline]['color'][0],
    $disciplines[$discipline]['color'][1],
    $disciplines[$discipline]['color'][2]
);
// Name
$boxName = new Box($image);
$boxName->setFontFace(BASE_PATH.'assets/fonts/Rubik-Medium.ttf');
$boxName->setFontSize(90);
$boxName->setLineHeight(1);
$boxName->setFontColor($color);
$boxName->setBox(325, 900, 600, 750);
$boxName->setTextAlign('center', 'top');
$boxName->draw($name);

// Value
$boxValue = new Box($image);
$boxValue->setFontFace(BASE_PATH.'assets/fonts/Rubik-Medium.ttf');
$boxValue->setFontSize(100);
$boxValue->setLineHeight(1);
$boxValue->setFontColor($color);
$boxValue->setBox(325, 180, 600, 750);
$boxValue->setTextAlign('center', 'top');
$boxValue->draw($value);

header('Content-type: image/png');
imagepng($image, null, 9, PNG_ALL_FILTERS);
imagedestroy($image);
