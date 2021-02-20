import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import NewsEventsLanding from './components/news_events/NewsEventsLanding';

ReactDOM.render(
  <Router>
    <Route path="/" component={NewsEventsLanding} />
  </Router>,
  document.getElementById('newsEventsSearch')
);
