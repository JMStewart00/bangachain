import React from 'react';

import { toArray } from '../../utils/helpers';

export const SiteWideResult = ({ hit }) => {
  const {
    url,
    title = '',
    brand,
    speed,
    turn,
    glide,
    fade,
    variation_images = [],
    stock_level
  } = hit;

  const indexedImageField = toArray(variation_images);

  let newImageUrl = false;
  if (indexedImageField.filter(a => a.length > 0).length > 0) {
    const images = indexedImageField.filter(a => a.length > 0);
    if (images.length > 0) {
      newImageUrl = images[0].split('/');
      newImageUrl.splice(4, 0, 'styles/square/public');
      newImageUrl = newImageUrl.join('/');
      // newImageUrl += '.webp';
    }
  }

  return (
    <div className="c-search-result__inner c-search-result__inner--no-grid">
      <div className="c-search-result__content-container--main-search c-product c-product--teaser">
        <a href={url} className="o-link">
          <div class="c-product__image">
            {newImageUrl ? (
              <img src={newImageUrl} alt={title} width="200" height="200" />
            ) : (
              <img src={'/themes/custom/bangachain/assets/images/default-disc.png'} alt={'Default Disc Image'} width="200" height="200" />
            )}
            <div class="c-product__brand">{brand}</div>
          </div>

          <div class="c-product__title">
            <div class="u-color__blue h2">{title }</div>
          </div>

          {speed &&
            <div class="c-product__flight-numbers">
              <ul class="o-list-inline">
                <li class="c-product__speed--small o-list-inline__item" title="Speed">{speed}</li>
                <li class="c-product__glide--small o-list-inline__item" title="Glide">{glide}</li>
                <li class="c-product__turn--small o-list-inline__item" title="Turn">{turn}</li>
                <li class="c-product__fade--small o-list-inline__item" title="Fade">{fade}</li>
              </ul>
            </div>
          }
        </a>
      </div>
    </div>
  );
}
