import React from 'react';
import { connectSearchBox } from 'react-instantsearch-dom';

const SearchBox = ({ currentRefinement, refine, placeholder = "Search..." }) => {
  return (
    <div className="c-form__element c-form__element--search-input t-background--gray-light">
      <input
        className="c-form__text c-form__text--has-addon"
        aria-label={placeholder}
        aria-describedby="search-addon"
        type="search"
        value={currentRefinement}
        onChange={event => refine(event.currentTarget.value)}
        placeholder={placeholder}
      />
      <button className="o-btn o-btn--input-addon" aria-label="Search" id="search-addon">
        <i class="fas fa-search 0-icon o-icon--white"></i>
      </button>
    </div>
  );
};


// export component, use HOC connectSearchBox to wire things up to Algolia
export const SearchInput = connectSearchBox(SearchBox);

