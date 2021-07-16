<?php
namespace App\Services;

use App\Exceptions\HTMLValidateException;

class HtmlValidateService {

	public function content($content)
	{
		$content = urldecode( $content );
        $dom = new \DOMDocument();
        $dom->validateOnParse = TRUE;
        libxml_use_internal_errors( TRUE );
        $dom->loadHTML( $content );
        $results = libxml_get_errors();
        $dom = NULL;
        libxml_clear_errors();

        if( !empty( $results ) && count( $results ) > 0 ){
            foreach( $results as $error ){
                $prefix = ['Tag', 'invalid'];
                $getTag = str_replace($prefix, '', $error->message);
                $html5Tags = $this->html5Tags();
                if(!in_array(trim($getTag), $html5Tags)) {
                    throw new HTMLValidateException('Invalid Template Content: '.$error->message);
                }
            }
        }
	}

    public function html5Tags()
    {
        return [
            'article',
            'aside',
            'details',
            'header',
            'hgroup',
            'footer',
            'nav',
            'section',
            'summary',
            'datalist',
            'keygen',
            'meter',
            'bdi',
            'mark',
            'output',
            'progress',
            'rp',
            'rt',
            'ruby',
            'wbr',
            'audio',
            'canvas',
            'embed',
            'figcaption',
            'figure',
            'source',
            'time',
            'video'
        ];
    }

}