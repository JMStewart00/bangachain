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
      { label: 'Brand', field: 'brand', andOr: 'or', alpha: true },
      { label: 'Speed', field: 'speed', andOr: 'or', alpha: true },
      { label: 'Glide', field: 'glide', andOr: 'or', alpha: true },
      { label: 'Turn', field: 'turn', andOr: 'or', alpha: true },
      { label: 'Fade', field: 'fade', andOr: 'or', alpha: true },
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
          filters={`status:true AND stock_level > 0`}
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
