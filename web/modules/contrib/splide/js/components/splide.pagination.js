/**
 * @file
 * Provides thumbnail grid/ hover on dot pagination as asnavfor alternative.
 */

(function ($, Drupal, _ds) {

  'use strict';

  var ThumbPagination = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var _pagination = o.pagination;
    var _fx = 'is-paginated--fx';
    var _dataThumb = 'data-thumb';
    var _thumbed = _pagination === 'thumb' || $.hasClass(root, _fx + '-grid') || $.hasClass(root, _fx + '-hover');

    return {
      mount: function () {
        var me = this;

        if (_pagination && _thumbed) {
          Splide.on('pagination:mounted.tnp', me.thumbify.bind(me));
        }
      },

      thumbify: function (data) {
        $.forEach(data.items, function (item, i) {
          var btn = item.button;
          if (btn.nextElementSibling === null) {
            var obj = Components.Slides.getAt(i);

            if (obj) {
              var slide = obj.slide;
              var media = slide.querySelector('[' + _dataThumb + ']');
              if (media) {
                var url = media.getAttribute(_dataThumb);
                var stage = slide.querySelector('img');
                var alt = stage === null ? 'Preview' : stage.getAttribute('alt');
                var img = '<img alt="' + Drupal.t(alt) + '" src="' + url + '" loading="lazy" decoding="async" />';
                var el = document.createElement('span');
                el.innerHTML = img;
                el.className = 'splide__pagination__tn';
                btn.insertAdjacentElement('afterend', el);
              }
            }
          }
        });
      }

    };
  };

  _ds.listen({
    ThumbPagination: ThumbPagination
  });

})(dBlazy, Drupal, dSplide);
