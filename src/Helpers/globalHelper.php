<?php

if(!function_exists('page_last_name')) {
    function page_last_name() {
        return last(explode('.', request()->route()->getName()));
    }
}

if(!function_exists('page_is_list')) {
    function page_is_list() {
       return page_last_name() === 'list';
    }
}

if(!function_exists('page_is_form')) {
    function page_is_form() {
       return page_last_name() === 'form';
    }
}

if(!function_exists('page_is_detail')) {
    function page_is_detail() {
       return page_last_name() === 'detail';
    }
}

if(!function_exists('array_transpose')) {
    function array_transpose(array $array, \Closure $func = null) {
       return array_map(fn(...$arr) => $func ? $func($arr) : $arr, ...$array);
    }
}
