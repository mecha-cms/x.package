<?php

if (!extension_loaded('zip')) {
    if (defined('DEBUG') && DEBUG) {
        Guard::abort('<a href="http://www.php.net/manual/en/book.zip.php" title="PHP &#x2013; Zip" rel="nofollow" target="_blank">PHP Zip</a> extension is not installed on your web server.');
    }
} else {
    // Require the plug manually…
    require __DIR__ . DS . 'engine' . DS . 'plug' . DS . 'file.php';
    require __DIR__ . DS . 'engine' . DS . 'plug' . DS . 'folder.php';
}