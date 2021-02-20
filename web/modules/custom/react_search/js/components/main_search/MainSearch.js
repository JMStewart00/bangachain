// package functions and components
import React, { Component } from 'react';
import {
  Configure,
  InstantSearch,
} from 'react-instantsearch-dom';

// helpers
import {
  createURL,
  searchStateToURL,
  urlToSearchState,
} from '../../utils/helpers';

// custom Components
import { SiteWideResult } from './SiteWideResult';
import { SearchLayout } from '../generic/SearchLayout';
import { searchClient } from '../../utils/searchClient';
import { DEBOUNCE_TIME, MAIN_ID } from '../../utils/constants';

class MainSearch extends Component {
  state = {
    searchState: urlToSearchState(this.props.location),
    lastLocation: this.props.location,
  };

  static getDerivedStateFromProps(props, state) {
    if (props.location !== state.lastLocation) {
      return {
        searchState: urlToSearchState(props.location),
        lastLocation: props.location,
      };
    }

    return null;
  }

  onSearchStateChange = searchState => {
    clearTimeout(this.debouncedSetState);

    this.debouncedSetState = setTimeout(() => {
      this.props.history.push(
        searchStateToURL(this.props, searchState, createURL),
        searchState
      );
    }, DEBOUNCE_TIME);

    this.setState({ searchState });
  };

  render() {
    const { searchState } = this.state;
    const facets = [
      { label: 'Product', field: 'product_type', andOr: 'or', alpha: true },
      { label: 'Brand', field: 'brand' },
      { label: 'Speed', field: 'speed' },
      { label: 'Glide', field: 'glide' },
      { label: 'Turn', field: 'turn' },
      { label: 'Fade', field: 'fade' },
    ];

    let sorts = [];

    return (
      <InstantSearch
        searchClient={searchClient}
        indexName={MAIN_ID}
        searchState={searchState}
        onSearchStateChange={this.onSearchStateChange}
        createURL={createURL}
      >
        {/*
          Filter is set up to only include published nodes/events,
          not noindex, nofollow and only english or und languages
        */}
        <Configure
          hitsPerPage={24}
          filters={`status:true`}
        />
        <SearchLayout
          defaultSort={MAIN_ID}
          facets={facets}
          resultsClasses={"c-search-result--main-search"}
          resultsComponent={SiteWideResult}
          sortItems={sorts}
          searchPlaceholder="Search Bangachain.com..."
        />
      </InstantSearch>
    );
  }
}

export default MainSearch;
