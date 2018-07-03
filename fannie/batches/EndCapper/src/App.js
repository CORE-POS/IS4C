import React, { Component } from 'react';
import { DragDropContext } from 'react-dnd';
import HTML5Backend from 'react-dnd-html5-backend';
import './App.css';
import EndCap from './EndCap.js';
import ToolBar from './ToolBar.js';
const uuidv4 = require('uuid/v4');

class App extends Component {

    constructor(props) {
        super(props);
        this.handleInit = (i) => this.init(i);
        this.penAdd = (n, u) => this.addToPen(n, u);
        this.handleMove = (i, p) => this.moveItem(i, p);
        this.handleToggle = (i) => this.toggleLine(i);
        this.state = {
            shelves: [],
            pen: [],
            saved: false
        };
    };

    init(num) {
        var shelves = [];
        for (var i=0; i < num; i++) {
            shelves.push([]);
        }
        this.setState({
            shelves: shelves,
            saved: false
        });
    } 

    addToPen(name, upc) {
        let newPen = this.state.pen;
        newPen.push({ id: uuidv4(), name: name, upc: upc, isLine: false });
        this.setState({pen: newPen});
    }

    moveItem(id, pos) {
        console.log("Move item " + id + " to shelf " + pos);
        let item = this.deleteItem(id);
        if (pos === -1) {
            let newPen = this.state.pen;
            newPen.push(item);
            this.setState({pen: newPen});
        } else {
            let newShelf = this.state.shelves[pos];
            newShelf.push(item);
            let newShelves = this.state.shelves;
            newShelves[pos] = newShelf;
            this.setState({shelves: newShelves});
        }
    }

    findItem(id) {
        var i;
        for (i=0; i<this.state.pen.length; i++) {
            if (this.state.pen[i].id === id) {
                return { area: 'pen', index: i };
            }
        }

        for (i=0; i<this.state.shelves.length; i++) {
            for (var j=0; j<this.state.shelves[i].length; j++) {
                if (this.state.shelves[i][j].id === id) {
                    return { area: 'shelves', tier: i, pos: j };
                }
            }
        }

        return false;
    }

    deleteItem(id) {
        let found = this.findItem(id);
        var ret = {};
        if (found === false) {
            return ret;
        }

        if (found.area === 'pen') {
            ret = this.state.pen[found.index];
            let newPen = this.state.pen;
            newPen.splice(found.index, 1);
            this.setState({pen: newPen});
            return ret;
        } else if (found.area === 'shelves') {
            ret = this.state.shelves[found.tier][found.pos];
            let newShelf = this.state.shelves[found.tier];
            newShelf.splice(found.pos, 1);
            let newShelves = this.state.shelves;
            newShelves[found.tier] = newShelf;
            this.setState({shelves: newShelves});
            return ret;
        }

        return ret;
    }

    toggleLine(id) {
        let found = this.findItem(id);
        if (found !== false) {
            if (found.area === 'pen') {
                let newPen = [...this.state.pen];
                newPen[found.index] = { ...newPen[found.index], isLine: !(newPen[found.index].isLine) };
                this.setState({pen: newPen});
            } else if (found.area === 'shelves') {
                let newShelves = this.state.shelves;
                newShelves[found.tier][found.pos].isLine = !(newShelves[found.tier][found.pos].isLine);
                this.setState({shelves: newShelves});
            }
        }
    }

    render() {
        return (
            <div id="ec-main" className="App container-fluid">
                <div className="row">
                    <div id="ec-canvas" className="col-sm-8">
                        <EndCap shelves={this.state.shelves} move={this.handleMove} 
                            toggle={this.handleToggle} />
                    </div>
                    <div id="ec-tools" className="col-sm-3">
                        <ToolBar init={this.handleInit} add={this.penAdd}
                            move={this.handleMove} items={this.state.pen} 
                            toggle={this.handleToggle} />
                    </div>
                </div>
            </div>
        );
    }
}

export default DragDropContext(HTML5Backend)(App);

