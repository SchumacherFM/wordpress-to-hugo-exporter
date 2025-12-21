<?php
/*
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * Usage:
 *
 *     $ php hugo-export-cli.php > my-hugo-files.zip
 *
 * Usage: (With configured tmp folder if the avaiable space in system /tmp is insufficent for migration)
 *
 *     $ php hugo-export-cli.php /mnt/tmp-folder
 *
 * Folder-only export (skips zip archive and writes to /mnt/tmp-folder/hugo-export-files)
 *
 *     $ php hugo-export-cli.php /mnt/tmp-folder --no-zip
 *
 * Must be run in the wordpress-to-hugo-exporter/ directory.
 *
 */

include_once "../../../wp-load.php";
include_once "../../../wp-admin/includes/file.php";
require_once "hugo-export.php";

$args = $argv;
array_shift($args);
$tmpFolder = null;
$folderOnly = false;
$folderFlags = array('--no-zip', '--folder-only');

foreach ($args as $arg) {
    if (in_array($arg, $folderFlags, true)) {
        $folderOnly = true;
        continue;
    }

    if (null === $tmpFolder) {
        $tmpFolder = $arg;
        continue;
    }
}

$je = new Hugo_Export();

if (isset($tmpFolder)) {
    if ('null' !== strtolower($tmpFolder) && is_dir($tmpFolder)) {
        echo "[INFO] Start to export data to configured folder $tmpFolder\n";
        $je->setTempDir($tmpFolder);
    } else {
        echo "[WARN] Passed TEMP folder $tmpFolder is not a valid folder. Remove the argument or create it as a folder before contiune\n";
        exit(1);
    }
    
} else {
    echo "[INFO] tmp folder not found, use default. You could invoke php hugo-export-cli.php with an extra argument as the temporary folder path if needful.\n";
}

$folderExportPath = null;
if ($folderOnly) {
    if (empty($tmpFolder)) {
        echo "[ERROR] --no-zip mode requires a writable temp folder argument.\n";
        exit(1);
    }
    $folderExportPath = trailingslashit($tmpFolder) . 'hugo-export-files';
    echo "[INFO] Folder-only export enabled. Cleaning and writing to $folderExportPath\n";
    $je->setCustomExportDir($folderExportPath, true, true);
    $je->skipZipCreation(true);
}

$je->export();

if ($folderExportPath) {
    echo "[INFO] Hugo files exported to $folderExportPath\n";
}
