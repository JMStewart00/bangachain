import React from 'react';
import classNames from 'classnames';

import { ClearRefinements } from './FacetsSummary';

export const MobileFacets = ({ FacetsComponent, facets = [], active = false, setActive }) => {

  // generate a class list for the containing div
  let classList = classNames([
    "c-mobile-search-filters",
    "t-background--white",
    {
      'open': active, // open is only applied if active is true
    }
  ])

  return (
    <div className={classList}>
      <div className="c-mobile-search-filters__actions l-row--no-columns">

        <button
          className="c-mobile-search-filters__close l-box--sm"
          onClick={() => setActive(false)}
        >
          <i className="fas fa-times fa-1x" aria-hidden="true"></i>
        </button>

        <ClearRefinements clearsQuery />

        <button
          className="c-mobile-search-filters__apply"
          onClick={() => setActive(false)}
        >
          {'APPLY'}
        </button>

      </div>

      {/* render all the facets, send in a new modifier class */}
      <FacetsComponent facets={facets} classes={["c-facet--mobile"]} />

    </div>
  )
};
