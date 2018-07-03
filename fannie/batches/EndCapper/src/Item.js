import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { DragSource } from 'react-dnd';

const itemSource = {
    beginDrag(props) {
        return {
            id: props.id
        };
    }
};

function collect(connect, monitor) {
    return {
        connectDragSource: connect.dragSource(),
        isDragging: monitor.isDragging()
    };
}

class Item extends Component {

    constructor(props) {
        super(props);
        this.state = {
            id: this.props.id,
            name: this.props.name,
            upc: this.props.upc,
            line: this.props.isLine
        };
    }

    render() {
        return this.props.connectDragSource(
            <div style={{border: "solid 1px black", display: "inline" }} className="col-sm-3">
                <span>{this.state.name}</span>
            </div>);
    }

}

Item.propTypes = {
    connectDragSource: PropTypes.func.isRequired,
    isDragging: PropTypes.bool.isRequired
};

export default DragSource('ITEM', itemSource, collect)(Item);

