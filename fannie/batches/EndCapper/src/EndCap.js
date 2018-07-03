import React, { Component } from 'react';
import Shelf from './Shelf.js';

class EndCap extends Component {

    render() {
        let all = this.props.shelves.map((s, i) =>
            <Shelf move={this.props.move} items={s} pos={i} 
                toggle={this.props.toggle} />
        );
        return (<p><div>{all}</div></p>);
    }
}

export default EndCap;

