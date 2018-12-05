<?php

Folder::_('packTo', function(string $path, $bucket = false) {
    return (new Package($this->path))->packTo($path, $bucket);
});

Folder::_('packAs', function(string $name, $bucket = false) {
    return (new Package($this->path))->packAs($name, $bucket);
});

Folder::_('pack', function($bucket = false) {
    return (new Package($this->path))->pack($bucket);
});