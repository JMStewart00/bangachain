import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import LocationSearch from './components/location_search/LocationSearch';

ReactDOM.render(
  <Router>
    <Route path="/" component={LocationSearch} />
  </Router>,
  document.getElementById('locationSearch')
);
