<?php
/*
 * Converts Youtube Iframe code back to <span class="youtube-iframe">(itemid)</span>
 * prior to filtering, then creates an object embed code after filtering.
 */

class HTMLPurifier_Filter_YouTubeIframe extends HTMLPurifier_Filter
{

    public $name = 'YouTubeIframe';

    public function preFilter($html, $config, $context) {
        $pre_regex = '#<iframe\b[a-zA-Z0-9/"=-\s]+?\bsrc="http://www.youtube.com/embed/([A-Za-z0-9]+)"[a-zA-Z0-9/"=-\s]*?></iframe>#';
        $pre_replace = '<span class="youtube-iframe">\1</span>';
        return preg_replace($pre_regex, $pre_replace, $html);
    }

    public function postFilter($html, $config, $context) {
        $post_regex = '#<span class="youtube-iframe">([A-Za-z0-9]+)</span>#';
        return preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
    }

    protected function armorUrl($url) {
        return str_replace('--', '-&#45;', $url);
    }

    protected function postFilterCallback($matches) {
        $url = $this->armorUrl($matches[1]);
        return '<object width="425" height="350" type="application/x-shockwave-flash" '.
            'data="http://www.youtube.com/'.$url.'">'.
            '<param name="movie" value="http://www.youtube.com/'.$url.'"></param>'.
            '<!--[if IE]>'.
            '<embed src="http://www.youtube.com/'.$url.'"'.
            'type="application/x-shockwave-flash"'.
            'wmode="transparent" width="425" height="350" />'.
            '<![endif]-->'.
            '</object>';

    }
}

// vim: et sw=4 sts=4
