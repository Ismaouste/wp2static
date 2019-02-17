<?php

chdir( dirname(__FILE__) . '/../../plugin' );

$plugin_dir = getcwd();

require_once $plugin_dir . '/WP2Static/WP2Static.php';
require_once $plugin_dir . '/WP2Static/HTMLProcessor.php';
require_once $plugin_dir . '/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorNormalizeURLTest extends TestCase {

    /**
     * Test data provider
     * @dataProvider anchorTagProvider
     */
    public function testNormalizePartialURLInAnchor(
        $node_html,
        $tag_name,
        $attr,
        $exp_result
        ) {
        $html_doc = new DOMDocument();
        $html_header = '<!DOCTYPE html><html lang="en-US" class="no-js no-svg"><body>';
        $html_footer = '</body></html>';
        $html_doc->loadHTML( $html_header . $node_html . $html_footer);
        $links = $html_doc->getElementsByTagName( $tag_name );
        $element = $links[0];

        // mock out only the unrelated methods
        $processor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isInternalLink',
                    'loadSettings',
                ]
            )
            ->getMock();

        $processor->method( 'loadSettings' )->willReturn( null );

        $processor->settings = array();

        $processor->page_url = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );

        $processor->method( 'isInternalLink' )->willReturn( true );
        $processor->expects( $this->once() )->method( 'isInternalLink' );
        $processor->normalizeURL( $element, $attr );

        $this->assertEquals(
            $exp_result,
            $element->ownerDocument->saveHTML( $element )
        );
    }

    public function anchorTagProvider() {
        return [
           'anchor tag with relative href' =>  [
                '<a href="/first_lvl_dir/a_file.jpg">Link to some file</a>',
                'a',
                'href',
                '<a href="http://mywpsite.com/first_lvl_dir/a_file.jpg">Link to some file</a>'
            ],
           'img tag with relative src' =>  [
                '<img src="/first_lvl_dir/a_file.jpg" />',
                'img',
                'src',
                '<img src="http://mywpsite.com/first_lvl_dir/a_file.jpg">'
            ],
           'script tag with relative src and malformed tag' =>  [
                '<script src="/some.js" />',
                'script',
                'src',
                '<script src="http://mywpsite.com/some.js"></script>'
            ],
           'link tag with href to file at same hierachy' =>  [
                '<link rel="stylesheet" type="text/css" href="theme.css">',
                'link',
                'href',
                '<link rel="stylesheet" type="text/css" href="http://mywpsite.com/category/photos/my-gallery/theme.css">'
            ],
           'link tag with href to site root' =>  [
                '<link rel="stylesheet" type="text/css" href="/">',
                'link',
                'href',
                '<link rel="stylesheet" type="text/css" href="http://mywpsite.com/">'
            ],
        ];
    }
}
