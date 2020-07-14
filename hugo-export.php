<?php

/*
Plugin Name: WordPress to Hugo Exporter
Description: Exports WordPress posts, pages, and options as YAML files parsable by Hugo
Version: 1.3
Author: Benjamin J. Balter
Author URI: http://ben.balter.com
License: GPLv3 or Later

Copyright 2012-2013  Benjamin J. Balter  (email : Ben@Balter.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Hugo_Export
{
    protected $_tempDir = null;
    private $posts_dir_name = 'post'; // name of posts dir, inside content dir

    /**
     * Export comments as part of your posts. Pingbacks won't get exported.
     *
     * @var bool
     */
    private $include_comments = true;

    // Dictionary of Wordpress options to Hugo config values
    public $rename_options = array(
        'siteurl' => 'baseUrl',
        'blogname' => 'title',
        'blogdescription' => 'description'
    );

    public $required_classes = array(
        'spyc' => '%pwd%/includes/spyc.php'
    );

    /**
     * Hook into WP Core
     */
    function __construct()
    {

        add_action('admin_menu', array(&$this, 'register_menu'));
        add_action('current_screen', array(&$this, 'callback'));
    }

    /**
     * Listens for page callback, intercepts and runs export
     */
    function callback()
    {

        if (get_current_screen()->id != 'export')
            return;

        if (!isset($_GET['type']) || $_GET['type'] != 'hugo')
            return;

        if (!current_user_can('manage_options'))
            return;

        $this->export();
        exit();
    }

    /**
     * Add menu option to tools list
     */
    function register_menu()
    {

        add_management_page(__('Export to Hugo', 'hugo-export'), __('Export to Hugo', 'hugo-export'), 'manage_options', 'export.php?type=hugo');
    }

    /**
     * Get an array of all post and page IDs
     * Note: We don't use core's get_posts as it doesn't scale as well on large sites
     */
    function get_posts()
    {

        global $wpdb;
        return $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status in ('publish', 'draft', 'private') AND post_type IN ('post', 'page' )");
    }

    protected function post_unix_time(WP_Post $post) {
        $use_date = $post->post_date_gmt;

        // Sometimes $post->post_date_gmt is 0000-00-00 00:00:00
        // for no apparent reason
        if ($use_date === '0000-00-00 00:00:00') {
            // When that's the case, sometimes post_date has a valid date, so at
            // least fall back to that, even if it's off by a few hours.
            $use_date = $post->post_date;
        }

        // Sometimes that one's invalid too!
        if ($use_date === '0000-00-00 00:00:00') {
            // So just don't return one
            return NULL;
        }
        return strtotime($use_date);
    }

    /**
     * Convert a posts meta data (both post_meta and the fields in wp_posts) to key value pairs for export
     */
    function convert_meta(WP_Post $post)
    {
        $output = array(
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES | ENT_XML1, 'UTF-8'),
            'author' => get_userdata($post->post_author)->display_name,
            'type' => get_post_type($post),
        );
        $timestamp = $this->post_unix_time($post);
        if (!is_null($timestamp)) {
            $output['date'] = date('c', $timestamp);
        }
        if (false === empty($post->post_excerpt)) {
            $output['excerpt'] = $post->post_excerpt;
        }

        if (in_array($post->post_status, array('draft', 'private'))) {
            // Mark private posts as drafts as well, so they don't get
            // inadvertently published.
            $output['draft'] = true;
        }
        if ($post->post_status == 'private') {
            // hugo doesn't have the concept 'private posts' - this is just to
            // disambiguate between private posts and drafts.
            $output['private'] = true;
        }

        //turns permalink into 'url' format, since Hugo supports redirection on per-post basis
        if ('page' !== $post->post_type) {
            $output['url'] = urldecode(str_replace(home_url(), '', get_permalink($post)));
        }

        // check if the post or page has a Featured Image assigned to it.
        if (has_post_thumbnail($post)) {
            $output['featured_image'] = str_replace(get_site_url(), "", get_the_post_thumbnail_url($post));
        }

        //convert traditional post_meta values, hide hidden values
        foreach (get_post_custom($post->ID) as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            if ($key === "openid_comments") {
                continue; // handled in convert_comments
            }
            if (false === $this->_isEmpty($value)) {
                $output[$key] = $value;
            }
        }
        return $output;
    }

    protected function _isEmpty($value)
    {
        if (true === is_array($value)) {
            if (true === empty($value)) {
                return true;
            }
            if (1 === count($value) && true === empty($value[0])) {
                return true;
            }
            return false;
//            $isEmpty=true;
//            foreach($value as $k=>$v){
//                if(true === empty($v)){
//                    $isEmpty
//                }
//            }
//            return $isEmpty;
        }
        return true === empty($value);
    }

    /**
     * Convert post taxonomies for export
     */
    function convert_terms($post)
    {
        $output = array();
        $tags = array(); // combined tags and categories (as 'tags')

        foreach (get_taxonomies(array('object_type' => array(get_post_type($post)))) as $tax) {

            $terms = wp_get_post_terms($post, $tax);

            if ($tax === 'category' || $tax == 'post_tag') {
                $tags = $tags + wp_list_pluck($terms, 'name');
            } else if ($tax == 'post_format') {
                $output['format'] = get_post_format($post);
            } else {
                $output[$tax] = wp_list_pluck($terms, 'name');
            }
        }

        $tags = array_values(array_filter($tags, function($v) {
            return $v !== '-no category-';
        }));

        if (!empty($tags)) {
            $output['tags'] = $tags;
        }

        return $output;
    }

    /**
     * Convert the main post content.
     */
    function convert_content($post)
    {
        return apply_filters('the_content', $post->post_content);
    }

    /**
     * Loop through and convert all comments for the specified post,
     * writing them out as separate files next the posts.
     */
    function convert_comments($post)
    {
        $args = array(
            'post_id' => $post->ID,
            'order' => 'ASC'   // oldest comments first
        );
        $comments = get_comments($args);
        if (empty($comments)) {
            return '';
        }

        $openid_comments = get_post_meta($post->ID, 'openid_comments', true);

        foreach ($comments as $comment) {
            $cid = $comment->comment_ID;
            $ctype = get_comment_type($cid);
            $meta = array(
                'id' => $cid,
                'type' => $ctype,
                'date' => date('c', strtotime($comment->comment_date_gmt)),
                // comment->user_id is apparently unreliable, so skip it here :-(
                'author' => $comment->comment_author,
                'authorUrl' => $comment->comment_author_url
            );
            if (in_array($cid, $openid_comments, true)) {
                $meta['openID'] = true;
            }

            $output = Spyc::YAMLDump($meta, false, 0);
            $output .= "\n---\n";
            $output .= $comment->comment_content;

            $filename = 'comment_' . $ctype . '_' . $cid . '.md';
            $this->write($output, $this->output_post_dir($post), $filename);
        }
    }

    /**
     * Loop through and convert all posts to MD files with YAML headers
     */
    function convert_posts()
    {
        global $post;

        foreach ($this->get_posts() as $postID) {
            $post = get_post($postID);
            setup_postdata($post);
            $meta = array_merge($this->convert_meta($post), $this->convert_terms($postID));
            // remove falsy values, which just add clutter
            foreach ($meta as $key => $value) {
                if (!is_numeric($value) && !$value) {
                    unset($meta[$key]);
                }
            }

            // Hugo doesn't like word-wrapped permalinks
            $output = Spyc::YAMLDump($meta, false, 0);
            $output .= "\n---\n";
            $output .= $this->convert_content($post);

            $this->write($output, $this->output_post_dir($post), 'index.md');

            if ($this->include_comments) {
                $this->convert_comments($post);
            }
        }
    }

    function filesystem_method_filter()
    {
        return 'direct';
    }

    /**
     *  Conditionally Include required classes
     */
    function require_classes()
    {

        foreach ($this->required_classes as $class => $path) {
            if (class_exists($class)) {
                continue;
            }
            $path = str_replace("%pwd%", dirname(__FILE__), $path);
            require_once($path);
        }
    }

    /**
     * Main function, bootstraps, converts, and cleans up
     */
    function export()
    {
        global $wp_filesystem;

        define('DOING_JEKYLL_EXPORT', true);

        $this->require_classes();

        add_filter('filesystem_method', array(&$this, 'filesystem_method_filter'));

        WP_Filesystem();

        $this->dir = $this->getTempDir() . 'wp-hugo-' . md5(time());
        $this->zip = $this->getTempDir() . 'wp-hugo.zip';
        $wp_filesystem->mkdir($this->dir) or die("Failed to create export dir");
        $wp_filesystem->mkdir("$this->dir/content") or die("Failed to create content dir");
        $wp_filesystem->mkdir("$this->dir/content/$this->posts_dir_name") or die("Failed to create posts folder");
        $wp_filesystem->mkdir("$this->dir/wp-content");

        $this->convert_options();
        $this->convert_posts();
        $this->convert_uploads();
        $this->zip();
        $this->send();
        $this->cleanup();
    }

    /**
     * Convert options table to config.yaml file
     */
    function convert_options()
    {
        global $wp_filesystem;

        $wp_options = wp_load_alloptions();
        $hugo_config = array();
        foreach ($this->rename_options as $from_key => $to_key) {
            $hugo_config[$to_key] = maybe_unserialize($wp_options[$from_key]);
        }

        $output = Spyc::YAMLDump($hugo_config);

        //strip starting "---"
        $output = substr($output, 4);

        $wp_filesystem->put_contents($this->dir . '/config.yaml', $output);
    }

    /**
     * Get the path for this post's output folder in the temp directory.
     */
    function output_post_dir($post)
    {
        $timestamp = $this->post_unix_time($post);
        $date = 'UNDATED';
        if (!is_null($timestamp)) {
            $date = date('Y-m-d', $timestamp);
        }
        $dirname = $date . '_' . urldecode($post->post_name);
        return "$this->dir/content/$this->posts_dir_name/$dirname";
    }

    /**
     * Write file to temp dir.
     */
    function write($output, $dir, $filename)
    {
        global $wp_filesystem;
        if (!$wp_filesystem->exists($dir))
            $wp_filesystem->mkdir($dir) or die("Failed to create post output dir: $dir");
        $wp_filesystem->put_contents($dir . '/' . $filename, $output);
    }

    /**
     * Zip temp dir
     */
    function zip()
    {

        //create zip
        $zip = new ZipArchive();
        $err = $zip->open($this->zip, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if ($err !== true) {
            die("Failed to create '$this->zip' err: $err");
        }
        $this->_zip($this->dir, $zip);
        $zip->close();
    }

    /**
     * Helper function to add a file to the zip
     */
    function _zip($dir, &$zip)
    {

        //loop through all files in directory
        foreach ((array)glob(trailingslashit($dir) . '*') as $path) {

            // periodically flush the zipfile to avoid OOM errors
            if ((($zip->numFiles + 1) % 250) == 0) {
                $filename = $zip->filename;
                $zip->close();
                $zip->open($filename);
            }

            if (is_dir($path)) {
                $this->_zip($path, $zip);
                continue;
            }

            //make path within zip relative to zip base, not server root
            $local_path = str_replace($this->dir . '/', '', $path);

            //add file
            $zip->addFile(realpath($path), $local_path);
        }
    }

    /**
     * Send headers and zip file to user
     */
    function send()
    {
        if ('cli' !== php_sapi_name()) {
            //send headers
            @header('Content-Type: application/zip');
            @header("Content-Disposition: attachment; filename=hugo-export.zip");
            @header('Content-Length: ' . filesize($this->zip));
        }

        //read file
        ob_clean();
        flush();
        readfile($this->zip);
    }

    /**
     * Clear temp files
     */
    function cleanup()
    {
        global $wp_filesystem;
        $wp_filesystem->delete($this->dir, true);
        $wp_filesystem->delete($this->zip);
    }

    /**
     * Rename an assoc. array's key without changing the order
     */
    function rename_key(&$array, $from, $to)
    {

        $keys = array_keys($array);
        $index = array_search($from, $keys);

        if ($index === false)
            return;

        $keys[$index] = $to;
        $array = array_combine($keys, $array);
    }

    function convert_uploads()
    {

        $upload_dir = wp_upload_dir();
        $this->copy_recursive($upload_dir['basedir'], $this->dir . '/' . str_replace(trailingslashit(get_home_url()), '', $upload_dir['baseurl']));
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     *
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.0.1
     * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
     *
     * @param       string $source Source path
     * @param       string $dest Destination path
     *
     * @return      bool     Returns TRUE on success, FALSE on failure
     */
    function copy_recursive($source, $dest)
    {

        global $wp_filesystem;

        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return $wp_filesystem->copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            if (!wp_mkdir_p($dest)) {
                $wp_filesystem->mkdir($dest) or wp_die("Could not created $dest");
            }
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            $this->copy_recursive("$source/$entry", "$dest/$entry");
        }

        // Clean up
        $dir->close();
        return true;
    }

    /**
     * @param null $tempDir
     */
    public function setTempDir($tempDir)
    {
        $this->_tempDir = $tempDir . (false === strpos($tempDir, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '');
    }

    /**
     * @return null
     */
    public function getTempDir()
    {
        if (null === $this->_tempDir) {
            $this->_tempDir = get_temp_dir();
        }
        return $this->_tempDir;
    }
}

$je = new Hugo_Export();

if (defined('WP_CLI') && WP_CLI) {

    class Hugo_Export_Command extends WP_CLI_Command
    {

        function __invoke()
        {
            global $je;

            $je->export();
        }
    }

    WP_CLI::add_command('hugo-export', 'Hugo_Export_Command');
}
