import React from 'react';
import { connectHits } from 'react-instantsearch-dom';

export const Hits = ({
  hits, // results
  Component, // Component passed in for rendering
  classes, // extra classes specific to these results
  highlightedBlock = false, // object from Drupal land
}) => {
  // loop through all results and use the passed in Component to render
  let renderedHits = hits.map((hit, idx) => (
    <article key={idx} className={classes}>
      <Component hit={hit} />
    </article>
  ))

  // if we get a block and we set show to true we want to inject
  // the block into the hits. TODO: Figure out how to put it on one page only.
  if (highlightedBlock) {
    // here we use the position to put the block in different spots.
    renderedHits = [
      ...renderedHits.slice(0, highlightedBlock.position),
      <HighlightedBlock key={'highlighted_block'} highlightedBlock={highlightedBlock} />,
      ...renderedHits.slice(highlightedBlock.position),
    ]
  }

  return (
    <div className="c-search-results">
      {renderedHits}
    </div>
  )
};

export const CustomHits = connectHits(Hits); // connect using Algolia's HOC to get all props needed

// create the highlighted block based on the info from drupal
const HighlightedBlock = ({ highlightedBlock }) => {
  const {
    image,
    link,
    title,
    content,
  } = highlightedBlock;

  return (
    <article className="c-search-result--drupal-block">
      <div className="t-background--brand-blue c-search-result__inner">

        {/* use a background image to help with scaling in the flex container. */}
        <div className="c-search-result__block_photo" style={{ backgroundImage: `url(${image})`}} />

        {/* display the title field and content with a link */}
        <div className="c-search-result__highlighted_content">
          <a href={link} className="o-link o-link--medium">
            <h3>{title}</h3>
          </a>
          <p>{content}</p>
        </div>

      </div>
    </article>
  )
}
