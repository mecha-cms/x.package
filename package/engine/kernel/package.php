<?php

class Package extends File {

    protected function _root() {
        $root = true === $this->root ? $this->path : $this->root;
        return !empty($root) || '0' === $root ? strtr($root, '/', DS) . DS : "";
    }

    public $exist;
    public $path;
    public $root;
    public $value;

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

    public function content(string $path) {
        $out = null;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $out = $z->getFromName(strtr($path, DS, '/'));
            }
            $z->close();
        }
        return $out;
    }

    public function count() {
        $count = 0;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                // Count file(s) only
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
            $this->value[1] = y(g($to, null, true));
        }
        return $this;
    }

    public function get(...$lot) {
        $out = [];
        $x = $lot[0] ?? null;
        $deep = $lot[1] ?? 0;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $root = $this->_root();
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

    public function has(string $path) {
        $out = false;
        if ($this->exist) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $out = false !== $z->locateName(strtr($path, DS, '/'));
            }
            $z->close();
        }
        return $out;
    }

    public function jsonSerialize() {
        $out = [$this->path => 1];
        $root = $this->_root();
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
                    $root = $this->_root();
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

    public function paste(string $from, string $to) {
        $out = [null];
        if ($this->exist && $path = $this->path) {
            $z = new \ZipArchive;
            if (true === $z->open($path)) {
                if (is_file($from)) {
                    $out[0] = $from;
                    $z->addFile($from, strtr($to, DS, '/'));
                }
                $out[1] = $this->_root() . strtr($to, '/', DS);
            }
            $z->close();
        }
        $this->value[1] = $out;
        return $this;
    }

    public function set(...$lot) {
        if ($this->exist) {
            $path = $lot[0] ?? null;
            $value = $lot[1] ?? null;
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                $path = strtr($path, DS, '/');
                if ([] === $value) {
                    $z->addEmptyDir($path);
                } else {
                    $z->addFromString($path, (string) $value);
                }
            }
            $z->close();
        }
    }

    public function stream($x = null, $deep = 0, $content = false): \Generator {
        if ($content) {
            $z = new \ZipArchive;
            if (true === $z->open($this->path)) {
                foreach ($this->get($x, $deep) as $k => $v) {
                    yield $k => 0 === $v ? [] : $z->getFromName(strtr($k, DS, '/'));
                }
                $z->close();
            } else {
                yield from [];
            }
        } else {
            yield from $this->get($x, $deep);
        }
    }

}