<?php

function generate_random_error() {
    $random_number = rand(1, 10);

    if ($random_number % 2) {
        return;
    }

    if ($random_number <= 5) {
        trigger_error('No error notice ;)', E_USER_NOTICE);
    } elseif ($random_number <= 7) {
        trigger_error("This is a warning.", E_USER_WARNING);
    } else {
        trigger_error("This is a CRITICAL error!", E_USER_ERROR);
    }
}
