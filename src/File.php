<?php

/**
 * Methods for the local filesystem.
 *
 * @author Chris Ullyott <chris@monkdevelopment.com>
 */
class File
{
    /**
     * Build a full path from parts passed as arguments.
     *
     * @return string
     */
    public static function path()
    {
        $parts = func_get_args();

        $s = DIRECTORY_SEPARATOR;

        $path = rtrim(array_shift($parts), $s) . $s;

        foreach ($parts as $p) {
            $path .= trim($p, $s) . $s;
        }

        return rtrim($path, $s);
    }

    /**
     * Read a file, return null if unreadable.
     *
     * @param  string $path The path to the file
     * @return mixed
     */
    public static function read($path)
    {
        if (is_readable($path)) {
            return file_get_contents($path);
        }

        return null;
    }

    /**
     * Write a string to a file, return boolean for success.
     *
     * @param  string  $path     The path to the file
     * @param  mixed   $contents The contents for the file
     * @param  integer $flags    Any flags available to file_put_contents()
     * @return boolean           Whether the file was written
     */
    public static function write($path, $contents, $flags = null)
    {
        return file_put_contents($path, $contents, $flags) !== false;
    }

    /**
     * Write a string to a file and use locking.
     *
     * @param  string  $path     The path to the file
     * @param  mixed   $contents The contents for the file
     * @param  string  $mode     The write mode to use
     * @return boolean           Whether the file is closed
     */
    public static function writeWithLock($path, $contents, $mode = 'w')
    {
        $handle = fopen($path, $mode);

        if (flock($handle, LOCK_EX)) {
            fwrite($handle, $contents);
            fflush($handle);
            flock($handle, LOCK_UN);
        }

        return fclose($handle);
    }

    /**
     * Create a directory if it doesn't exist.
     *
     * @param  integer $permissions The permissions octal
     * @return boolean              Whether the directory exists or was created
     */
    public static function createDir($path, $permissions = 0755)
    {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }

        return true;
    }

    /**
     * List all the files in a directory.
     *
     * @param  string  $dir       The path of a directory
     * @param  boolean $recursive Whether to list recursively
     * @return array              The listed files
     */
    public static function listDir($dir, $recursive = false)
    {
        $files = array();

        $glob = glob(self::path($dir, '*'));

        foreach ($glob as $path) {
            if ($recursive && is_dir($path)) {
                $files = array_merge($files, self::listDir($path, $recursive));
            } elseif (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Delete a directory.
     *
     * @param  string  $dir The path of a directory
     * @return boolean
     */
    public static function deleteDir($dir)
    {
        $files = self::listDir($dir);

        foreach ($files as $file) {
            unlink($file);
        }

        return rmdir($dir);
    }

    /**
     * Format byte count.
     *
     * @param  integer $bytes     The byte count
     * @param  integer $precision The number of decimal places to output
     * @return string
     */
    public static function formatBytes($bytes, $precision = 0)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Generate a random filename which isn't already taken in a directory.
     *
     * @param  string $dir The directory path
     * @return string
     */
    public static function availablePath($dir)
    {
        do {
            $name = self::randomString();
            $path = self::path($dir, $name);
        } while (file_exists($path));

        return $path;
    }

    /**
     * Find the next available file path (via sequence number) among those in its
     * current directory.
     *
     * @param  string $path The attempted file path
     * @return string
     */
    public static function sequencedPath($path)
    {
        while (file_exists($path)) {
            $path = self::incrementSequenceNumber($path);
        }

        return $path;
    }

    /**
     * Increment the sequence number of a filename in a path. Example:
     *
     * my_file.pdf     => my_file (1).pdf
     * my_file (1).pdf => my_file (2).pdf
     *
     * @param  string $path The file path
     * @return string
     */
    private static function incrementSequenceNumber($path)
    {
        $i = pathinfo($path);

        $filename = preg_replace_callback('/\((\d{1,})\)$/', function($m) {
            return '(' . ($m[1] + 1) . ')';
        }, $i['filename']);

        if ($filename === $i['filename']) {
            $filename .= " (1)";
        }

        return self::updateFilename($path, $filename);
    }


    /**
     * Update the filename in a path, preserving the extension.
     *
     * @param  string $path     The file path
     * @param  string $filename The new filename (without extension)
     * @return string
     */
    private static function updateFilename($path, $filename)
    {
        $p = '';
        $i = pathinfo($path);

        if (!empty($i['dirname']) && $i['dirname'] !== '.') {
            $p = $i['dirname'] . DIRECTORY_SEPARATOR;
        }

        $p .= $filename;

        if (!empty($i['extension'])) {
            $p .= ".{$i['extension']}";
        }

        return $p;
    }

    /**
     * Generate a random string using letters and numbers.
     *
     * @param  integer $length The length of the string
     * @return string
     */
    private static function randomString($length = 32)
    {
        $string = '';

        $characters = array_merge(
            range('A', 'Z'),
            range('a', 'z'),
            range(0, 9)
        );

        for ($i = 0; $i < $length; $i++) {
            $key = array_rand($characters);
            $string .= $characters[$key];
        }

        return $string;
    }

}
