# WordPress to Hugo Exporter

Hugo a static site generator written in GoLang: [https://gohugo.io](https://gohugo.io)

This repo is based on [https://github.com/benbalter/wordpress-to-jekyll-exporter](https://github.com/benbalter/wordpress-to-jekyll-exporter)

## Hugo Features

One-click WordPress plugin that converts all posts, pages, taxonomies, metadata,
and settings to Markdown and YAML which can be dropped into Hugo.

## Features

* Converts all posts, pages, and settings from WordPress for use in Hugo
* Export what your users see, not what the database stores (runs post content
through `the_content` filter prior to export, allowing third-party plugins to
modify the output)
* Converts all `post_content` to Markdown Extra (using Markdownify)
* Converts all `post_meta` and fields within the `wp_posts` table to YAML front
matter for parsing by Hugo.
* Exports optionally `comments` as part of their posts. This features needs to be
enabled manually by editing the PHP source code. See file hugo-export.php at
line ~40.
* Export private posts and drafts. They are marked as drafts as well and won't get
published with Hugo.
* Generates a `config.yaml` with all settings in the `wp_options` table
* Outputs a single zip file with `config.yaml`, pages, and `post` folder
containing `.md` files for each post in the proper Hugo naming convention.
* No settings. Just a single click.

## Usage with a self hosted WordPress installation

1. Place plugin in `/wp-content/plugins/` folder
2. Make sure `extension=zip.so` line is uncommented in your `php.ini`
3. Activate plugin in WordPress dashboard
4. Select `Export to Hugo` from the `Tools` menu

## Usage at wordpress.com or any other hoster without SSH access

(I've never tried it, because not a wp.com user)

1. Login into the backend.
2. Create an XML export of the whole blog and download the XML file.
3. Setup a local WordPress instance on your machine. You need PHP, MySQL or
MariaDB and Nginx or Apache or Caddy Server. Alternatively you can install a
Docker Compose setup
[https://github.com/wodby/docker4wordpress](https://github.com/wodby/docker4wordpress)
4. Install this plugin by downloading a zip file of this repo and uploading to WP.
5. Import the XML export. You should take care that the WordPress version of the
export matches the WP version used for the import.
6. In the WP backend run the `Export to Hugo` command. If that fails go to the
command line run the CLI script with `memory_limit=-1`, means unlimited memory
usage.
7. Collect the ZIP via download or the CLI script presents you the current name.
8. Remove WordPress and enjoy Hugo.

Re Docker: It should be very easy to create a Dockerfile containing everything
above mentioned for a one time conversion of the XML file to the Hugo format.

## Command-line Usage

If you're having trouble with your web server timing out before the export is
complete, or if you just like terminal better, you may enjoy the command-line
tool.

It works just like the plugin, but produces the zipfile at `/tmp/wp-hugo.zip`:

    php hugo-export-cli.php


If you want to offer a folder (say a mount point to a huge drive) other than using `/tmp` in OS, pass it as the first argument to the script:

    php hugo-export-cli.php /YOUR_PATH_TO_TMP_FOLDER/

Alternatively, if you have [WP-CLI](http://wp-cli.org) installed, you can run:

```
wp hugo-export > export.zip
```

The WP-CLI version will provide greater compatibility for alternate WordPress
environments, such as when `wp-content` isn't in the usual location.

## Changelog

### 1.6

* Fix destination against hugo 0.27
* Fix for working on older PHP
* Fix memory leak in Converter.php by cut unnessesary
* updated markdownify
* entities fix and post image added

### 1.5

* Export drafts and private posts
* Export optionally comments
* Various changes and fixes

### 1.4

* Made license explicit
* Removed word-wrap from YAML export to prevent breaking permalinks

### 1.3

* Use [fork of Markdownify](https://github.com/Pixel418/Markdownify) rather than external API to convert content from HTML to markdown
* Better memory utilization for larger sites, props @ghelleks

### 1.2

* Commmand-line support, props @ghelleks and @scribu

### 1.1

* Use WP_Filesystem for better compatability
* 1.1.1 - Use heckyeahmarkdown to prevent PHP errors when Markdownify chokes on malformed HTML
* 1.1.2 - clarify zip.so requirement in readme

### 1.0

* Initial Release

## License

The project is licensed under the GPLv3 or later

