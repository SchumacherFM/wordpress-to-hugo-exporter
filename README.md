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

## Usage

1. Place plugin in `/wp-content/plugins/` folder
2. Make sure `extension=zip.so` line is uncommented in your `php.ini`
3. Activate plugin in WordPress dashboard
4. Select `Export to Hugo` from the `Tools` menu

## Command-line Usage

If you're having trouble with your web server timing out before the export is
complete, or if you just like terminal better, you may enjoy the command-line
tool.

It works just like the plugin, but produces the zipfile at `/tmp/wp-hugo.zip`:

    php hugo-export-cli.php

Alternatively, if you have [WP-CLI](http://wp-cli.org) installed, you can run:

```
wp hugo-export > export.zip
```

The WP-CLI version will provide greater compatibility for alternate WordPress
environments, such as when `wp-content` isn't in the usual location.

## Changelog

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

