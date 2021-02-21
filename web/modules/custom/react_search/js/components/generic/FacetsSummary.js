import React from 'react';
import { connectCurrentRefinements } from 'react-instantsearch-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';


// the button that clears all search queries, export for use elsewhere, too
export const ClearRefinements = connectCurrentRefinements(({ items, refine }) => {
  return (
    <React.Fragment>

      {/* No matter if items or not, still render a placeholder */}
      {items.length ? (
        <span
          className="o-link c-facet-summary__filter-clear"
          tabIndex="0"
          onClick={() => refine(items)}
          onKeyDown={(event) => {
            if (event.type === 'keydown' && (event.key === " " || event.key === "Enter")) {
              refine(items);
            }
          }}
        >
          Clear Filters
        </span>
      ) : <span></span>
      }

    </React.Fragment>
  )
});

export const FacetsSummary = connectCurrentRefinements(({ items, refine }) => {
  return (
    <React.Fragment>
      {items.length > 0 &&
        <div className="l-row--no-columns c-facet-summary">
          <ul className="o-list-inline">

            {/* Loop through all the enabled facets */}
            {
              items.map((facet, i) => {
                const { items = [] } = facet;
                return (
                  <React.Fragment key={i}>

                    {/* Loop through each currentRefinement nested inside a facet to output current filters */}
                    {items.map(nested => (
                      <li
                        key={nested.label}
                        className="c-facet-summary__item o-list-inline__item"
                        tabIndex="0"
                        onClick={event => {
                          refine(nested.value);
                        }}
                        onKeyDown={event => {
                          if (event.type === 'keydown' && (event.key === " " || event.key === "Enter")) {
                            refine(nested.value);
                          }
                        }}
                      >
                        {`${facet.label[0].toUpperCase()}${facet.label.substring(1)}`} {nested.label}{' '}<FontAwesomeIcon icon={"times-circle"} aria-hidden />
                      </li>
                    ))}

                  </React.Fragment>
                )
              })
            }

          </ul>
          <ClearRefinements clearsQuery />
        </div>
      }
    </React.Fragment>
  )
});

