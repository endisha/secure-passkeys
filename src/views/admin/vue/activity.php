<?php

defined('ABSPATH') || exit;

$app_file = basename(__FILE__, '.php');

$scripts = ['jquery-ui-datepicker', 'jquery-ui-autocomplete', 'vuejs', 'vue-router', 'vue-pagination', 'vue-secure-passkeys-activity'];

$styles = ['jquery-ui', 'secure-passkeys-css'];

$shared_components = true;

require realpath(__DIR__ . '/app.php');
