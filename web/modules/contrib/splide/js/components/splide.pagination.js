/**
 * @file
 * Provides thumbnail grid/ hover on dot pagination as asnavfor alternative.
 */

(function (Drupal, _db, _ds) {

  'use strict';

  var ThumbPagination = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var _pagination = o.pagination;
    var _fx = 'is-paginated--fx';
    var _thumbed = _pagination === 'thumb' || _db.hasClass(root, _fx + '-grid') || _db.hasClass(root, _fx + '-hover');

    return {
      mount: function () {
        var me = this;

        if (_pagination && _thumbed) {
          Splide.on('pagination:mounted', me.thumbify.bind(me));
        }
      },

      thumbify: function (data) {
        _db.forEach(data.items, function (item) {
          var btn = item.button;
          if (btn.nextElementSibling === null) {
            var slide = item.Slides[0].slide;
            var media = slide.querySelector('[data-thumb]');
            if (media) {
              var url = media.getAttribute('data-thumb');
              var stage = slide.querySelector('img');
              var alt = stage === null ? 'Preview' : stage.getAttribute('alt');
              var img = '<img alt="' + Drupal.t(alt) + '" src="' + url + '" loading="lazy" decoding="async" />';
              var el = document.createElement('span');
              el.innerHTML = img;
              el.className = 'splide__pagination__tn';
              btn.insertAdjacentElement('afterend', el);
            }
          }
        });
      }

    };
  };

  _ds.extend({
    ThumbPagination: ThumbPagination
  });

})(Drupal, dBlazy, dSplide);
