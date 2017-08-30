ZeroGravity - Grav-like content management in Symfony
=====================================================

## Media

Image and document files can be put alongside page definitions. Given the following files exist in your `/var/pages/` structure:

```
var/pages
├── 01.home # slug: ~
│   ├── image1.jpg
│   └── home.png
│
├── 02.blog # slug: blog
│   ├── files
│   │   └── file1.pdf
│   │
│   └── images
│       ├── bloggy1.jpg
│       ├── bloggy2.jpg
│       ├── bloggy3.jpg
│       └── bloggy4.jpg
│
└── images
    ├── file1.jpg
    ├── file2.jpg
    ├── file3.jpg
    ├── file4.jpg
    │
    ├── header
    │   └── top.png
    │
    └── icons
        ├── icon1.svg
        ├── icon2.svg
        └── icon3.svg
```

As you can see, files can be put anywhere inside your file structure. You will be able to access them
using the following paths:

```
# since the "home" slug is empty, the files can be access relative to the slug:
/01.home/image1.jpg
/01.home/home.png
/image1.jpg  
/home.png

# same for the "02.blog" directory: both slug and directory names can be used
/02.blog/images/bloggy*.jpg
/blog/images/bloggy*.jpg

# directories without any page information work just as well:
/images/file*.jpg
/images/header/top.png
/images/icons/icon*.svg
```

When using files and images in your templates, there are 2 ways: by straight URL (for markdown usage) and using twig helpers.

### Straight URL

By default, 2 routes are registered by ZeroGravityBundle:

```
# images using LiipImagine filters:
/i/{filter}/{path}

# document files:
/f/{path}
```

You may configure the allowed extensions for each type using the bundle configuration.

Accessing images and filters might look like this in markdown:

```markdown
![Alt Text](/i/my_filter/home.png)
![Alt Text](/i/my_filter/01.home/image1.jpg)
![Alt Text](/i/another_filter/images/header/top.png)

<img src="/i/another_filter/images/header/top.png" />
```

Optional: images may also be accessed relative to the current page. This requires
the kinda-magic `___` (3 underscores) route:

```markdown
# on /blog

![Alt Text](my_filter/___/images/bloggy1.jpg)

# results in browser calling the URL:
# /blog/my_filter/___/images/bloggy1.jpg
# will be redirected to:
# /i/my_filter/blog/images/bloggy1.jpg
```

A similar mechanism exists for files, using 3 dashes:

```markdown
# on /blog

[Link text](---/files/file1.pdf)

# results in browser calling the URL:
# /blog/---/files/file1.pdf
# will be redirected to:
# /f/blog/files/file1.pdf
```

### Twig helpers and programmatic access 

