<?php
/*
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * Usage:
 *
 *     $ php hugo-export-cli.php > my-hugo-files.zip
 *
 * Must be run in the wordpress-to-hugo-exporter/ directory.
 *
 */

include "../../../wp-load.php";
include "../../../wp-admin/includes/file.php";
require_once "hugo-export.php";

$je = new Hugo_Export();
if (isset($argv[1]) && 'null' !== strtolower($argv[1]) && is_dir($argv[1])) {
    $je->setTempDir($argv[1]);
}
$je->export();
