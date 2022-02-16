<?php

Folder::_('pack', function(string $as = null) {
    $folder = $this->path;
    $path = strtr($as ?? $folder . '.zip', '/', DS);
    // Always create a new package
    if (is_file($path)) {
        unlink($path);
    }
    if (is_dir($folder)) {
        $package = new Package($path);
        foreach ($this->get(1, true) as $k => $v) {
            $package->put($k, strtr($k, [
                $folder . DS => "",
                DS => '/'
            ]));
        }
        $this->value[1] = $path;
    } else {
        $this->value[1] = null;
    }
    return $this;
});