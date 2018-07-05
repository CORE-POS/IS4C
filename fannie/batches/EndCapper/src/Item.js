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
            isLine: this.props.isLine,
            width: this.props.width
        };
    }

    getClass(width) {
        switch (width) {
            case 1:
                return 'col-sm-3';
            case 2:
                return 'col-sm-4';
            case 3:
                return 'col-sm-6';
            default:
                return 'col-sm-12';
        }
    }

    render() {
        let mode = !this.state.isLine ? 'Item' : 'Product Line';
        let modeClass = !this.state.isLine ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-info';
        let widthClass = this.getClass(this.state.width);
        return this.props.connectDragSource(
            <div style={{border: "solid 1px black", display: "inline" }} className={widthClass}
                title={this.props.upc}>
                <p>{this.props.name}</p>
                <p className="small"><button className={modeClass} onClick={() => {
                    this.props.manageItem.toggle(this.props.id);
                    // shouldn't be necessary but react doesn't flow
                    // the changes from toggle down correctly...
                    this.setState({ isLine: !this.state.isLine });
                }}>
                    {mode}
                </button></p>
                <p>
                    <button className="btn btn-success btn-sm pull-left"
                        onClick={() => {
                            var w = this.props.manageItem.widen(this.props.id, -1);
                            if (w) {
                                this.setState({width: w});
                            }
                        }}>
                        <span className="glyphicon glyphicon-resize-small"></span>
                    </button>
                    <button className="btn btn-success btn-sm pull-right"
                        onClick={() => {
                            var w = this.props.manageItem.widen(this.props.id, 1);
                            if (w) {
                                this.setState({width: w});
                            }
                        }}>
                        <span className="glyphicon glyphicon-resize-full"></span>
                    </button>
                    <button className="btn btn-danger btn-sm"
                        onClick={() => this.props.manageItem.trash(this.props.id)}>
                        <span className="glyphicon glyphicon-trash" title="Delete this item"></span>
                    </button>
                </p>
            </div>);
    }

}

Item.propTypes = {
    connectDragSource: PropTypes.func.isRequired,
    isDragging: PropTypes.bool.isRequired
};

export default DragSource('ITEM', itemSource, collect)(Item);

