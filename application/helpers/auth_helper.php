<?php

if (!function_exists('create_token')) {

    function create_token() {
        return substr("abcdefghijklmnopqrstuvwxyzADEFGHIJKLMNOPQRSTUVWXYZ", mt_rand(0, 50), 1) . substr(md5(time()), 1);
    }
}