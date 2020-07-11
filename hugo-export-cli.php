<?php
/*
 * Run the exporter from the command line and print the location of the zip file.
 *
 * Usage:
 *
 *     $ php hugo-export-cli.php
 *
 * Must be run in the wordpress-to-hugo-exporter/ directory.
 *
 * If you have multiple hostnames, call it like SERVER_NAME=example.com php hugo-export-cli.php
 */

include "../../../wp-load.php";
include "../../../wp-admin/includes/file.php";
require_once "hugo-export.php";

$je = new Hugo_Export();
if (isset($argv[1]) && 'null' !== strtolower($argv[1]) && is_dir($argv[1])) {
    $je->setTempDir($argv[1]);
}
$je->export();
