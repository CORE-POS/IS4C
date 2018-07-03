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

    deleteItem(id) {
        var ret = {};
        var i;
        for (i=0; i<this.state.pen.length; i++) {
            if (this.state.pen[i].id === id) {
                console.log("Found in pen");
                ret = this.state.pen[i];
                let newPen = this.state.pen;
                newPen.splice(i, 1);
                this.setState({pen: newPen});
                return ret;
            }
        }
        for (i=0; i<this.state.shelves.length; i++) {
            for (var j=0; j<this.state.shelves[i].length; j++) {
                if (this.state.shelves[i][j].id === id) {
                    console.log("Found in shelves");
                    ret = this.state.shelves[i][j];
                    let newShelf = this.state.shelves[i];
                    newShelf.splice(j, 1);
                    let newShelves = this.state.shelves;
                    newShelves[i] = newShelf;
                    this.setState({shelves: newShelves});
                    return ret;

                }
            }
        }

        console.log("Not found");

        return ret;
    }

    render() {
        return (
            <div id="ec-main" className="App container-fluid">
                <div className="row">
                    <div id="ec-canvas" className="col-sm-8">
                        <EndCap shelves={this.state.shelves} move={this.handleMove} />
                    </div>
                    <div id="ec-tools" className="col-sm-3">
                        <ToolBar init={this.handleInit} add={this.penAdd} items={this.state.pen} />
                    </div>
                </div>
            </div>
        );
    }
}

export default DragDropContext(HTML5Backend)(App);

