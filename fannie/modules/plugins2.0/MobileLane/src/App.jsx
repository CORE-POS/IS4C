import React from 'react';
import ReactDOM from 'react-dom';
import Bootstrap from 'bootstrap/dist/css/bootstrap.css';

import LoginForm from './pages/LoginForm.jsx';
import ItemList from './pages/ItemList.jsx';
import MenuPage from './pages/MenuPage.jsx';
import MemberPage from './pages/MemberPage.jsx';
import TenderPage from './pages/TenderPage.jsx';
import * as State from './lib/State.jsx';

export default class App extends React.Component {

    constructor(props) {
        super(props);
        this.state = State.initialState();
        this.morph = this.morph.bind(this);
    }

    morph(action) {
        this.setState(State.nextState(this.state, action));
    }

    render() {
        let content = null;
        if (!this.state.loggedIn) {
            content = <LoginForm morph={this.morph} />;
        } else {
            switch (this.state.nav) {
                case 'member':
                    content = <MemberPage s={this.state} morph={this.morph} />
                    break; 
                case 'tender':
                    content = <TenderPage s={this.state} morph={this.morph} />
                    break; 
                case 'menu':
                    content = <MenuPage s={this.state} morph={this.morph} />
                    break; 
                case 'items':
                default:
                    content = <ItemList s={this.state} morph={this.morph} />
                    break;
            }
        }

        return content;
    }
}

