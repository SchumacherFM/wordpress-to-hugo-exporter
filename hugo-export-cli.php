<?php
/*
 * Run the exporter from the command line and spit the zipfile to STDOUT.
 *
 * Usage:
 *
 *     $ php jekyll-export-cli.php > my-jekyll-files.zip
 *
 * Must be run in the wordpress-to-jekyll-exporter/ directory.
 *
 */

require "../../wp-load.php";
require "../../wp-admin/includes/file.php";
require_once "hugo-export.php";

$je = new Jekyll_Export();
$je->export();
