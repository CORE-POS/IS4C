import React, { Component } from 'react';
import AutoComplete from 'react-autocomplete';
import PropTypes from 'prop-types';
import { DropTarget } from 'react-dnd';
import Item from './Item.js';

var penItem = function(id) {
};

const penTarget = {
    canDrop(props) {
        return true;
    },

    drop(props, monitor) {
        // move the item
        let item = monitor.getItem();
        penItem(item.id);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop()
    };
}

class ToolBar extends Component {

    constructor(props) {
        super(props);
        this.state = {
            numShelves: "",
            itemSearch: "",
            startDate: "",
            endDate: "",
            loadList: [],
            acItems: []
        };
        penItem = (id) => this.props.manageItem.move(id, -1);
        this.listToken;
    }

    newEC() {
        this.props.init(this.state.numShelves);
    }

    addItem(name, upc) {
        this.props.add(name, upc);
        this.setState({itemSearch: "", acItems: []});
    }

    itemAutocomplete(ev, v) {
        this.setState({itemSearch: v});
        if (v.length > 2) {
            var req = {
                jsonrpc: '2.0',
                method: '\\COREPOS\\Fannie\\API\\webservices\\FannieAutoComplete',
                id: new Date().getTime(),
                params: { field: 'item', search: v, wide: true }
            };
            fetch('../../ws/', {
                method: 'post',
                body: JSON.stringify(req),
                headers: { 'Content-type': 'application/json' }
            }).then((res) => res.json())
            .then((res) => {
                if (res.result) {
                    this.setState({ acItems: res.result });
                }
            });
        }
    }

    getEcList() {
        fetch('EndCapperPage.php?all=1')
        .then((res) => res.json())
        .then((res) => this.setState({loadList: res}));
    }

    saveWrapper(name) {
        this.props.manageData.save(name);
/*
        if (this.listToken) {
            clearTimeout(this.listToken);
        }
        this.listToken = setTimeout(() => this.getEcList(), 3000);
*/
    }

    componentDidMount() {
        this.getEcList();
    }

    render() {
        let items = this.props.items.map((i) =>
            <Item key={i.id} {...i}
                manageItem={this.props.manageItem}
                toggle={this.props.manageItem.toggle} />
        );
        let opts = this.state.loadList.map((i) => {
            if (i.id === this.props.ecID) {
                return (<option key={i.id} selected value={i.id}>{i.name}</option>);
            }
            return (<option key={i.id} value={i.id}>{i.name}</option>);
        });
        let reportID = this.props.manageData.canReport();
        var reportBtn = '';
        if (reportID) {
            let url = 'EndCapperReport.php?id='+reportID;
            reportBtn = (<p>
                    <a href={url} className="btn btn-default">Sales Report</a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <button type="btn" className="btn btn-default"
                        onClick={() => this.props.manageData.save(this.props.ecName)}>Save</button>
                </p>)
        }

        return this.props.connectDropTarget(
            <div>
                <p>
                    <div className="input-group">
                        <span className="input-group input-group-addon">Load</span>
                        <select className="form-control" onChange={(ev) => {
                            if (ev.target.value) {
                                this.props.manageData.load(ev.target.value);
                            } else{
                                this.props.manageData.reset();
                            }
                        }}>
                            <option value="">Select one...</option>
                            {opts}
                        </select>
                    </div>
                </p>
                <p>
                    <div className="input-group">
                        <span className="input-group input-group-addon">Name</span>
                        <input type="text" className="form-control" value={this.props.ecName}
                            onChange={(ev) => this.props.manageData.save(ev.target.value)} />
                    </div>
                </p>
                <p>
                    <div className="input-group">
                        <span className="input-group input-group-addon">Start Date</span>
                        <input type="text" className="form-control date-field" value={this.props.startDate}
                            onChange={(ev) => this.props.manageData.start(ev.target.value)} />
                    </div>
                </p>
                <p>
                    <div className="input-group">
                        <span className="input-group input-group-addon">End Date</span>
                        <input type="text" className="form-control date-field" value={this.props.endDate}
                            onChange={(ev) => this.props.manageData.end(ev.target.value)} />
                    </div>
                </p>
                {reportBtn}
                <p>
                <div className="form-inline">
                    <input type="number" className="form-control" value={this.state.numShelves} placeholder="# of shelves" 
                        onChange={(ev) => this.setState({numShelves: ev.target.value})} />
                    <button type="button" className="btn btn-default" onClick={() => this.newEC()}>New</button>
                </div>
                </p>
                <p>
                <div className="form-inline">
                    <AutoComplete inputProps={{className:"form-control", placeholder:"Item Name"}} items={this.state.acItems}
                        value={this.state.itemSearch} onChange={(ev, v) => this.itemAutocomplete(ev, v)} 
                        getItemValue={(item) => item.value } 
                        renderItem={(item, isHighlighted) =>
                            <div style={{ background: isHighlighted ? 'lightgray' : 'white' }}>
                              {item.label}
                            </div>
                        }
                        onSelect={(v, i) => this.addItem(i.label, i.value) }
                    />
                    <button type="button" className="btn btn-default"
                        onClick={() => this.addItem(this.state.itemSearch, '1234567890123')}>Add</button>
                </div>
                </p>
                <p><div style={{"min-height": "100px"}} id="item-pen">{items}</div></p>
            </div>
        );
    }
}

ToolBar.propTypes = {
    connectDropTarget: PropTypes.func.isRequired,
    isOver: PropTypes.bool.isRequired,
    canDrop: PropTypes.bool.isRequired
};

export default DropTarget('ITEM', penTarget, collect)(ToolBar);

