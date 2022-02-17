
***
# <a name="changes"></a>NOTABLE CHANGES
* _Blazy 2.6_:
   + [Preloading](https://drupal.org/node/3262804).
   + [Anti-pattern buffer](https://drupal.org/node/3262724).
   + Works absurdly fine at IE9 for core lazy functionality. Not fancy features
     like Blur or Animation, etc. Unless you include some polyfills on your own.
   + [Drupal 10 ready](https://drupal.org/node/3254692).
   + `dBlazy.js` is pluginized, has minimal jQuery replacement methods to DRY.
     Check out `js/components/jquery/blazy.photobox.js` for a sample.
   + `dBlazy.js` removed many old IEs fallback. Some were moved into polyfill
     which can be ditched via Blazy UI to abandon IE supports. Should you need
     to support more, please find and include polyfill into your theme globally.
   + Old bLazy is now a [fallback for IO](https://drupal.org/node/3258851) to
     have a single source of truth to minimize competitions and complications.
     Competition is great to measure survival, but not within a module codebase.
     The library is forked at Blazy 2.6, and no longer required from now on.
     Both lazyloader scripts (IO + bLazy) can be ditched via `No JavaScript`.
   + [Decoupled lazyload JavaScript](https://drupal.org/node/3257512). Now Blazy
     works without JavaScript within/without JavaScript browsers.
     Even [AMP](https://drupal.org/node/3101810) pages.
     Any javascript-related issues might no longer be valid when
     `No JavaScript lazy` enabled. Unless the exceptions are met or for those
     who still support old IEs, and cannot ditch lazyloader script, yet.
   + [Massive optimization](https://drupal.org/node/3257511). Please report any
     uncovered regressions, or issues for quick fixes. Thanks.
