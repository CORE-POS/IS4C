import React, { Component } from 'react';
import Shelf from './Shelf.js';

class EndCap extends Component {

    render() {
        let all = this.props.shelves.map((s, i) =>
            <Shelf items={s} pos={i} 
                manageItem={this.props.manageItem} />
        );
        return (<p><div>{all}</div></p>);
    }
}

export default EndCap;

