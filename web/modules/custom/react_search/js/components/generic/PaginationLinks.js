import React, { Component } from 'react';

export default class PaginationLinks extends Component {
    render() {
        const { ulClass, liClass, createURL, items, refine } = this.props;
        return (
            <ul className={ulClass}>
                {items.map(item => {
                    let linkClass = 'o-link c-pagination__link';
                    if (item.disabled) linkClass += ' c-pagination__link--disabled';
                    if (item.selected) linkClass += ' c-pagination__link--selected';
                    return (
                        <li key={item.key} className={liClass} >
                            {!item.disabled ? (
                                <a
                                    className={linkClass}
                                    aria-label={item.ariaLabel}
                                    href={createURL(item.value)}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        refine(item.value);
                                    }}
                                >
                                    {item.label === undefined ? item.value : item.label}
                                </a>
                            ) : (
                                <span className={linkClass}>{item.label === undefined ? item.value : item.label}</span>
                            )}
                        </li>
                    )
                })
                }
            </ul>
        );
    }
}
