<?php
thumb($_GET['file'], $_GET['w'], $_GET['h']);
function thumb($img, $w = 100, $h = 100, $fill = true) {
	if (!extension_loaded('gd') && !extension_loaded('gd2')) {
		trigger_error("No dispones de la libreria GD para generar la imagen.", E_USER_WARNING);
		return false;
	}

	$imgInfo = getimagesize($img);
	switch ($imgInfo[2]) {
		case 1: $im = imagecreatefromgif($img); break;
		case 2: $im = imagecreatefromjpeg($img);  break;
		case 3: $im = imagecreatefrompng($img); break;
		default:  trigger_error('Tipo de imagen no reconocido.', E_USER_WARNING);  break;
	}

	if ($imgInfo[0] <= $w && $imgInfo[1] <= $h && !$fill) {
		$nHeight = $imgInfo[1];
		$nWidth = $imgInfo[0];
	}else{
		if ($w/$imgInfo[0] < $h/$imgInfo[1]) {
			$nWidth = $w;
			$nHeight = $imgInfo[1]*($w/$imgInfo[0]);
		}else{
			$nWidth = $imgInfo[0]*($h/$imgInfo[1]);
			$nHeight = $h;
		}
	}
  
	$nWidth = round($nWidth);
	$nHeight = round($nHeight);

	$newImg = imagecreatetruecolor($nWidth, $nHeight);

	imagecopyresampled($newImg, $im, 0, 0, 0, 0, $nWidth, $nHeight, $imgInfo[0], $imgInfo[1]);

	header("Content-type: ". $imgInfo['mime']);

	switch ($imgInfo[2]) {
		case 1: imagegif($newImg); break;
		case 2: imagejpeg($newImg);  break;
		case 3: imagepng($newImg); break;
		default:  trigger_error('Imposible mostrar la imagen.', E_USER_WARNING);  break;
	}
  
	imagedestroy($newImg);
}
?>

