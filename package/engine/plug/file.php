<?php

File::_('packTo', function(string $path, $bucket = false) {
    return Package::from($this->path)->packTo($path, $bucket);
});

File::_('packAs', function(string $name, $bucket = false) {
    return Package::from($this->path)->packAs($name, $bucket);
});

File::_('pack', function($bucket = false) {
    return Package::from($this->path)->pack($bucket);
});