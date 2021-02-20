import React from 'react';
import ReactDOM from 'react-dom';
import MainSearch from './components/main_search/MainSearch';
import { BrowserRouter as Router, Route } from 'react-router-dom';

ReactDOM.render(
  <Router>
    <Route path="/" component={MainSearch} />
  </Router>,
  document.getElementById('mainSearch')
);
