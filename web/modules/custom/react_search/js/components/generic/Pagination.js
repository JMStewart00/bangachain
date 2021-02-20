import React, { Component } from 'react';
import { connectPagination } from 'react-instantsearch-dom';
import PaginationLinks from './PaginationLinks';
import { range, capitalize } from '../../utils/helpers';

// Determines the size of the widget (the number of pages displayed - that the user can directly click on)
function calculateSize(padding, maxPages) {
    return Math.min(2 * padding + 1, maxPages);
}

function calculatePaddingLeft(currentPage, padding, maxPages, size) {
    if (currentPage <= padding) { return currentPage; }
    if (currentPage >= maxPages - padding) { return size - (maxPages - currentPage); }
    return padding + 1;
}

// Retrieve the correct page range to populate the widget
function getPages(currentPage, maxPages, padding) {
    const size = calculateSize(padding, maxPages);
    // If the widget size is equal to the max number of pages, return the entire page range
    if (size === maxPages) return range({ start: 1, end: maxPages + 1 });

    const paddingLeft = calculatePaddingLeft( currentPage, padding, maxPages, size );
    const paddingRight = size - paddingLeft;

    const first = currentPage - paddingLeft;
    const last = currentPage + paddingRight;

    return range({ start: first + 1, end: last + 1 });
}

class Pagination extends Component {
    static defaultProps = {
        listComponent: PaginationLinks,
        showFirst: false,
        showPrevious: true,
        showNext: true,
        showLast: false,
        padding: 3,
        totalPages: Infinity,
        classNames: '',
    };

    render() {
        const {
            listComponent: PaginationLinks,
            nbPages,
            totalPages,
            currentRefinement,
            padding,
            showFirst,
            showPrevious,
            showNext,
            showLast,
            refine,
            createURL,
        } = this.props;

        const maxPages = Math.min(nbPages, totalPages);
        const lastPage = maxPages;

        let items = [];
        if (showFirst) {
            items.push({
                key: 'first',
                modifier: 'item--firstPage',
                disabled: currentRefinement === 1,
                label: 'FIRST',
                value: 1,
                ariaLabel: 'ariaFirst',
            });
        }
        if (showPrevious) {
            items.push({
                key: 'previous',
                modifier: 'item--previousPage',
                disabled: currentRefinement === 1,
                label: 'PREV',
                value: currentRefinement - 1,
                ariaLabel: 'ariaPrevious',
            });
        }

        items = items.concat(
            getPages(currentRefinement, maxPages, padding).map(value => ({
                key: value,
                modifier: 'item--page',
                value,
                selected: value === currentRefinement,
                ariaLabel: 'ariaPage', value,
            }))
        );
        if (showNext) {
            items.push({
                key: 'next',
                modifier: 'item--nextPage',
                disabled: currentRefinement === lastPage || lastPage <= 1,
                label: 'NEXT',
                value: currentRefinement + 1,
                ariaLabel: 'ariaNext',
            });
        }
        if (showLast) {
            items.push({
                key: 'last',
                modifier: 'item--lastPage',
                disabled: currentRefinement === lastPage || lastPage <= 1,
                label: 'LAST',
                value: lastPage,
                ariaLabel: 'ariaLast',
            });
        }

        // hide pagination on for single page
        if (items.length <= 3) return null;

        return (
            <div className={'l-row--no-columns c-pagination u-flex-center u-vr__mt--2'}>
                <PaginationLinks
                    ulClass={'o-list-inline'}
                    liClass={'o-list-inline__item'}
                    items={items}
                    onSelect={refine}
                    createURL={createURL}
                    refine={refine}
                />
            </div>
        );
    }
}

export const CustomPagination = connectPagination(Pagination);
