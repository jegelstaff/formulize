<?php

class HTMLPurifier_Filter_WeGame extends HTMLPurifier_Filter
{
    
    public $name = 'WeGame';
    
    public function preFilter($html, $config, $context) {
		$pre_regex = '#<object[^>]+>.+?'.'http://www.wegame.com/static/flash/player2.swf\?tag=([A-Za-z0-9\-_]+).+?</object>#s';
		$pre_replace = '<span class="wegame-embed">\1</span>';
		return preg_replace($pre_regex, $pre_replace, $html);
    }
    
    public function postFilter($html, $config, $context) {
        $post_regex = '#<span class="wegame-embed">([A-Za-z0-9\-_]+)</span>#';
        $post_replace = '<object width="480" height="387" '.
            'data="http://www.wegame.com/static/flash/player2.swf?tag=\1">'.
            '<param name="movie" value="http://www.wegame.com/static/flash/player2.swf?tag=\1"></param>'.
            '<param name="wmode" value="transparent"></param>'.
            '<!--[if IE]>'.
            '<embed src="http://www.wegame.com/static/flash/player2.swf?tag=\1"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="480" height="387" />'.
            '<![endif]-->'.
            '</object>';
        return preg_replace($post_regex, $post_replace, $html);
    }
    
}
