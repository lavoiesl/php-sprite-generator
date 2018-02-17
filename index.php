<?php

if (empty($_GET['sprite'])) {
  header('HTTP/' . $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
  die();
}

$sprite_name = $_GET['sprite'];
$format = isset($_GET['format']) ? $_GET['format'] : 'html';
$files = glob("sources/$sprite_name/*.png");

$size = empty($_GET['size']) ? false : (int) $_GET['size'];

// Checksum all files
$hash = hash_init('crc32');
foreach ($files as $file) {
  hash_update_file($hash, $file);
}
hash_update_file($hash, __FILE__); // Also hash this PHP file
$etag = "${sprite_name}.${format}/" . hash_final($hash);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
  header('HTTP/' . $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
  die();
}

$images = array();
foreach ($files as $file) {
  $name = pathinfo($file, PATHINFO_FILENAME);
  $image = false;
  if (!$size || $format != 'css') {
    $image = new Imagick($file);
    if (!$size) {
      $size = $image->getImageWidth();
    }
    if ($image->getImageWidth() != $size) {
      $image->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
    }
  }

  $images[$name] = $image;
}

$side = ceil(sqrt(count($files))) * $size;

switch ($format) {
  case 'less':
    header('Content-Type: text/less');
    header("ETag: ${etag}");

    echo ".sprite {
  display: inline-block;
  background: transparent 0 0 no-repeat scroll;

  &.$sprite_name {
    background-image: url(images/$sprite_name.png);
    @height: ${size}px;
    @width: ${size}px;
    height: @height;
    width: @width;

";

    $names = array_keys($images);
    $max_length = max(array_map('strlen', $names));

    $x = $y = 0;
    $side /= $size;
    foreach ($names as $name) {
      $name = str_pad($name, $max_length);
      if ($x >= $side) {
        $x = 0;
        $y += 1;
      }

      echo "    &.$name {background-position: (-$x * @width) (-$y * @height)}\n";
      $x += 1;
    }

    echo "  }\n}\n";
  break;

  case 'css':
    header('Content-Type: text/css');
    header("ETag: ${etag}");

    echo ".sprite.$sprite_name {
  background: transparent url('?sprite=${sprite_name}&size=${size}&format=png') 0 0 no-repeat scroll;
  display: inline-block;
  height: ${size}px;
  width: ${size}px;
}
";

    $names = array_keys($images);
    $max_length = max(array_map('strlen', $names));

    $x = $y = 0;
    foreach ($names as $name) {
      $name = str_pad($name, $max_length);
      if ($x >= $side) {
        $x = 0;
        $y += $size;
      }
      $_x = $x ? "-{$x}px" : '0';
      $_y = $y ? "-{$y}px" : '0';

      echo ".sprite.$sprite_name.$name {background-position: $_x $_y}\n";
      $x += $size;
    }

    break;

  case 'html':
    header('Content-Type: text/html');
    header("ETag: ${etag}");

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>${sprite_name}</title>
  <link rel="stylesheet" href="?sprite=${sprite_name}&size=${size}&format=css">
</head>
<body>
<table border="1" cellspacing=0 cellpadding=5 style="border-collapse: collapse">
  <tr>
HTML;

    $names = array_keys($images);

    $x = $y = 0;
    foreach ($names as $name) {
      if ($x >= $side) {
        $x = 0;
        $y += $size;
        echo "\n  </tr>\n  <tr>";
      }

      echo "\n    <td><div class=\"sprite ${sprite_name} ${name}\"></div></td>";

      $x += $size;
    }

    echo "\n  </tr>\n</table>\n</body>\n</html>\n";

    break;

  default:
    $sprite = new Imagick;
    $sprite->setFormat($format);
    $color = 'transparent';
    if ($format == 'jpg' || $format == 'jpeg') {
      $pixel = current($images)->getImagePixelColor(0, 0);
      $color = $pixel->getColorAsString();
      if ($color == 'rgba(0,0,0,0)') $color = 'white';
    }
    $sprite->newImage($side, $side, $color);

    $x = $y = 0;
    foreach ($images as $image) {
      if ($x >= $side) {
        $x = 0;
        $y += $size;
      }
      $sprite->compositeImage($image, Imagick::COMPOSITE_DEFAULT, $x, $y);
      $x += $size;
    }

    header('Content-Type: ' . $sprite->getImageMimeType());
    header("ETag: ${etag}");
    echo "" . $sprite;

    break;
}
