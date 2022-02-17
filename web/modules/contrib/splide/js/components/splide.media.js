/**
 * @file
 * Provides Splide extensions for (local|remote) videos.
 *
 * This extension is using Blazy media player, not the Splide video extension
 * for reasons: already existed years ago, cross-modules, not only Splide,
 * php-based for cacheability, less size, etc. The caveat is lacking of options.
 * However the options respect Splide conventions to easily override/ switch.
 */

(function ($, _ds, _win) {

  'use strict';

  var Media = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var ov = o.video || {};
    var _autoVideo = ov.autoplay || false;
    var _videoTimer;
    var _interrupted = false;

    return {
      mount: function () {
        var me = this;

        Splide.on('active.spm', me.play.bind(me));
        Splide.on('moved.spm', me.close.bind(me));
        $.on(root, 'click', '.media__icon--close', me.stop.bind(me));
        $.on(root, 'click', '.media__icon--play', me.pause.bind(me));
      },

      /**
       * Turns off any playing (local|remote) videos.
       */
      close: function () {
        var me = this;

        me.stop();
        me.stopLocalVideo();
        _interrupted = false;
      },

      play: function (slide) {
        var me = this;

        if (!_autoVideo || _interrupted) {
          return;
        }

        var media = slide.slide.querySelector('.media');
        if (media !== null) {
          me.close();

          var btn = media.querySelector('.media__icon--play');
          var vid = media.querySelector('video');
          if (btn !== null) {
            me._play(btn);
          }
          else if (vid !== null) {
            me._play(vid);
          }
        }
      },

      _play: function (el) {
        _win.clearTimeout(_videoTimer);
        _videoTimer = _win.setTimeout(function () {

          el[$.equal(el, 'video') ? 'play' : 'click']();
        }, 501);
      },

      /**
       * Pauses the local video.
       */
      stopLocalVideo: function () {
        var vids = root.querySelector('video') === null ? [] : root.querySelectorAll('video');
        if (vids.length) {
          $.forEach(vids, function (vid) {
            vid.pause();
          });
        }
      },

      /**
       * Stops the remote video.
       */
      stop: function () {
        // Clean up any pause marker at slider container.
        root.classList.remove('is-paused');

        var cn = root.querySelector('.is-playing');
        if (cn !== null) {
          cn.classList.remove('is-playing');
          var btn = cn.querySelector('.media__icon--close');
          if (btn !== null) {
            _interrupted = true;
            btn.click();
          }
        }
      },

      /**
       * Trigger pause on splide instance when media playing a video.
       */
      pause: function () {
        _interrupted = true;
        root.classList.add('is-paused');
        if (o.autoplay) {
          Splide.off('autoplay:playing');
        }
      }
    };
  };

  _ds.extend({
    Media: Media
  });

})(dBlazy, dSplide, this);
