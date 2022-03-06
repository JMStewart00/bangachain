
***
## <a name="troubleshooting"></a>TROUBLESHOOTING
Please consult Slick projects for additional info such as Slick Paragraphs, etc.
Most docs are still relevant for Splide, but might not be written at Splide.

Do not modify **Splide X** Views samples nor its optionsets, clone them instead!

Splide is a bit trickier than Slick, and unforgiving, too. Having a working
**Splide X** intact ensures you have a correctly working patron to look back
should you mess up with your own implementations.

If you don't find the below solve your issues, check out from Slick:
[Slick Gotchas](https://www.drupal.org/project/issues/search?issue_tags=slick%20gotchas)

1. When upgrading to later version, try to re-save options at:
   * `/admin/config/media/splide`
   * `/admin/structure/types/manage/CONTENT_TYPE/display`
   * `/admin/structure/views/view/VIEW_NAME`

     Only if trouble to see the new options, or when options don't apply
     properly. Most likely true when the library adds/changes options, or the
     module does something new. This is normal for any library even commercial
     ones, so bear with it.

2. Always clear the cache, and re-generate JS (if aggregation is on) when
   updating the module to ensure things are picked up:
   * `/admin/config/development/performance`
   * Or regular `drush updb` and `drush cr`

3. If you are customizing template files, or theme functions, be sure to
   re-check against the latest.

4. Splide release date is similar, or later than Blazy.

5. Skins are permanently cached. Clear cache if new skins you created or
   provided by sub-modules do not appear immediately.


## KNOWN ISSUES
1. Splide admin CSS may not be compatible with private or contrib admin
   themes. Only if trouble with admin display, please disable it at:

   `/admin/config/media/blazy`

2. The Splide lazyLoad is not compatible with Picture. Splide only
   facilitates Picture to get in. The image formatting is taken over by
   Picture. Some other options such as Aspect ratio is currently not
   supported either.

3. The following is not module related, but worth a note:
   * lazyLoad `nearby` has issue with dummy image excessive height.
     Added fixes to suppress it via option Aspect ratio (fluid).
     Or use `Blazy` lazyload for more advanced options.
   * Fade option with `perPage` > 1 will screw up.
   * `autoWidth` ignores `perPage`.
   * Too much `padding` at small device affects `perPage`.
   * If thumbnail display is `type == 'loop'`, the main one must be
     `type == 'loop'` too, else incorrect syncing.
   * `autoHeight` is no good for vertical.  

4. Lighbox integration
   * `type == 'loop'` option will create duplicates or clone slides which look
     more obvious if `perPage` > 1. This means that lightboxes (Colorbox,
     Photobox, PhotoSwipe) will have dups.  
     **Solution:**

     Change `type == 'loop'` option to either `slide` or `fade`.
     2021/5, added fixes for: Colorbox.
   * Photobox is best for:
     - `type == 'loop'` + `perPage` 1
     - `type != 'loop'` + `perPage` N

      If "`type == 'loop'` + `perPage > 1`" is a must, but you don't want dup
      thumbnails, simply override the JS to disable `thumbs` option.
