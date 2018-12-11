<?php

Folder::_('packTo', function(string $path, $bucket = false) {
    return Package::from($this->path)->packTo($path, $bucket);
});

Folder::_('packAs', function(string $name, $bucket = false) {
    return Package::from($this->path)->packAs($name, $bucket);
});

Folder::_('pack', function($bucket = false) {
    return Package::from($this->path)->pack($bucket);
});