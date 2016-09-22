import React from 'react';
import ReactDOM from 'react-dom';
import Bootstrap from 'bootstrap/dist/css/bootstrap.css';

import LoginForm from './LoginForm.jsx';
import ItemList from './ItemList.jsx';
import MenuPage from './MenuPage.jsx';
import MemberPage from './MemberPage.jsx';
import TenderPage from './TenderPage.jsx';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            loggedIn: true,
            emp: 0,
            reg: 0,
            nav: 'items',
            member: false
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

    navigate(n) {
        this.setState({nav: n});
    }

    setMember(m) {
        this.setState({member: m});
    }

    render() {
        var content;
        if (!this.state.loggedIn) {
            content = <LoginForm doLogin={this.login.bind(this)} />;
        } else {
            switch (this.state.nav) {
                case 'member':
                    content = <MemberPage 
                                    empNo={this.state.emp} 
                                    registerNo={this.state.reg} 
                                    nav={this.navigate.bind(this)}
                                    mem={this.setMember.bind(this)}
                                />
                    break; 
                case 'tender':
                    content = <TenderPage 
                                    empNo={this.state.emp} 
                                    registerNo={this.state.reg} 
                                    nav={this.navigate.bind(this)}
                                    mem={this.setMember.bind(this)}
                                />
                    break; 
                case 'menu':
                    content = <MenuPage 
                                    empNo={this.state.emp} 
                                    registerNo={this.state.reg} 
                                    nav={this.navigate.bind(this)}
                                    doLogout={this.logout.bind(this)}
                                />
                    break; 
                case 'items':
                default:
                    content = <ItemList 
                                    empNo={this.state.emp} 
                                    registerNo={this.state.reg} 
                                    memNo={this.state.member} 
                                    nav={this.navigate.bind(this)}
                                />
                    break;
            }
        }

        return content;
    }
}

export default function startReact() {
    ReactDOM.render(<App />, document.getElementById('app-main'));
}


