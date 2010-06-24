<?php

class HTMLPurifier_Filter_GoogleVideo extends HTMLPurifier_Filter
{
    
    public $name = 'GoogleVideo';
    
    public function preFilter($html, $config, $context) {
        $pre_regex = '#<embed[^>]+>.+?'.
            'http://video.google.com/googleplayer.swf\?docid=([A-Za-z0-9]+).+?</embed>#s';
        $pre_replace = '<span class="googlevideo-embed">\1</span>';
        return preg_replace($pre_regex, $pre_replace, $html);
    }
    
    public function postFilter($html, $config, $context) {
        $post_regex = '#<span class="googlevideo-embed">([A-Za-z0-9]+)</span>#';
        $post_replace = '<object width="400" height="326" '.
            'data="http://video.google.com/googleplayer.swf?docid=\1">'.
            '<param name="movie" value="http://video.google.com/googleplayer.swf?docid=\1"></param>'.
            '<param name="wmode" value="transparent"></param>'.
            '<!--[if IE]>'.
            '<embed src="http://video.google.com/googleplayer.swf?docid=\1"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="400" height="326" />'.
            '<![endif]-->'.
            '</object>';
        return preg_replace($post_regex, $post_replace, $html);
    }
    
}