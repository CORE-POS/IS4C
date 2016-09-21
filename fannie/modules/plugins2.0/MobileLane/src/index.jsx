import React from 'react';
import ReactDOM from 'react-dom';
import Bootstrap from 'bootstrap/dist/css/bootstrap.css';

import LoginForm from './LoginForm.jsx';
import ItemList from './ItemList.jsx';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            loggedIn: false,
            emp: 0,
            reg: 0
        };
    }

    login(e, r) {
        this.setState({
            loggedIn: true,
            emp: e,
            reg: r
        });
    }

    logout() {
        this.setState({
            loggedIn: false,
            emp: 0,
            reg: 0
        });
    }

    render() {
        var content;
        if (this.state.loggedIn) {
            content = <LoginForm doLogin={login.bind(this)} />;
        } else {
            content = <ItemList empNo={this.state.emp} registerNo={this.state.reg} />;
        }

        return content;
    }
}

ReactDOM.render(<App />, document.getElementById('app-main'));

