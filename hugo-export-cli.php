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
 * Must be run in the wordpress-to-hugo-exporter/ directory.
 *
 */

include_once "../../../wp-load.php";
include_once "../../../wp-admin/includes/file.php";
require_once "hugo-export.php";

$args = $argv;
$incrementalFlags = array('--incremental', '-i', 'incremental');
$tmpFolder = null;
$incrementalRequested = false;

array_shift($args); // remove script name
foreach ($args as $arg) {
    $lower = strtolower($arg);
    if (in_array($lower, $incrementalFlags, true)) {
        $incrementalRequested = true;
        continue;
    }
    if (null === $tmpFolder) {
        $tmpFolder = $arg;
    }
}

$je = new Hugo_Export();

if (isset($tmpFolder)) {
    if ('null' !== strtolower($tmpFolder) && is_dir($tmpFolder)) {
        echo "[INFO] Start to export data to configured folder $tmpFolder";
        $je->setTempDir($tmpFolder);
    } else {
        echo "[WARN] Passed TEMP folder $tmpFolder is not a valid folder. Remove the argument or create it as a folder before contiune";
        exit(1);
    }
    
} else {
    echo "[INFO] tmp folder not found, use default. You could invoke php hugo-export-cli.php with an extra argument as the temporary folder path if needful.";
}

if (true === $incrementalRequested) {
    echo "[INFO] Incremental export requested.\n";
    $je->enableIncrementalMode();
}

$je->export();
