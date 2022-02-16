<?php

File::_('pack', function(string $as = null) {
    $file = $this->path;
    $path = strtr($as ?? Path::F($file) . '.zip', '/', DS);
    // Always create a new package
    if (is_file($path)) {
        unlink($path);
    }
    if (is_file($file)) {
        $package = new Package($path);
        $package->put($file, basename($file));
        $this->value[1] = $path;
    } else {
        $this->value[1] = null;
    }
    return $this;
});