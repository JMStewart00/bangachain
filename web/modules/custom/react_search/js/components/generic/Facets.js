import React from 'react';
import { connectRefinementList } from 'react-instantsearch-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import classNames from 'classnames';
import AnimateHeight from 'react-animate-height';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

class Facet extends React.Component {
  // set initial states
  state = {
    showFacetList: true,
    extended: false,
  }

  toggleExtended = (event) => {
    const { extended } = this.state;
    if (
      event.type === 'click' ||
      (event.type === 'keydown' && (event.key === " " || event.key === "Enter"))
    ) {
      event.preventDefault();
      this.setState({ extended: !extended })
    }
  }

  toggleShowFacetList = (event) => {
    const { showFacetList } = this.state;
    if (
      event.type === 'click' ||
      (event.type === 'keydown' && (event.key === " " || event.key === "Enter"))
    ) {
      event.preventDefault();
      this.setState({ showFacetList: !showFacetList })
    }
  }

  // decides whether to add 'Show More' Button
  renderShowMore() {
    const { showMore } = this.props;
    const { extended } = this.state;
    const disabled = this.props.limit >= this.props.items.length;

    if (!showMore || disabled) {
      return null;
    }

    return (
      <div
        onClick={(e) => {
          this.toggleExtended(e);
        }}
        onKeyDown={(e) => {
          this.toggleExtended(e);
        }}
        className="o-link"
        tabIndex="0"
      >
        {extended ? 'Show Less -' : 'Show More +'}
      </div>
    );
  }

  // helper for rendering the facet list to initial visibility
  getLimit = () => {
    const { limit, showMoreLimit } = this.props;
    const { extended } = this.state;
    return extended ? showMoreLimit : limit;
  };

  render() {
    // deconstruct this.state and provide any necessary defaults to
    const {
      showFacetList,
    } = this.state;

    // deconstruct this.props and provide any necessary defaults to
    const {
      items,
      label,
      classes = [],
    } = this.props;

    // construct the classes list based on a default class
    // and classes passed in as props
    let classList = classNames([
      "c-facet",
      ...classes
    ])

    // Set up the list items to animate in.
    const STAGGER = 100;
    const duration = { enter: 500, exit: 300 };
    const itemsList = items
      .slice(0, this.getLimit()) // decide how many items to show.
      .map((item, idx) => {
        return (
          <CSSTransition
            key={item.label}
            timeout={{
              enter: duration.enter + idx * STAGGER,
              exit: duration.exit
            }}
            classNames="c-facet__animate-"
          >
            <Item item={item} {...this.props} label={label} />
          </CSSTransition>
        )
      })

    if (items.length > 0) {
      return (
        <div className={classList}>

          <div
            className="c-facet__header"
            tabIndex="0"
            onClick={(e) => {
              this.toggleShowFacetList(e);
            }}
            onKeyDown={(e) => {
              this.toggleShowFacetList(e);
            }}
          >
            <div className="c-facet__label">
              <strong>{label.toUpperCase()}</strong>{' '}<span className="c-facet__count">({items.length})</span>
            </div>
            <div className="c-facet__icon">
              {showFacetList ? <FontAwesomeIcon icon={"minus"} aria-hidden /> : <FontAwesomeIcon icon={"plus"} aria-hidden />}
            </div>
          </div>

          <AnimateHeight
            duration={ 500 }
            height={ showFacetList ? 'auto' : 0 }
          >
            <div className="c-facet__content">
              <TransitionGroup className="c-facet__list o-list" component="ul">
                {itemsList}
              </TransitionGroup>
              {this.renderShowMore()}
            </div>
          </AnimateHeight>

        </div>
      );
    }
    return null
  }
}

// Individual List Item for facets rendering
const Item = ({ item, refine, label }) => (
  <li key={item.label} className="c-facet__item o-list__item">
    <div className="c-form__element">
      <input
        id={`${label}${item.label}`}
        type="checkbox"
        className="c-form__checkbox"
        onChange={() => {
          refine(item.value);
        }}
        checked={item.isRefined}
      />
      <label className="c-form__label c-form__label--checkbox" htmlFor={`${label}${item.label}`}>
        {item.label}{' '}<span className="c-facet__count">({item.count})</span>
      </label>
    </div>
  </li>
);

// main export that uses connectRefinementList to HOC from Algolia
export const CustomFacet = connectRefinementList(Facet);
