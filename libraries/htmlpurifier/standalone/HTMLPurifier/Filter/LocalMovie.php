<?php

class HTMLPurifier_Filter_LocalMovie extends HTMLPurifier_Filter
{
    public $name = 'LocalMovie';
    
    public function preFilter($html, $config, $context) {
	$localhost = "http://".$_SERVER['SERVER_NAME']."/uploads/flashmovies/"; // example: http://www.yourdomain.com/uploads/flashmovies/  (you must include the trailing slash)
        $pre_regex = '#<object[^>]+>.+?'.$localhost.'([A-Za-z0-9\-_\.]+).+?</object>#s';
        $pre_replace = '<span class="localmovie-embed">\1</span>';
        return preg_replace($pre_regex, $pre_replace, $html);
    }
    
    public function postFilter($html, $config, $context) {
	$localhost = "http://".$_SERVER['SERVER_NAME']."/uploads/flashmovies/"; // example: http://www.yourdomain.com/uploads/flashmovies/  (you must include the trailing slash)
        $post_regex = '#<span class="localmovie-embed">([A-Za-z0-9\-_\.]+)</span>#';
        $post_replace = '<object width="425" height="350" '.
            'data="'.$localhost.'\1">'.
            '<param name="movie" value="'.$localhost.'\1"></param>'.
            '<param name="wmode" value="transparent"></param>'.
            '<!--[if IE]>'.
            '<embed src="'.$localhost.'\1"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="425" height="350" />'.
            '<![endif]-->'.
            '</object>';
        return preg_replace($post_regex, $post_replace, $html);
    }
    
}