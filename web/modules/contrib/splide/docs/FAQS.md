***
***
# <a name="faq"></a>FAQS

## PROGRAMATICALLY
[**splide.api.php**](https://git.drupalcode.org/project/splide/-/blob/1.0.x/splide.api.php)


## QUICK PERFORMANCE TIPS
* Use lazyLoad **nearby** for tons of images, not **sequential**. Unless within
  an ajaxified lightbox.
* Use lazyload **Blazy** for carousels below the fold to delay loading them.
* Tick **Optimized** option on the top right of Splide optionset edit page.
* Use image style with regular sizes containing effect **crop** in the name.
  This way all images will inherit dimensions calculated once.
* Disable core library **splide.min.css** to minimize overrides at:
  **/admin/config/media/splide/ui**
* Use Blazy multi-serving images, Responsive image, or Picture, accordingly.
* Uninstall Splide UI at production.
* Enable Drupal cache, and CSS/ JS assets aggregation.


## OPTIONSETS
To create optionsets, go to:

  [Splide UI](/admin/config/media/splide)

Enable Splide UI sub-module first, otherwise regular **Access denied**.
They will be available at field formatter "Manage display", and Views UI.

## VIEWS AND FIELDS
Splide works with Views and as field display formatters.
Splide Views is available as a style plugin included at splide_views.module.
Splide Fields formatters included as a plugin which supports:
Image, Media, Field Collection, Paragraphs, Text.


## NESTED SPLIDES
Nested splide is a parent Splide containing slides which contain individual
splide per slide. The child splides are basically regular slide overlays like
a single video over the large background image, only with nested splides it can
be many videos displayed as a slideshow as well.
Use Splide Fields with Field Collection or Paragraphs or Views to build one.
Supported multi-value fields for nested splides: Image, Text, Media.


## <a name="skins"></a>SKINS
The main purpose of skins are to demonstrate that often some CSS lines are
enough to build fairly variant layouts. No JS needed. Unless, of course, for
more sophisticated slider like spiral 3D carousel which is beyond what CSS can
do. But more often CSS will do.

Skins allow swapable layouts like next/prev links, split image or caption, etc.
with just CSS. However a combination of skins and options may lead to
unpredictable layouts, get yourself dirty. Use the provided samples to see
the working skins.

Some default complex layout skins applied to desktop only, adjust for the mobile
accordingly. The provided skins are very basic to support the necessary layouts.
It is not the module job to match your awesome design requirements.

### Registering Splide skins:
[**splide.api.php**](https://git.drupalcode.org/project/splide/-/blob/1.0.x/splide.api.php#L352)

1. Copy `\Drupal\splide\Plugin\splide\SplideSkin` into your module
  `/src/Plugin/splide directory`.
2. Adjust everything accordingly: rename the file, change SplideSkin ID and
  label, change class name and its namespace, define skin name, and its CSS and
  JS assets.

The SplideSkin object has 3 supported methods: `::setSkins()`, `::setDots()`,
`::setArrows()` to have skin options for main/thumbnail/overlay displays, dots,
and arrows skins respectively.

The declared skins will be available for custom coded, or UI selections.
Be sure to clear cache since skins are permanently cached!

### Optional skins:
* **None**

  It is all about DIY.
  Doesn't load any extra CSS other than the basic styles required by splide.
  Skins at the optionset are ignored, only useful to fetch description and
  your own custom work when not using the sub-modules, nor plugins.
  If using individual slide layout, do the layouts yourself.

* **Classic**

  Adds dark background color over white caption, only good for slider (single
  slide visible), not carousel (multiple slides visible), where small captions
  are placed over images, and animated based on their placement.

* **Full screen**

  Works best with 1 perPage. Use z-index layering > 8 to position elements
  over the slides, and place it at large regions. Currently only works with
  Splide fields, use Views to make it a block. Use Splide Paragraphs to
  have more complex contents inside individual slide, and assign it to Slide
  caption fields.

* **Full width**

  Adds additional wrapper to wrap overlay video and captions properly.
  This is designated for large slider in the header or spanning width to window
  edges at least 1170px width for large monitor. To have a custom full width
  skin, simply prefix your skin with "full", e.g.: fullstage, fullwindow, etc.

* **Split**

  Caption and image/media are split half, and placed side by side. This requires
  any layout containing "split", otherwise useless.

* **Grid**

  Only reasonable if you have considerable amount of slides.
  Uses the Foundation 5.5 block-grid, and disabled if you choose your own skin
  not named Grid. Otherwise overrides skin Grid accordingly.

  **Requires:**

  `Visible slides`, `Skin Grid` for starter, A reasonable amount of slides,
  Optionset with Rows and slidesPerRow = 1. If no`Display style` selected,
  default  to `Grid Foundation`.

  Avoid `autoWidth` and `autoHeight`. Use consistent dimensions.
  This is module feature, and offers more flexibility.
  Available at `splide_views` plugin via Views UI, or formatters via Field UI.

If you want to attach extra 3rd libraries, e.g.: image reflection, image zoomer,
more advanced 3d carousels, etc.:
1. simply put them into js array of the target skin. Be sure to add proper
   weight, if you are acting on existing splide events, normally < 0
   (`splide.load.min.js`) is the one.
2. use the provided js plugin extensions, see: `js/components` for samples.

Other skins are available at
**Splide X**
Some extra skins are WIP which may not work as expected.
Use them as starters, not final products!


## GRID
To create Splide grid or multi-row carousels, there are 3 options:

1. **One row grid managed by library:**

   [/admin/config/media/splide](/admin/config/media/splide)

   Edit the current optionset, and set

   `perPage > 1`

2. **Multiple rows grid managed by library:**

   Not currently supported, 2022/2.

3. **Multiple rows grid managed by module:**

   [Grid sample](/admin/structure/views/view/splide_x/edit/block_grid)
   from `splide_x`. Be sure to install the Splide X sub-module first.
   Requires:  
   + skin **Grid**
   + `perPage = 1`

The first 2 are supported by core library using pure JS approach.
The last is the Module feature using pure CSS: `Grid Foundation`, `CSS3 columns`
or `Native Grid` via `Blazy Grid` available under `Display style` option.

**The key is:**

The total amount of Views results must be bigger than `Visible slides`,
otherwise broken Grid, see skin Grid above for more details.


## <a name="html-structure"></a>HTML STRUCTURE
Unlike Slick, Splide library supports BEM.

```html
<div class="splide splide--UNIQUE">
  <div class="splide__slider">
    <div class="splide__track">
      <ul class="splide__list">
        <li class="splide__slide"> </li>
      </ul>
    </div>
  </div>
</div>
```

The `splide--UNIQUE` class is any of:  
1. `splide--default`: default implementations via `splide.load.min.js`  
2. `splide--vanilla`: vanilla implementations via `splide.vanilla.min.js`  
3. `splide--YOURS`: your own implementations, your custom scripts.

As outlined in details at [Splide UI](/admin/config/media/splide/ui).

All splide variants require classes and containers, including item list `UL`:  
`splide, splide__track, splide__list, splide__slide`

The only optional is `splide__slider`. However be aware, some stock skins might
require it till further updates. Leave it as is unless you are on your own.

Unlike Slick, Splide requires manual additions of `splide__track, splide__list`.


## SPLIDE VS. SLICK CSS STATE CLASSES
<pre>
* `is-mounted`       x `slick--initialized`
* `is-carousel`      x `slick--multiple-view`
* `is-less`          x `slick--less`
* `is-vertical`      x `slick--vertical`
* `is-captioned`     x `slick--has-caption`
* `is-arrowed--down` x `slick--has-arrow-down`
* `is-MEDIA-SWITCH`  x `slick--MEDIA-SWITCH`
* `is-active`        x `slick-current`
* `is-visible`       x `slick-active`
</pre>

## SPLIDE VS. SLICK CSS DISPLAY CLASSES
<pre>
* `splide--main`     x `slick--main`
* `splide--nav`      x `slick--thumbnail`
</pre>

The `splide--nav` is backed/ reserved by the library. Display classes are only
available if using synced navigation. Otherwise just `splide--default`.

The skin and optionset remain the same, only different namespace:  

* `splide--skin--SKIN-NAME`
* `splide--optionset--OPTIONSET-NAME`


## CURRENT DEVELOPMENT STATUS
Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, always open:

**[/admin/config/development/performance](/admin/config/development/performance)**

And hit **Clear all caches** button once the new Splide is in place.
Regenerate CSS and JS as the latest fixes may contain changes to the assets.
Have the latest or similar release Blazy to avoid trouble in the first place.

For drush users, if running regular `drush updb` and `drush cr` breaks for some
reasons, try the good old browser above. Such breakages not necessarily relate
to this module.

## HOW CAN YOU HELP?
Please consider helping in the issue queue, provide improvement, or helping with
documentation.

If you find this module helpful, please help back spread the love. Thanks.
