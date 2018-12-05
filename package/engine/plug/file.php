<?php

File::_('packTo', function(string $path, $bucket = false) {
    return (new Package($this->path))->packTo($path, $bucket);
});

File::_('packAs', function(string $name, $bucket = false) {
    return (new Package($this->path))->packAs($name, $bucket);
});

File::_('pack', function($bucket = false) {
    return (new Package($this->path))->pack($bucket);
});