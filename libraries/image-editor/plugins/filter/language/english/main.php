<?php
define('_FILTER_PLUGNAME', 'Filter Tool');
define('_FILTER_PLUGDESC', 'Plugin to allow the DHTML Image Editor apply filters in the images. Select the desired filter, set the parameters (if have) and click on the button to apply or preview the filter.');

define('_FILTER_PREVIEW', 'Preview');
define('_FILTER_EXECUTE', 'Filter');
define('_FILTER_SELECT', 'Select a filter');

define('_FILTER_NEGATE', 'Negative');
define('_FILTER_NEGATE_DESC', 'Reverses all colors of the image.');

define('_FILTER_GRAYSCALE', 'Grayscale');
define('_FILTER_GRAYSCALE_DESC', 'Converts the image into grayscale.');

define('_FILTER_BRIGHTNESS', 'Brightness');
define('_FILTER_BRIGHTNESS_DESC', 'Changes the brightness of the image.');
define('_FILTER_BRIGHTNESS_ARG_TITLE', 'Level');
define('_FILTER_BRIGHTNESS_ARG_DESC', '-255 = min brightness, 0 = no change, +255 = max brightness');

define('_FILTER_CONTRAST', 'Contrast');
define('_FILTER_CONTRAST_DESC', 'Changes the contrast of the image.');
define('_FILTER_CONTRAST_ARG_TITLE', 'Level');
define('_FILTER_CONTRAST_ARG_DESC', '-100 = mix contrast, 0 = no change, +100 = max contrast');

define('_FILTER_COLORIZE', 'Colorize');
define('_FILTER_COLORIZE_DESC', 'Adds (subtracts) specified RGB values to each pixel.');
define('_FILTER_COLORIZE_ARG_TITLE', 'Red');
define('_FILTER_COLORIZE_ARG_DESC', '-255 = min, 0 = no change, +255 = max');
define('_FILTER_COLORIZE_ARG1_TITLE', 'Green');
define('_FILTER_COLORIZE_ARG1_DESC', '-255 = min, 0 = no change, +255 = max');
define('_FILTER_COLORIZE_ARG2_TITLE', 'Blue');
define('_FILTER_COLORIZE_ARG2_DESC', '-255 = min, 0 = no change, +255 = max');

define('_FILTER_EDGEDETECT', 'Highlight Edges');
define('_FILTER_EDGEDETECT_DESC', 'Uses edge detection to highlight the edges in the image.');

define('_FILTER_EMBOSS', 'Emboss');
define('_FILTER_EMBOSS_DESC', 'Embosses the image.');

define('_FILTER_GAUSSIAN', 'Gaussian Blur');
define('_FILTER_GAUSSIAN_DESC', 'Blurs the image using the Gaussian method.');

define('_FILTER_SELECTIVE', 'Selective Blur');
define('_FILTER_SELECTIVE_DESC', 'Blurs the image.');

define('_FILTER_REMOVAL', 'Sketchy');
define('_FILTER_REMOVAL_DESC', 'Uses mean removal to achieve a "sketchy" effect.');

define('_FILTER_SMOOTH', 'Smooth');
define('_FILTER_SMOOTH_DESC', 'Makes the image smoother. Applies a 9-cell convolution matrix where center pixel has the weight arg1 and others weight of 1.0. The result is normalized by dividing the sum with arg1 + 8.0 (sum of the matrix).');
define('_FILTER_SMOOTH_ARG_TITLE', 'Level');
define('_FILTER_SMOOTH_ARG_DESC', 'any float is accepted, large value (in practice: 2048 or more) = no change');

define('_FILTER_SEPIA', 'Sepia');
define('_FILTER_SEPIA_DESC', 'Apply sepia effects in the image');
?>