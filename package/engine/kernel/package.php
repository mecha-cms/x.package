<?php

class Package extends Genome {

    public $source = null;
    public $zip = null;

    // Cache!
    private static $explore = [];

    public function __construct($path = null) {
        $this->zip = new \ZipArchive;
        $this->source = $path;
    }

    public function packTo(string $path, $bucket = false) {
        $source = $this->source;
        $path = strtr($path, '/', DS);
        $zip = $this->zip;
        if (file_exists($path)) {
            $mode = \ZipArchive::OVERWRITE;
        } else {
            // <http://php.net/manual/en/ziparchive.close.php#104051>
            Folder::create(dirname($path));
            $mode = \ZipArchive::CREATE;
        }
        $status = $zip->open($path, $mode);
        if ($status !== true) {
            Guardian::abort($zip->getStatusString());
        }
        if ($bucket) {
            $bucket = strtr($bucket === true ? Path::N($path) : (string) $bucket, '/', DS) . DS;
        } else {
            $bucket = "";
        }
        if (is_array($source)) {
            foreach ($source as $k => $v) {
                if (file_exists($k)) {
                    if (is_dir($k)) {
                        continue;
                    }
                    $zip->addFile($k, $bucket . $v);
                }
            }
        } else if (is_dir($source)) {
            $r = $source . DS;
            foreach (File::explore($source, true) as $k => $v) {
                if ($v === 0) {
                    $zip->addEmptyDir($name = rtrim(str_replace($r, "", $bucket . $k . DS), DS));
                } else {
                    $zip->addFile($k, $name = str_replace($r, "", $bucket . $k));
                }
                $zip->setCompressionName($name, \ZipArchive::CM_DEFAULT);
            }
        } else if (is_file($source)) {
            $zip->addFile($source, $bucket . basename($source));
        }
        $zip->close();
        return self::explore($path, true);
    }

    public function packAs(string $name, $bucket = false) {
        $path = $this->source;
        if (is_array($path)) {
            end($path);
            $path = key($path);
        }
        return $this->packTo(dirname($path) . DS . $name, $bucket);
    }

    public function pack($bucket = false) {
        $path = $this->source;
        if (is_array($path)) {
            end($path);
            $path = key($path);
        }
        return $this->packAs(Path::N($path) . '.zip', $bucket);
    }

    public function extractTo(string $path) {
        $source = strtr($this->source, '/', DS);
        $zip = $this->zip;
        $status = $zip->open($source);
        if ($status !== true) {
            Guardian::abort($zip->getStatusString());
        }
        if (!is_dir($path)) {
            Folder::create($path);
        }
        $zip->extractTo($path);
        $zip->close();
        return self::explore($source, true);
    }

    public function extractAs($bucket = false) {
        if ($bucket) {
            $bucket = DS . strtr($bucket === true ? Path::N($this->source) : $bucket, '/', DS);
        } else {
            $bucket = "";
        }
        return $this->extractTo(dirname($this->source) . $bucket);
    }

    public function extract() {
        return $this->extractAs(false);
    }

    public function delete($files = null) {
        self::$explore = []; // Reset cache
        $path = $this->source;
        if (!isset($files)) {
            File::open($path)->delete();
        } else {
            $zip = $this->zip;
            if ($zip->open($path) === true) {
                foreach ((array) $files as $file) {
                    $file = strtr($file, DS, '/');
                    if ($zip->locateName($file) !== false) {
                        $zip->deleteName($file);
                    } else {
                        // TODO: Remove folder and its content(s)
                        $explore = self::explore($path, true);
                        krsort($explore);
                        foreach ($explore as $k => $v) {
                            $k = strtr($k, DS, '/');
                            if (strpos($k, $file . '/') === 0) {
                                $zip->deleteName($k);
                            } else if ($k === $file) {
                                $zip->deleteName($k . '/');
                            }
                        }
                        $zip->deleteName($file . '/');
                    }
                }
                $zip->close();
            }
        }
        return self::explore($this->source, true);
    }

    // Alias for `delete`
    public function reset($files = null) {
        return $this->delete($files);
    }

    public function set($path, string $source = null) {
        $zip = $this->zip;
        if ($zip->open($this->source) === true) {
            self::$explore = []; // Reset cache
            if (is_string($path)) {
                $zip->addFile($source, $path);
            } else {
                foreach ((array) $path as $k => $v) {
                    $zip->addFile($v, $k);
                }
            }
            $zip->close();
        }
        return $this;
    }

    public function put($binary, string $path = null) {
        $zip = $this->zip;
        if ($zip->open($this->source) === true) {
            self::$explore = []; // Reset cache
            if (is_string($binary)) {
                $zip->addFromString($path, $binary);
            } else {
                foreach ((array) $binary as $k => $v) {
                    $zip->addFromString($k, $v);
                }
            }
            $zip->close();
        }
        return $this;
    }

    // TODO
    public function get() {}
    public function read() {}

    // To be packed
    public static function from($files) {
        return new static($files);
    }

    // To be extracted
    public static function open(string $package) {
        return new static($package);
    }

    public static function explore($archive, $deep = false, $fail = []) {
        $id = json_encode(func_get_args());
        if (isset(self::$explore[$id])) {
            $out = self::$explore[$id];
            return !empty($out) ? $out : $fail;
        }
        $x = null;
        if (is_array($archive)) {
            $x = $archive[1] ?? null;
            $archive = $archive[0];
        }
        $archive = strtr($archive, '/', DS);
        if (!file_exists($archive)) {
            return $fail;
        }
        $out = [];
        $zip = new \ZipArchive;
        if ($zip->open($archive) === true) {
            $folders = [];
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = strtr($zip->statIndex($i)['name'], '/', DS);
                $n = rtrim($name, DS);
                $d = substr($name, -1) === DS;
                if (strpos($n, DS) !== false) {
                    $folders[explode(DS, $n)[0]] = 0;
                }
                if (!$deep && strpos($n, DS) !== false) {
                    continue;
                }
                if ($x === 0) {
                    if ($d) {
                        $out[$n] = 0;
                    }
                } else if ($x === 1) {
                    if (!$d) {
                        $out[$n] = 1;
                    }
                } else if (is_string($x)) {
                    if (!$d && strpos(',' . $x . ',', ',' . strtolower(pathinfo($n, PATHINFO_EXTENSION)) . ',') !== false) {
                        $out[$n] = 1;
                    }
                } else {
                    $out[$n] = $d ? 0 : 1;
                }
            }
            if ($folders && ($x === 0 || $x === null)) {
                // TODO: Set proper folder order in the result list
                $out = concat($out, $folders);
            }
            $zip->close();
        }
        self::$explore[$id] = $out;
        return !empty($out) ? $out : $fail;
    }

    public function comment() {
        $zip = $this->zip;
        if ($zip->open($this->source) === true) {
            return (string) $zip->comment;
        }
        return null;
    }

    public function files(): array {
        $zip = $this->zip;
        $files = [];
        if ($zip->open($this->source) === true) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                if ($stat = $zip->statIndex($i)) {
                    $path = strtr($stat['name'], '/', DS);
                    $files[rtrim($path, DS)] = [
                        0 => substr($path, -1) === DS, // Is folder
                        1 => substr($path, -1) !== DS, // Is file
                        'size' => File::sizer($stat['comp_size']),
                        '_size' => $stat['comp_size']
                    ];
                }
            }
        }
        return $files;
    }

    public function status(): int {
        $zip = $this->zip;
        if ($zip->open($this->source) === true) {
            return $zip->status;
        }
        return -1;
    }

    /* TODO
    public static function inspect(string $path, $key = null, $fail = false) {
        $id = json_encode(func_get_args());
        $out = File::inspect($path);
        $out['package'] = [];
        $zip = new \ZipArchive;
        if ($zip->open($path) === true) {
            $out['status'] = $zip->status;
            $out['comment'] = $zip->comment;
        }
        self::$inspect[$id] = $out;
        return isset($key) ? Anemon::get($out, $key, $fail) : $out;
    }
    */

}