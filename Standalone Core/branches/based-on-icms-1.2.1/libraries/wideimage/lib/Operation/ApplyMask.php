<?php
	/**
 * @author Gasper Kozak
 * @copyright 2007, 2008, 2009

    This file is part of WideImage.
		
    WideImage is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 2.1 of the License, or
    (at your option) any later version.
		
    WideImage is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.
		
    You should have received a copy of the GNU Lesser General Public License
    along with WideImage; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    * @package Internal/Operations
  **/
	
	/**
	 * ApplyMask operation class
	 * 
	 * @package Internal/Operations
	 */
	class WideImage_Operation_ApplyMask
	{
		/**
		 * Applies a mask on the copy of source image
		 *
		 * @param WideImage_Image $image
		 * @param WideImage_Image $mask
		 * @param smart_coordinate $left
		 * @param smart_coordinate $top
		 * @return WideImage_Image
		 */
		function execute($image, $mask, $left = 0, $top = 0)
		{
			$left = WideImage_Coordinate::fix($image->getWidth(), $left);
			$top = WideImage_Coordinate::fix($image->getHeight(), $top);
			
			$width = $image->getWidth();
			if ($width > $mask->getWidth())
				$width = $mask->getWidth();
			
			$height = $image->getHeight();
			if ($height > $mask->getHeight())
				$height = $mask->getHeight();
			
			$result = $image->asTrueColor();
			$result->alphaBlending(false);
			$result->saveAlpha(true);
			
			$srcTransparentColor = $image->getTransparentColor();
			if ($srcTransparentColor >= 0)
			{
				$trgb = $image->getColorRGB($srcTransparentColor);
				$trgb['alpha'] = 127;
				$destTransparentColor = $result->allocateColorAlpha($trgb);
				$result->setTransparentColor($destTransparentColor);
			}
			else
				$destTransparentColor = $result->allocateColorAlpha(255, 255, 255, 127);
			
			for ($x = 0; $x < $width; $x++)
				for ($y = 0; $y < $height; $y++)
					if ($left + $x < $image->getWidth() && $top + $y < $image->getHeight())
					{
						$srcColor = $image->getColorAt($left + $x, $top + $y);
						if ($srcColor == $srcTransparentColor)
							$destColor = $destTransparentColor;
						else
						{
							$maskRGB = $mask->getRGBAt($x, $y);
							if ($maskRGB['red'] == 0)
								$destColor = $destTransparentColor;
							elseif ($srcColor >= 0)
							{
								$imageRGB = $image->getRGBAt($left + $x, $top + $y);
								$level = ($maskRGB['red'] / 255) * (1 - $imageRGB['alpha'] / 127);
								$imageRGB['alpha'] = 127 - round($level * 127);
								if ($imageRGB['alpha'] == 127)
									$destColor = $destTransparentColor;
								else
									$destColor = $result->allocateColorAlpha($imageRGB);
							}
							else
								$destColor = $destTransparentColor;
						}
						$result->setColorAt($left + $x, $top + $y, $destColor);
					}
			
			return $result;
		}
	}
?>