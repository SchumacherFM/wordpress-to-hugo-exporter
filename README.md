# WordPress to Hugo Exporter

Hugo is a static site generator written in GoLang:
[https://gohugo.io](https://gohugo.io)

This repo is a *personal fork* of
https://github.com/SchumacherFM/wordpress-to-hugo-exporter v1.6:

- Put posts in `content/post/` instead of `posts/` to match Hugo's
  expectations
- Enable comment export by default
- Posts are index.md files in individual directories under `content/`,
  and each comment is a separate Markdown file in that dir
- Merge categories into tags
- Exclude default category (by hardcoded name)
- Remove Markdown conversion (except for metadata framing)
- Include pingbacks/trackbacks
- Detect posts with all-zeroes dates, including where GMT date is
  invalid but non-GMT date is usable, and treat them as undated
- Change exporter to send zip file to stdout like CLI wrapper claims
  it will
- Include instructions in CLI wrapper for multi-host setups
- Fix hugo config.yaml creation (correct keys, and simpler process)
- Put everything in root of zip file, not under another folder
- Internal: Don't put trailing slashes on directory path strings; add
  trailing slash when concatenating instead
- Special-case `openid_comments` postmeta by applying to comments
  directly

## License

The project is licensed under the GPLv3 or later

