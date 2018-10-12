<?php

class Package extends Genome {

    protected $z = null;
    protected $path = null;

    public function __construct($file, $fail = false) {
        if (!extension_loaded('zip')) {
            exit('<a href="http://www.php.net/manual/en/book.zip.php" title="PHP &ndash; Zip" rel="nofollow" target="_blank">PHP Zip</a> extension is not installed on your web server.');
        }
        $this->z = new ZipArchive;
        $this->path = $file;
        return $this->z->open($file) === true ? $this : $fail;
    }

    public static function inspect($package, $key = null, $fail = false) {
        if (!extension_loaded('zip')) {
            exit('<a href="http://www.php.net/manual/en/book.zip.php" title="PHP &ndash; Zip" rel="nofollow" target="_blank">PHP Zip</a> extension is not installed on your web server.');
        }
        $output = [];
        $z = new ZipArchive;
        if ($z->open($package) === true) {
            $output = extend(File::inspect($package), ['package' => [
                'status' => $package->zip->status,
                'i' => $this->zip->numFiles
            ]]);
            for ($i = 0; $i < $output['package']['i']; ++$i) {
                $j = $this->zip->statIndex($i);
                $j['name'] = str_replace(['/', DS . DS], DS, $d['name']);
                $output['package']['contain'][$i] = $d;
            }
            $z->close();
        }
        if (isset($key)) {
            if (strpos($key, '.') > 0) {
                return Anemon::get($output, $key, $fail);
            }
            return array_key_exists($key, $output) ? $output[$key] : $fail;
        }
        return !empty($output) ? $output : $fail;
    }

    public static function extract($file, $to = null, $fail = false) {
        $zip = new static($file);
        if ($zip !== false) {
            $zip->extractTo($to ?: dirname($file));
            $zip->close();
        }
        return $to;
    }

    public function pack($as = 'package-%{id}%.zip', $fail = false) {
        if (strpos($as, ROOT) !== 0) {
            $as = ROOT . DS . $as;
        }
        $as = candy($as, ['id' => time()]);
        // Delete the old package…
        File::open($as)->delete();
        if (!$this->z->open($as, ZipArchive::CREATE)) {
            return $fail;
        }
        if (is_array($this->path)) {
            foreach ($this->path as $k => $v) {
                if (strpos(ROOT, $k) !== 0) {
                    $k = ROOT . DS . $k;
                }
                if (strpos(ROOT, $v) !== 0) {
                    $v = ROOT . DS . $v;
                }
                if (file_exists($k)) {
                    $this->z->addFile($k, $v);
                }
            }
        } else if (is_dir($this->path)) {
            $a = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)
            $b = new \RecursiveIteratorIterator($a, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($b as $v) {
                if (is_dir($v)) {
                    $this->z->addEmptyDir(str_replace($this->path . DS, "", $v . DS));
                } else if (is_file($o)) {
                    $this->z->addFromString(str_replace($this->path . DS, "", $v), file_get_contents($v));
                }
            }
        } else if (is_file($this->path)) {
            $this->z->addFromString($this->path, file_get_contents($this->path));
        }
        $this->z->close();
        return $as;
    }

    public function set() {}
    public function get() {}
    public function reset() {}

}