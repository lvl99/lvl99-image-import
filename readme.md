# LVL99 Image Import (WordPress Plugin) v0.1.0-alpha

Author: Matt Scheurich <matt@lvl99.com>


## Description

LVL99 Image Import is a WordPress plugin which allows you to easily import into the Media Library (or change) any images referenced within post content. This was developed to aid importing [WordPress.com](http://www.wordpress.com/) hosted images into self-hosted WP sites for easy transition.


## Installation

1. Download files and place in your WordPress plugins directory
2. Activate plugin
3. High-five a friend!


## Usage

* Image Import will scan all your posts' contents (if `post_type=attachment`, it will scan the `guid` field too).

* You can either import images to the media library (this will also update all the image references to reference the new images located in your media library), or just change the image references themselves (for instance, you could change domains across all image references , e.g. `http://example.com/image.jpg` to `http://example.org/image.jpg`)

* Filters can allow you to *include* and *exclude* certain image references, and *search and replace* specific terms within those references. These are [PCRE compatible](http://www.regex101.com/), should that take your fancy.


## Notes

* Visit [github.com/lvl99/lvl99-image-import](http://www.github.com/lvl99/lvl99-image-import) for news and updates
* Fork development of this plugin at [github.com/lvl99/lvl99-image-import](http://www.github.com/lvl99/lvl99-image-import)
* Consider supporting this free plugin's creation and development by donation


## Todos

* Could easily be expanded to support non-image media
* Bug fixes


## Licence

GNU General Public License v2.0 only (see license.md for full license text)

```
Copyright (C) 2015 Matt Scheurich (matt@lvl99.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
MA 02110-1301, USA.
```