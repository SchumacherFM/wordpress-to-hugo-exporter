# WordPress to Hugo Exporter

Hugo is a static site generator written in GoLang:
[https://gohugo.io](https://gohugo.io)

This repo is a *personal fork* of
https://github.com/SchumacherFM/wordpress-to-hugo-exporter v1.6:

- Put posts in `content/` instead of `posts/` to match Hugo's
  expectations
- Enable comment export by default
- Posts are index.md files in individual directories under `content/`,
  and each comment is a separate Markdown file in that dir
- Merge categories into tags
- Exclude default category (by hardcoded name)
- Remove Markdown conversion (except for metadata framing)
- Include pingbacks/trackbacks
- Handle all-zeroes dates, including where GMT date is invalid but
  non-GMT date is usable
- Fix instructions in CLI wrapper

Broken:

- As with the original repo, you'll need to make some changes to the
  generated config.yaml file:
    - Change `url` to `baseUrl`
    - Change `name` to `title`

## License

The project is licensed under the GPLv3 or later

