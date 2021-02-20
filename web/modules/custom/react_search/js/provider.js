import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import ProviderSearch from './components/provider_search/ProviderSearch';

ReactDOM.render(
  <Router>
    <Route path="/" component={ProviderSearch} />
  </Router>,
  document.getElementById('providerSearch')
);
