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
        this.manageItem = {
            toggle: (i) => this.toggleLine(i),
            move: (i, p) => this.moveItem(i, p),
            widen: (i, w) => this.setWidth(i, w),
            trash: (i) => this.deleteItem(i)
        };
        this.manageData = {
            reset: () => {
                clearTimeout(this.autoSaveToken);
                this.setState(this.defaultState);
            },
            load: (i) => this.load(i),
            save: (n) => this.save(n),
            start: (d) => this.setState({startDate: d}),
            end: (d) => this.setState({endDate: d}),
            canReport: () => this.state.permanentID
        }
        this.defaultState = {
            name: "",
            startDate: "",
            endDate: "",
            shelves: [],
            pen: [],
            permanentID: false,
            initID: false
        };
        this.state = this.defaultState;
        this.saving = false;
    };

    componentDidMount() {
        let initialize = document.getElementById('initializeEndCap');
        if (initialize) {
            this.setState({initID: initialize.value});
            this.load(initialize.value);
        }
    }

    load(id) {
        fetch('EndCapperPage.php?id=' + id)
        .then((res) => res.json())
        .then((res) => {
            if (res.state) {
                res.state.permanentID = id;
                this.setState(res.state);
            }
        });
    }

    /**
        React onChange works a little different than browser onchange
        and fires on every typed or deleted character.
    */
    save(newName) {
        this.setState({name: newName});
        if (!this.saving && newName.length > 0) {
            this.saving = true; 
            let body =  {...this.state, newName: newName };
            console.log(this.state);
            fetch('EndCapperPage.php', {
                method: 'post',
                body: JSON.stringify(body),
                headers: { 'Content-type': 'application/json' }
            }).then((res) => res.json())
            .then((res) => {
                if (res.saved) {
                    this.setState({ permanentID: res.id });
                    this.autoSaveToken = setTimeout(() => this.autoSaveLoop(), 15000);
                }
                this.saving = false;
            })
            .catch((err) => {
                this.saving=false
                console.log(err);
            });
        }
    } 

    init(num) {
        var shelves = [];
        for (var i=0; i < num; i++) {
            shelves.push([]);
        }
        this.setState({
            shelves: shelves
        });
    } 

    addToPen(name, upc) {
        let newPen = this.state.pen;
        newPen.push({
            id: uuidv4(),
            name: name,
            upc: upc,
            isLine: false,
            width: 4
        });
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
            let newPen = [...this.state.pen];
            newPen.splice(found.index, 1);
            this.setState({pen: newPen});
            this.forceUpdate();
            return ret;
        } else if (found.area === 'shelves') {
            ret = this.state.shelves[found.tier][found.pos];
            let newShelf = [...this.state.shelves[found.tier]];
            newShelf.splice(found.pos, 1);
            let newShelves = [...this.state.shelves];
            newShelves[found.tier] = newShelf;
            this.setState({shelves: newShelves});
            this.forceUpdate();
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
                let newShelves = [...this.state.shelves];
                newShelves[found.tier][found.pos].isLine = !(newShelves[found.tier][found.pos].isLine);
                this.setState({shelves: newShelves});
            }
        }
    }

    widen(item, change) {
        let newWidth = item.width + change;
        if (newWidth >= 1 && newWidth <= 4) {
            return {...item, width: newWidth};
        }

        return item;
    }

    setWidth(id, change) {
        let found = this.findItem(id);
        if (found !== false) {
            if (found.area === 'pen') {
                let newPen = [...this.state.pen];
                newPen[found.index] = this.widen(newPen[found.index], change);
                this.setState({pen: newPen});
                return newPen[found.index].width;
            } else if (found.area === 'shelves') {
                let newShelves = [...this.state.shelves];
                newShelves[found.tier][found.pos] = this.widen(newShelves[found.tier][found.pos], change);
                this.setState({shelves: newShelves});
                return newShelves[found.tier][found.pos].width;
            }
        }

        return 0;
    }

    render() {
        return (
            <div id="ec-main" className="App container-fluid">
                <div className="row">
                    <div id="ec-canvas" className="col-sm-8">
                        <EndCap shelves={this.state.shelves} manageItem={this.manageItem} />
                    </div>
                    <div id="ec-tools" className="col-sm-3">
                        <ToolBar init={this.handleInit} add={this.penAdd}
                            items={this.state.pen} ecName={this.state.name} ecID={this.state.initID}
                            startDate={this.state.startDate} endDate={this.state.endDate}
                            manageData={this.manageData}
                            manageItem={this.manageItem} />
                    </div>
                </div>
            </div>
        );
    }
}

export default DragDropContext(HTML5Backend)(App);

