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
$incrementalExport = false;
$folderFlags = array('--no-zip', '--folder-only');

foreach ($args as $arg) {
    if (in_array($arg, $folderFlags, true)) {
        $folderOnly = true;
        continue;
    }

    if ('--incremental' === $arg) {
        $incrementalExport = true;
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

$lastSyncFile = null;
$incrementalStartTime = null;

if ($incrementalExport && !$folderOnly) {
    echo "[ERROR] --incremental requires --no-zip since incremental sync only works with folder exports.\n";
    exit(1);
}

$folderExportPath = null;
if ($folderOnly) {
    if (empty($tmpFolder)) {
        echo "[ERROR] --no-zip mode requires a writable temp folder argument.\n";
        exit(1);
    }
    $folderExportPath = trailingslashit($tmpFolder) . 'hugo-export-files';
    $lastSyncFile = trailingslashit($folderExportPath) . '.last_sync';
    if ($incrementalExport) {
        if (!is_dir($folderExportPath)) {
            echo "[ERROR] Incremental export requires an existing folder at $folderExportPath. Run a full sync first.\n";
            exit(1);
        }
        if (file_exists($lastSyncFile)) {
            $timestampValue = trim((string)file_get_contents($lastSyncFile));
            $parsed = strtotime($timestampValue);
            if (false === $parsed) {
                echo "[WARN] Unable to parse last sync timestamp in $lastSyncFile. Running full export.\n";
            } else {
                $minuteInSeconds = defined('MINUTE_IN_SECONDS') ? MINUTE_IN_SECONDS : 60;
                $incrementalStartTime = $parsed - (5 * $minuteInSeconds);
                $incrementalStartFormatted = gmdate('c', $incrementalStartTime);
                echo "[INFO] Incremental export enabled starting from $incrementalStartFormatted (includes 5 minute buffer).\n";
                $je->setIncrementalStartTime($incrementalStartFormatted);
            }
        } else {
            echo "[WARN] No last sync marker found at $lastSyncFile. Running full export.\n";
        }
    }
    $shouldCleanExportDir = !$incrementalExport;
    $logAction = $shouldCleanExportDir ? "Cleaning and writing" : "Using existing export folder";
    echo "[INFO] Folder-only export enabled. $logAction to $folderExportPath\n";
    $je->setCustomExportDir($folderExportPath, true, $shouldCleanExportDir);
    $je->skipZipCreation(true);
}

$je->export();

if ($folderExportPath) {
    echo "[INFO] Hugo files exported to $folderExportPath\n";
    $timestampForMarker = gmdate('c');
    $markerTarget = $lastSyncFile ?: trailingslashit($folderExportPath) . '.last_sync';
    if (false === file_put_contents($markerTarget, $timestampForMarker)) {
        echo "[WARN] Failed to update last sync marker at $markerTarget\n";
    } else {
        echo "[INFO] Updated last sync marker at $markerTarget\n";
    }
}
