<?php
/*
Plugin Name: Error Generator
Description: Plugin for generating random errors for testing purposes.
Version: 1.0
Author: Szymon
*/

    $random_number = rand(1, 10);

if (is_admin() || is_login() || $random_number % 2) {
    return;
}

if ($random_number <= 5) {
    trigger_error("This is a CRITICAL error in plugin!", E_USER_ERROR);
}
