import React from 'react';
import { connectSortBy } from 'react-instantsearch-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

export const SortBy = connectSortBy(({ items, refine, currentRefinement, createUrl }) => {
  return (
    <div className="l-box--md c-form__element">
      <select
        className="c-form__select"
        onChange={event => refine(event.currentTarget.value)}
      >
        {items.map(item => (
          <option key={item.value} value={item.value}>
            {`Sort by: ${item.label}`}
          </option>
        ))}
      </select>
      <FontAwesomeIcon icon={"caret-down"} aria-hidden />
    </div>
  )
});


