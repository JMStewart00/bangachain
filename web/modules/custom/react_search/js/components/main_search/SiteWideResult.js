import React from 'react';
import { connectHighlight } from 'react-instantsearch-dom';

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
    product_image,
    variation_images
  } = hit;

  const indexedImageField = toArray(variation_images);

  let newImageUrl = false;
  if (indexedImageField.filter(a => a.length > 0).length > 0) {
    const images = indexedImageField.filter(a => a.length > 0);
    if (images.length > 0) {
      newImageUrl = images[0].split('/');
      newImageUrl.splice(4, 0, 'styles/square/public');
      newImageUrl = newImageUrl.join('/');
    }
  }

  return (
    <div className="c-search-result__inner c-search-result__inner--no-grid">
      <div className="c-search-result__content-container--main-search">
        <a href={url} className="o-link">
          {newImageUrl ? (
            <img src={newImageUrl} alt={title} width="200" height="200" />
          ) : (
            <img src={'/themes/custom/bangachain/assets/images/default-disc.png'} alt={'Default Disc Image'} width="200" height="200" />
          )}
          {title && <h2><TitleHighlight hit={hit} attribute='title'/></h2>}
          { (speed || glide || turn || fade) &&
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

// Search highlighting for the Title link.
const TitleHighlight = connectHighlight(({ highlight, attribute, hit }) => {
  const parsedHit = highlight({
    highlightProperty: '_highlightResult',
    attribute,
    hit,
  });
  return (
    <>
      {
        parsedHit.map((part, index) => {
          return (
            part.isHighlighted ?
              <strong key={index}>{part.value}</strong> :
              <React.Fragment key={index}>{part.value}</React.Fragment>
          )
        })
      }
    </>
  );
});



