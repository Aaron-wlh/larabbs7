<?php

use \Illuminate\Support\Facades\Route;

function route_class() {
    return str_replace('.', '-', Route::currentRouteName());
}

function category_nav_active($route_name, $url_param) {
    if (\Route::currentRouteName() == $route_name
        && (\Route::current()->originalParameters())['category'] == $url_param
    ) {
        return 'active';
    }
}

function make_excerpt($body, $length = 200) {
    $excerpt = trim(preg_replace('/\r\n|\r|\n+/', '', strip_tags($body)));
    $excerpt = \Illuminate\Support\Str::limit($excerpt, $length);
    return $excerpt;
}