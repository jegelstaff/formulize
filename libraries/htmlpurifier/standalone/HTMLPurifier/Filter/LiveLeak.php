<?php

class HTMLPurifier_Filter_LiveLeak extends HTMLPurifier_Filter
{
    
    public $name = 'LiveLeak';
    
    public function preFilter($html, $config, $context) {
		$pre_regex = '#<object[^>]+>.+?'.'http://www.liveleak.com/e/([A-Za-z0-9\-_]+).+?</object>#s';
		$pre_replace = '<span class="liveleak-embed">\1</span>';
		return preg_replace($pre_regex, $pre_replace, $html);
    }
    
    public function postFilter($html, $config, $context) {
        $post_regex = '#<span class="liveleak-embed">([A-Za-z0-9\-_]+)</span>#';
        $post_replace = '<object width="450" height="370" '.
            'data="http://www.liveleak.com/e/\1">'.
            '<param name="movie" value="http://www.liveleak.com/e/\1"></param>'.
            '<param name="wmode" value="transparent"></param>'.
            '<!--[if IE]>'.
            '<embed src="http://www.liveleak.com/e/\1"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="450" height="370" />'.
            '<![endif]-->'.
            '</object>';
        return preg_replace($post_regex, $post_replace, $html);
    }
}