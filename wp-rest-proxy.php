<?php
/*
Plugin Name: WP REST Proxy
Plugin URI: https://github.com/mnelson4/wp-rest-proxy
Description: Allows your site's REST API endpoints to act as proxy for another site.
Author: Michael Nelson
Version: 1.0.0
Requires at least: 4.4
Requires PHP: 5.4
Author URI: https://cmljnelson.wordpress.com
*/

// from https://secure.php.net/manual/en/function.getallheaders.php#84262
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Intercepts REST API requests, checks for the query argument "proxy_for", and if present, forwards the request
 * to that site. If "proxy_for" is absent, leaves the request alone.
 * @since 1.0.0
 */
function rest_proxy_loaded()
{
    if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
        return;
    }
    // check for the proxy_for parameter
    $query_params = $_GET;
    unset($query_params['rest_route']);
    if ( empty($query_params['proxy_for'])) {
        return;
    }
    // if it's set, grab the rest route
    $rest_route = $GLOBALS['wp']->query_vars['rest_route'];
    // remove the version string
    $resource_part_of_route = str_replace('/wp/v2/', '', $rest_route);
    // send the rest of the request on.
    // duplicate using the same method
    $method = $_SERVER['REQUEST_METHOD'];
    // including headers
    $headers = array();//getallheaders();
    $proxy_for = $query_params['proxy_for'];
    unset($query_params['proxy_for']);
    // get the response
    $url = add_query_arg(
        $query_params,
        trailingslashit($proxy_for) . $resource_part_of_route
    );
    $response = wp_remote_request(
        $url,
        array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 20,
        )
    );
    // return that same response on
    if(is_wp_error($response)){
        echo wp_json_encode($response);
        exit;
    }
//    var_dump($response);
    $response_object = $response['http_response'];
    /* @var $response_object WP_HTTP_Requests_Response */
    foreach($response_object->get_headers() as $header => $values){
        if( in_array(
            $header,
            array('access-control-allow-headers','access-control-expose-headers','allow','x-wp-total','x-wp-totalpages','date','vary','x-content-type-options')
        )) {
            header($header . ': ' . $values, false);
        }
    }
    echo $response['body'];
    exit;
}

// just forward the request on, and return its exact response
add_action( 'parse_request', 'rest_proxy_loaded', 9);