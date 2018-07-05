
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import Item from './Item.js';

var moveItem = function(id, pos) {
};

const shelfTarget = {
    canDrop(props) {
        return true;
    },

    drop(props, monitor) {
        // move the item
        let item = monitor.getItem();
        moveItem(item.id, props.pos);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop()
    };
}

class Shelf extends Component {

    constructor(props) {
        super(props);
        moveItem = (id, pos) => this.props.manageItem.move(id, pos);
    }

    render() {
        let items = this.props.items.map((i) =>
            <Item key={i.id} {...i}
                manageItem={this.props.manageItem}
                toggle={this.props.manageItem.toggle} />
        );
        return this.props.connectDropTarget(
            <div className="shelf-wrapper">
                <div className="shelf-items">{items}</div>
            </div>
        );
    }
}

Shelf.propTypes = {
    connectDropTarget: PropTypes.func.isRequired,
    isOver: PropTypes.bool.isRequired,
    canDrop: PropTypes.bool.isRequired
};

export default DropTarget('ITEM', shelfTarget, collect)(Shelf);

