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

	* @package Internals
  **/
	
	
	/**
	 * @package Exceptions
	 */
	class WideImage_InvalidCoordinateException extends WideImage_Exception {}
	
	/**
	 * A utility class for smart coordinates
	 *  
	 * @package Internals
	 **/
	class WideImage_Coordinate
	{
		static protected $coord_regex = array("[0-9]+", "[0-9]+\.[0-9]+", "[0-9]+%", "[0-9]+\.[0-9]+%");
		
		/**
		 * Parses a numeric or string representation of a corrdinate into a structure
		 * 
		 * @param string $coord Smart coordinate
		 * @return array Parsed smart coordinate
		 */
		static function parse($coord)
		{
			$coord = trim($coord);
			
			if (strlen($coord) == 0)
				return array('type' => 'abs', 'value' => '+0');
			
			$comp_regex = implode('|', self::$coord_regex);
			
			if (preg_match("/^([+-])?(\s+)?({$comp_regex})$/", $coord, $match))
			{
				if ($match[1] == '')
					$match[1] = '+';
				return array('type' => 'abs', 'value' => $match[1] . $match[3]);
			}
			
			if (preg_match("/^([+-])?(\s+)?({$comp_regex})(\s+)?([+-])(\s+)?({$comp_regex})$/", $coord, $match))
			{
				if ($match[1] == '')
					$match[1] = '+';
				return array('type' => 'cal', 'pivot' => $match[1] . $match[3], 'value' => $match[5] . $match[7]);
			}
		}
		
		/**
		 * Evaluates the $coord relatively to $dim
		 * 
		 * @param string $coord A numeric value or percent string
		 * @param int $dim Dimension
		 * @return int Calculated value
		 */
		static function evaluate($coord, $dim)
		{
			$comp_regex = implode('|', self::$coord_regex);
			if (preg_match("/^([+-])?({$comp_regex})$/", $coord, $matches))
			{
				$sign = intval($matches[1] . "1");
				$val = $matches[2];
				if (substr($val, -1) === '%')
					return intval(round($sign * $dim * floatval(str_replace('%', '', $val)) / 100));
				else
					return $sign * intval(round($val));
			}
		}
		
		/**
		 * Calculates and fixes a smart coordinate into a numeric value
		 * 
		 * @param int $dim Dimension
		 * @param mixed $value Smart coordinate, relative to $dim
		 * @param bool $clip Clip if $value outside [0, $dim]
		 * @return int Calculated value
		 */
		static function fix($dim, $value, $clip = false)
		{
			$coord = self::parse($value);
			if ($coord === null)
				throw new WideImage_InvalidCoordinateException("Couldn't parse coordinate '$value' properly.");
			
			if ($coord['type'] === 'abs')
			{
				$result = self::evaluate($coord['value'], $dim);
			}
			elseif ($coord['type'] === 'cal')
			{
				$p = self::evaluate($coord['pivot'], $dim);
				$v = self::evaluate($coord['value'], $dim);
				$result = $p + $v;
			}
			
			if ($clip)
			{
				if ($result < 0)
					return 0;
				elseif ($result >= $dim)
					return $dim;
			}
			return $result;
		}
		
		/**
		 * Fix a coordinate for a resize (limits by image weight and height)
		 * 
		 * @param WideImage_Image $img
		 * @param int $width Width of the image
		 * @param int $height Height of the image
		 * @return array An array(width, height), fixed for resizing
		 */
		static function fixForResize($img, $width, $height)
		{
			if ($width === null && $height === null)
				return array($img->getWidth(), $img->getHeight());
			
			if ($width !== null)
				$width = self::fix($img->getWidth(), $width);
			
			if ($height !== null)
				$height = self::fix($img->getHeight(), $height);
			
			if ($width === null)
				$width = floor($img->getWidth() * $height / $img->getHeight());
			
			if ($height === null)
				$height = floor($img->getHeight() * $width / $img->getWidth());
			
			return array($width, $height);
		}
	}
?>