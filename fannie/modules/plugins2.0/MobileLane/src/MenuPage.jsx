
import React from 'react';
import ReactDOM from 'react-dom';
import {
    Button,
    Alert
} from 'react-bootstrap';
var $ = require('jquery');

export default class MenuForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = { msg: false, msgStyle: 'danger', emptyTrans: false};
    }

    componentDidMount() {
        $.ajax({
            url: 'api/item/',
            method: 'get',
            data: 'e='+this.props.empNo+'&r='+this.props.registerNo
        }).done(resp => {
            if (resp.items.length == 0) {
                this.setState({emptyTrans: true});
            }
        });
    }

    api(url, verb) {
        $.ajax({
            url: url,
            method: 'post',
            data: JSON.stringify({e: this.props.empNo, r: this.props.registerNo})
        }).fail((xhr, stat, err) => {
            this.setState({msg: 'Error ' + verb + 'ing transaction', msgStyle: 'danger'});
        }).done(resp => {
            if (resp.error) {
                this.setState({msg: resp.error, msgStyle: 'danger'});
            } else {
                this.setState({msg: 'Transaction ' + verb + 'ed', msgStyle: 'success'});
            }
        }); 
    }

    logout() {
        $.ajax({
            url: 'api/logout/',
            method: 'post',
            data: JSON.stringify({e: this.props.empNo})
        }).fail((xhr, stat, err) => {
            this.setState({msg: "Failed to sing out", msgStyle: "danger"});
        }).done(resp => {
            this.props.doLogout();
        });
    }

    render() {
        return (
            <div>
                {this.state.msg ? <Alert bsStyle={this.state.msgStyle}>{this.state.msg}</Alert> : null}
                <p>
                    <Button block={true} onClick={() => this.props.nav('items')}>Go Back</Button>
                </p>
                <p>
                    <Button block={true} onClick={() => this.api('api/cancel/', 'cancell')} bsStyle="danger">Cancel Transaction</Button>
                </p>
                <p>
                    <Button block={true} onClick={() => this.api('api/suspend/', 'suspend')} bsStyle="warning">Suspend Transaction</Button>
                </p>
                { this.state.emptyTrans ?
                <p>
                    <Button block={true} onClick={() => this.logout()} bsStyle="info">Sign Out</Button>
                </p>
                : null } 
            </div>
        );
    }
}

