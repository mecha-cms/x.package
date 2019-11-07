<?php

if (!extension_loaded('zip')) {
    if (defined('DEBUG') && DEBUG) {
        Guard::abort(i('Missing %s extension.', 'PHP <code>zip</code>'));
    }
} else {
    require __DIR__ . DS . 'engine' . DS . 'plug' . DS . 'file.php';
    require __DIR__ . DS . 'engine' . DS . 'plug' . DS . 'folder.php';
}