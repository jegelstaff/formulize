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

    * @package WideImage
  **/
	
	/**
	 * TTF font support class
	 * 
	 * @package WideImage
	 */
	class WideImage_Font_TTF
	{
		public $face;
		public $size;
		public $color;
		
		function __construct($face, $size, $color)
		{
			$this->face = $face;
			$this->size = $size;
			$this->color = $color;
		}
		
		function writeText($image, $x, $y, $text, $angle = 0)
		{
			if ($image->isTrueColor())
				$image->alphaBlending(true);
			
			imagettftext($image->getHandle(), $this->size, $angle, $x, $y, $this->color, $this->face, $text);
		}
	}
	
?>