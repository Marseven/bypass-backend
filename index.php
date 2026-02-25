<?php

/**
 * Hostinger shared hosting entry point.
 *
 * On shared hosting the DocumentRoot cannot point to public/,
 * so this file proxies every request into public/index.php.
 */

// Redirect all requests to public/index.php
require __DIR__.'/public/index.php';
