// package functions and components
import React, { useState } from 'react';

// components
import { CustomHits } from './Hits';

export const LimitedSearchBlock = ({
  resultsComponent, // component is being rendered for results
  resultsClasses, // results specific classes
  highlight = false,
  activeLink = false, // news and events toggler
  setActiveView, // news and events active view
  ExtraLinksComponent = false,
}) => {

  const [searchQuery, setSearchQuery] = useState('');

  const handleSearch = (e) => {
    e.preventDefault();
    window.location.href = `/news-events?page=1&active=events&query=${searchQuery}`
  }

  const handleChange = e => setSearchQuery(e.target.value);

  return (
    <div className="l-container l-search-layout">
      <div className="l-row">
        {/* Specific to the News and Events buttons, just a toggle between views */}
        {ExtraLinksComponent && <ExtraLinksComponent activeLink={activeLink} setActiveView={setActiveView} />}

        <form className="c-form c-form--news-events" onSubmit={handleSearch}>
          <div className="c-form__element c-form__element--search-input c-form__element--news-events">
            <input
              className="c-form__text c-form__text--has-addon"
              aria-label="Search input"
              aria-describedby="search-addon"
              type="search"
              value={searchQuery}
              onChange={handleChange}
              />
            <button className="o-btn o-btn--input-addon" aria-label="Search" id="search-addon" type="submit">
              Search
            </button>
          </div>
        </form>
        <CustomHits
          Component={resultsComponent}
          classes={resultsClasses}
          highlight={highlight}
          />
      </div>
    </div>
  )
};
