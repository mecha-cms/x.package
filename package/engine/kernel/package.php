<?php

class Package extends Folder {

    protected function getRoot() {
        $root = true === $this->root ? $this->path : $this->root;
        return !empty($root) || '0' === $root ? strtr($root, '/', DS) . DS : "";
    }

    public $exist;
    public $path;
    public $root;
    public $value;

    public function __construct(string $path = null) {
        $this->value[0] = null;
        if ($path && is_string($path) && 0 === strpos($path, ROOT)) {
            $path = strtr($path, '/', DS);
            if (!is_file($path)) {
                if (!is_dir($d = dirname($path))) {
                    mkdir($d, 0775, true);
                }
                touch($path); // Create an empty package
            }
            $this->path = is_file($path) ? (realpath($path) ?: $path) : null;
        }
        $this->exist = !!$this->path;
    }

    public function comment(string $comment = null) {
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                if (!isset($comment)) {
                    $comment = $z->getArchiveComment();
                    return "" !== $comment ? $comment : null;
                }
                $z->setArchiveComment($comment);
            }
            $z->close();
        }
        return $this;
    }

    public function count() {
        $count = 0;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $count = $z->numFiles;
            }
            $z->close();
        }
        return $count;
    }

    public function extract(string $to = null, $files = null) {
        $this->value[1] = [];
        if ($this->exist && $path = $this->path) {
            $z = new \ZipArchive;
            if (true === $z->open($path)) {
                if (!is_dir($to = $to ?? dirname($path))) {
                    mkdir($to, 0775, true);
                }
                $z->extractTo($to, isset($files) ? (array) $files : null);
            }
            $z->close();
            $this->value[1] = array_keys(y(g($to, null, true)));
        }
        return $this;
    }

    public function get($x = null, $deep = 0) {
        $out = [];
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $root = $this->getRoot();
                for ($i = 0, $j = $z->numFiles; $i < $j; ++$i) {
                    $n = $z->getNameIndex($i);
                    if (
                        null === $x ||
                        1 === $x ||
                        is_string($x) && false !== strpos(',' . $x . ',', ',' . pathinfo($n, PATHINFO_EXTENSION) . ',')
                    ) {
                        if (true === $deep || $deep >= substr_count($n, '/')) {
                            $out[$root . strtr($n, '/', DS)] = 1;
                        }
                    }
                    if (false !== strpos($n, '/') && (null === $x || 0 === $x)) {
                        if (true === $deep || $deep >= substr_count($n, '/') - 1) {
                            $out[$root . strtr(dirname($n), '/', DS)] = 0;
                        }
                    }
                }
            }
            $z->close();
        }
        ksort($out);
        return $out;
    }

    public function getIterator() {
        return $this->stream(null, true, true);
    }

    public function jsonSerialize() {
        $out = [$this->path => 1];
        $root = $this->getRoot();
        foreach ($this->stream(null, true) as $k => $v) {
            $out[$root . $k] = $v;
        }
        return $out;
    }

    public function let($any = true) {
        $out = [];
        if ($this->exist) {
            $path = $this->path;
            if (true === $any) {
                $out[$path] = unlink($path) ? 1 : null;
            } else {
                $z = new \ZipArchive;
                if (true === $z->open($path)) {
                    $root = $this->getRoot();
                    foreach ((array) $any as $v) {
                        $v = strtr($v, '/', DS);
                        $n = strtr($v, DS, '/');
                        $out[$root . $v] = false !== $z->locateName($n) ? 1 : 0;
                        if (!$z->deleteName($n) && !$z->deleteName($n . '/')) {
                            $out[$root . $v] = null;
                        }
                    }
                }
                $z->close();
            }
        }
        $this->value[1] = $out;
        return $this;
    }

    public function name($x = false) {
        if ($this->exist && $path = $this->path) {
            if (true === $x) {
                return basename($path);
            }
            return pathinfo($path, PATHINFO_FILENAME) . (is_string($x) ? '.' . $x : "");
        }
        return null;
    }

    public function offsetExists($i) {
        $out = false;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $out = false !== $z->locateName(strtr($i, DS, '/'));
            }
            $z->close();
        }
        return $out;
    }

    public function offsetGet($i) {
        $out = null;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $out = $z->getFromName(strtr($i, DS, '/'));
            }
            $z->close();
        }
        return $out;
    }

    public function offsetSet($i, $value) {
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                if ([] === $value) {
                    $z->addEmptyDir(strtr($i, DS, '/'));
                } else {
                    $z->addFromString(strtr($i, DS, '/'), (string) $value);
                }
            }
            $z->close();
        }
    }

    public function offsetUnset($i) {
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $z->deleteName(strtr($i, DS, '/'));
                $z->deleteName(strtr($i, DS, '/') . '/');
            }
            $z->close();
        }
    }

    public function paste(string $from, string $to) {
        $out = [null];
        if ($this->exist && $path = $this->path) {
            $z = new \ZipArchive;
            if (true === $z->open($path)) {
                if (is_file($from)) {
                    $out[0] = $from;
                    $z->addFile($from, strtr($to, DS, '/'));
                }
                $out[1] = $this->getRoot() . strtr($to, '/', DS);
            }
            $z->close();
        }
        $this->value[1] = $out;
        return $this;
    }

    public function save($seal = null) {
        $out = false; // Return `false` if `$this` is just a placeholder
        if ($path = $this->path) {
            if (isset($seal)) {
                $this->seal($seal);
            }
            // Return `$path` on success
            $out = $path;
        }
        $this->value[1] = $out;
        return $this;
    }

    public function set(string $path, $value) {
        return $this->offsetSet($path, $value);
    }

    public function size(string $unit = null, int $r = 2) {
        if (null !== ($size = $this->_size())) {
            return File::sizer($size, $unit, $r);
        }
        return null;
    }

    public function stream($x = null, $deep = 0, $content = false): \Generator {
        if ($content) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                foreach ($this->get($x, $deep) as $k => $v) {
                    yield $k => 0 === $v ? [] : $z->getFromName(strtr($k, DS, '/'));
                }
            }
            $z->close();
        } else {
            yield from $this->get($x, $deep);
        }
    }

    public function type() {
        return $this->exist ? mime_content_type($this->path) : null;
    }

    public function x() {
        return $this->exist ? pathinfo($this->path, PATHINFO_EXTENSION) : null;
    }

}