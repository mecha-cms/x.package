<?php

class Packages extends Files {

    public function file(string $path): \ArrayAccess {
        return $this->package($path);
    }

    public function package(string $path) {
        return new Package($path);
    }

    // TODO
    public static function from(...$lot) {}

}