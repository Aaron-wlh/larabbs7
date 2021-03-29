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