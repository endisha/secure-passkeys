<?php

defined('ABSPATH') || exit;

$app_file = basename(__FILE__, '.php');

$scripts = ['vuejs', 'vue-router', 'vue-secure-passkeys-overview'];

$styles = ['jquery-ui', 'secure-passkeys-css'];

$shared_components = false;

require realpath(__DIR__ . '/app.php');
