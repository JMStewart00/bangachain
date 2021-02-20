import React from 'react';
import { connectScrollTo } from 'react-instantsearch-dom';

class ScrollTo extends React.Component {
  componentDidUpdate(prevProps) {
    const { value, hasNotChanged } = this.props;

    if (value !== prevProps.value && hasNotChanged) {
      // scroll to the span but not too far.
      const element = document.getElementById('scrollTo');
      const y = element.getBoundingClientRect().top + window.pageYOffset + -30;
      window.scrollTo({top: y, behavior: 'smooth'});
    }
  }

  render() {
    return (
      <span id="scrollTo" ref={ref => (this.el = ref)}>
        {this.props.children}
      </span>
    );
  }
}

export const CustomScrollTo = connectScrollTo(ScrollTo);
