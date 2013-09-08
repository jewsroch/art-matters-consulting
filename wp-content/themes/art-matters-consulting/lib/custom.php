<?php
/**
 * Custom functions
 */
remove_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'wpautop' , 99);
add_filter( 'the_content', 'shortcode_unautop', 100);
/**
 * Custom shortcodes
 */
function row_shortcode( $atts , $content = null ) {

    // Attributes
    extract( shortcode_atts(
            array(
                'class' => '',
            ), $atts )
    );

    // Code
    $code = '';
    $code .= '<div class="row ' . esc_attr($class) . '">';
    $code .= do_shortcode($content);
    $code .= '</div>';
    return $code;
}
add_shortcode( 'row', 'row_shortcode' );


function shortcodeColumn($atts, $content = null)
{
    extract( shortcode_atts( array(
        'align'     => 'left',
        'size'      => '12',
        'offset'  => '0',
    ), $atts ) );

    if ($size <= 0) {
        $size = 1;
    }
    if ($size > 12) {
        $size = 12;
    }

    if ($pad_left > (12 - $size)) {
        $pad_left = (12 - $size);
    }

    $code  = "<div class='" . esc_attr("col-sm-{$size} ") . ($offset > 0 ? esc_attr("col-md-offset-{$offset} ") : "") . esc_attr("text-{$align}") . "'>\n";
    $code .= do_shortcode($content);
    $code .= "</div>\n";

    return $code;
}

add_shortcode('column', 'shortcodeColumn');

function line_shortcode( $atts ) {
    extract( shortcode_atts(
        array(
            'color' => 'orange',
        ), $atts )
    );

    return '<div class="full-width rounded ' . esc_attr($color) . '"></div>';
}
add_shortcode('line', 'line_shortcode');

function heading_shortcode( $atts, $content = null ) {
    extract( shortcode_atts(
            array(
                'size' => 'large',
            ), $atts )
    );

    $htype = '';
    switch ($size) {
        case 'large':
            $htype = 'h2';
            break;
        case 'medium':
            $htype = 'h3';
            break;
        case 'small':
            $htype = 'h4';
            break;
        case 'extra-small':
            $htype = 'h5';
            break;
    }
    return '<' . esc_attr($htype) . '>' . do_shortcode($content) . '</' . esc_attr($htype) . '>';
}
add_shortcode('heading', 'heading_shortcode');
