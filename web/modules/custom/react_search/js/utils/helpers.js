import qs from 'qs';

/*
The variables/functions in this file are meant
to be helpers throughout the react apps to cut down
on repeated code.

Contents:
- range: create a range
- capitalize: capitalize a string
- toArray: convert a variable to an array
- createURL: stringifies state into query params
- searchStateToURL: creates full URL, utilizing createURL
- urlToSearchState: Allows searchState to be derived from query strings
- createMapLink: converts address to google maps link
*/

// taken from InstantSearch.js/utils
export function range({ start = 0, end, step = 1 }) {
    // We can't divide by 0 so we re-assign the step to 1 if it happens.
    const limitStep = step === 0 ? 1 : step;

    // In some cases the array to create has a decimal length.
    // We therefore need to round the value.
    // Example:
    //   { start: 1, end: 5000, step: 500 }
    //   => Array length = (5000 - 1) / 500 = 9.998
    const arrayLength = Math.round((end - start) / limitStep);

    return [...Array(arrayLength)].map(
      (_, current) => (start + current) * limitStep
    );
}

// Capitalize a string
export const capitalize = (key) => key.length === 0 ? '' : `${key[0].toUpperCase()}${key.slice(1)}`;

// ensure the items provided are organized into an array
export const toArray = (items) => {
  if (Array.isArray(items)) {
    return items;
  }
  if (items === null) {
    return [];
  }
  return [items];
}

export const createURL = state => `?${qs.stringify(state)}`;

export const searchStateToURL = (props, searchState, createURL) =>
  searchState ? `${props.location.pathname}${createURL(searchState)}` : '';

export const urlToSearchState = location => qs.parse(location.search.slice(1));

export const createMapLink = (address_line1, address_line2, city, state, postal_code) => {
  const googleMapsLink = "https://www.google.com/maps/dir/?api=1&saddr=CurrentLocation";
  const destination = [address_line1, address_line2, city, state, postal_code].map((item) => item && item.replace(/\s/g, '+')).join(',');
  return `${googleMapsLink}&destination=${encodeURI(destination)}`;
}
