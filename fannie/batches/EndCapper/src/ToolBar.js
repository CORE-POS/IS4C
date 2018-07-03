import React, { Component } from 'react';
import AutoComplete from 'react-autocomplete';
import Item from './Item.js';

class ToolBar extends Component {

    constructor(props) {
        super(props);
        this.state = {
            numShelves: "",
            itemSearch: "",
            acItems: []
        };
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
                params: { field: 'item', search: v }
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

    render() {
        let items = this.props.items.map((i) => <Item id={i.id} name={i.name} upc={i.upc} isLine={i.isLine} />);
        return (
            <div>
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
                <p>
                <div id="item-pen">{items}</div>
                </p>
            </div>
        );
    }
}

export default ToolBar;

