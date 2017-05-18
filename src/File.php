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
     * Create a directory if it doesn't exist.
     *
     * @param  integer $permissions The permissions octal
     * @return boolean              Whether the directory exists or was created
     */
    public static function createDir($path, $permissions = 0777)
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
    public static function availableFilePath($dir)
    {
        do {
            $name = self::randomString();
            $path = self::path($dir, $name);
        } while (file_exists($path));

        return $path;
    }

    /**
     * Generate a random string using letters and numbers.
     *
     * @param  integer $length The length of the string
     * @return string
     */
    public static function randomString($length = 32)
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