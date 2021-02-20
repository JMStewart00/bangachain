import React from 'react';
import { connectStateResults } from 'react-instantsearch-dom';

export const SearchSummary = connectStateResults(
  ({
    searchState, // currentSearchState, provided by Algolia connector (connectStateResults)
    searchResults, // list of results, provided by Aloglia connector (connectStateResults)
  }) => {
    const hasResults = searchResults && searchResults.nbHits !== 0;

    // build up a summary section like the mockups suggests
    const nbHits = searchResults && searchResults.nbHits; // set nbHits to searchResults.nbhits

    // construct summary text
    let summary = `The search returned the following ${nbHits} ${nbHits > 1 ? 'results' : 'result'}`;
    if (searchState.query) {
      summary += ` for your search "<strong>${searchState.query}</strong>".`;
    } else {
      summary += '.';
    }

    // building an array of the current refinements
    let currentRefinements = [];
    if (searchState && searchState.refinementList){
      Object.keys(searchState.refinementList)
        .filter(facet => searchState.refinementList[facet].length > 0)
        .map(facet => {
          currentRefinements.push(...searchState.refinementList[facet]);
        })

      if (currentRefinements.length > 0) {
        summary += ' Filtered by: ';
      }
    }

    return (
      <div className="l-row--no-columns u-vr__py--1 c-facet-search-summary">
        <div className="u-fw__medium" hidden={!hasResults || !searchState.query} dangerouslySetInnerHTML={{__html: summary}}/>
        <div className="u-fw__medium" hidden={hasResults}>
          There are no results for "<strong>{searchState.query}</strong>".
        </div>
      </div>
    );
  });
