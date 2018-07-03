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
            isLine: this.props.isLine
        };
    }

    render() {
        let mode = !this.state.isLine ? 'Item' : 'Product Line';
        return this.props.connectDragSource(
            <div style={{border: "solid 1px black", display: "inline" }} className="col-sm-3">
                <p>{this.state.name}</p>
                <p className="small" onClick={() => {
                    this.props.toggle(this.state.id);
                    // shouldn't be necessary but react doesn't flow
                    // the changes from toggle down correctly...
                    this.setState({ isLine: !this.state.isLine });
                }}>
                    {mode}
                </p>
            </div>);
    }

}

Item.propTypes = {
    connectDragSource: PropTypes.func.isRequired,
    isDragging: PropTypes.bool.isRequired
};

export default DragSource('ITEM', itemSource, collect)(Item);

