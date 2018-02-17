PHP sprite generator
====================

Proof of concept to generate a sprite image and its accompanying CSS.

Note that sprites may not always be the best solution:
https://stackoverflow.com/questions/32160790/does-using-image-sprites-make-sense-in-http-2

## Usage

1. Start a test server: `php -S localhost:9999`
2. Open http://localhost:9999/?sprite=flags.

### Arguments

 - `sprite`: The folder name inside `sources`, which contain all the individual images.
 - `format`: One of `(html|jpe?g|gif|png|css|less)`.
 - `size` (optional): Resize the width, in pixels, of the whole sprite. By default, it will grow to accommodate the orignal images.

## Author

Sébastien Lavoie <github@lavoie.sl>

## TODO

 - Support different sizes of images in the same sprite.
