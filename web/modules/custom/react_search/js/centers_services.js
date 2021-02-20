import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import CentersServicesSearch from './components/centers_services/CentersServicesSearch';

ReactDOM.render(
  <Router>
    <Route path="/" component={CentersServicesSearch} />
  </Router>,
  document.getElementById('centersServicesSearch')
);
