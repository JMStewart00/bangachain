import React from 'react';
import ReactDOM from 'react-dom';
import DiscSearch from './components/main_search/DiscSearch';
import { BrowserRouter as Router, Route } from 'react-router-dom';

ReactDOM.render(
  <Router>
    <Route path="/" component={DiscSearch} />
  </Router>,
  document.getElementById('discSearch')
);
