// package functions and components
import React, { useState } from 'react';
import { Helmet } from "react-helmet";
import { orderBy } from "lodash";

// constants
import { FACET_LIMIT, LARGE_SCREEN_SIZE } from '../../utils/constants';

// custom hooks
import { useWindowSize } from './WindowSize';

// components
import { CustomFacet } from './Facets';
import { CustomHits } from './Hits';
import { CustomPagination } from './Pagination';
import { CustomScrollTo } from './ScrollTo';
import { FacetsSummary } from './FacetsSummary';
import { MobileFacets } from './MobileFacets';
import { SearchInput } from './SearchInput';
import { SearchSummary } from './SearchSummary';
import { SortBy } from './SortBy';

import 'focus-visible';
import styled from 'styled-components';

const FocusVisible = styled.div`
  &.js-focus-visible :focus:not(.focus-visible) {
    outline: none;
  }
  &.js-focus-visible .focus-visible,
  .focus-visible + label {
    outline-offset: 2px;
    outline: 1px solid #528deb;
  }
`;

export const SearchLayout = ({
  resultsComponent, // component is being rendered for results
  resultsClasses, // results specific classes
  facets = [], // side bar filters
  defaultSort = '',
  sortItems = [],
  highlight = false,
  activeLink = false, // news and events toggler
  setActiveView, // news and events active view
  ExtraLinksComponent = false,
  highlightedBlock = false,
  searchPlaceholder,
}) => {
  const [width, height] = useWindowSize(); // use custom hook to control layout
  const [mobileFiltersActive, setMobileFiltersActive] = useState(false);

  return (
    <FocusVisible className="l-container l-search-layout js-focus-visible focus-visible">
      <Helmet
        bodyAttributes={{
          'class': `${mobileFiltersActive ? 'no-scroll' : ''}`,
        }}
      />
      <div className="l-row">

        {/* If the window is large screen or higher, render the <aside></aside> */}
        {width > 0 && width >= LARGE_SCREEN_SIZE && facets.length > 0 &&
          <aside className="l-search-layout__sidebar">
            <Facets facets={facets} />
          </aside>
        }

        <div className="l-search-layout__main">
          <CustomScrollTo />

          {/* Specific to the News and Events buttons, just a toggle between views */}
          {ExtraLinksComponent && <ExtraLinksComponent activeLink={activeLink} setActiveView={setActiveView} />}

          <div className="l-row--no-columns c-form">
            <SearchInput placeholder={searchPlaceholder} />
            {sortItems.length > 0 &&
              <SortBy
                defaultRefinement={defaultSort}
                items={sortItems}
              />
            }
          </div>

          {/* If the window is medium or lower, render the mobile filters */}
          {width > 0 && width < LARGE_SCREEN_SIZE && facets.length > 0 &&
            <React.Fragment>
              <div className="l-row--no-columns u-vr__py--1 u-flex-center">
                <button
                  className="o-btn o-btn--secondary"
                  onClick={() => setMobileFiltersActive(true)}
                >
                  <i className="fas fa-sliders-h" aria-hidden="true"></i>{' '}FILTERS
                  </button>
              </div>

              <MobileFacets
                FacetsComponent={Facets}
                facets={facets}
                active={mobileFiltersActive}
                setActive={setMobileFiltersActive}
              />

            </React.Fragment>
          }

          <SearchSummary />
          <CustomHits
            Component={resultsComponent}
            classes={resultsClasses}
            highlight={highlight}
            highlightedBlock={highlightedBlock}
          />
          <CustomPagination />

        </div>
      </div>
    </FocusVisible>
  )
};

// create facets component for use here within the app
const Facets = ({ facets = [], classes = [] }) => (
  facets.map((facet, i) => {
    let { label, field, andOr = 'and' } = facet; // deconstruct the prop to help rendering
    return (
      <CustomFacet
        attribute={field}
        classes={classes}
        key={i}
        label={label}
        limit={FACET_LIMIT}
        operator={andOr}
        showMore
        showMoreLimit={50}
      />
    )
  })
)

