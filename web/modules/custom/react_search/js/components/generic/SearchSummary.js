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
          <p>There are no results for "<strong>{searchState.query}</strong>".</p>
          <p>Two things could cause this.</p>
          <ul>
            <li>(1) Your search query is too far off from what you meant (misspelled) or...</li>
            <li>(2) We're out of stock on that one and we didn't want it to show up here and get your hopes up.<br />If you really want this one, drop us a message on the Contact Us page and let us know that you're looking for it<br />and we'll try to get it in soon! You can always stop by the shop and see if we have it in the used bins as well!</li>
          </ul>
        </div>
      </div>
    );
  });
