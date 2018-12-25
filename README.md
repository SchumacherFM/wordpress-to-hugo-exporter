# WordPress to Hugo Exporter

Hugo is a static site generator written in GoLang:
[https://gohugo.io](https://gohugo.io)

This repo is a *personal fork* of
https://github.com/SchumacherFM/wordpress-to-hugo-exporter v1.6:

- Put posts in `content/` instead of `posts/` to match Hugo's
  expectations
- Make Markdown conversion optional, and disabled by default
- Enable comment export by default
- Include comment author's URL, if provided (still includes extraneous
  class=url attribute from Wordpress but whatever)

## License

The project is licensed under the GPLv3 or later

