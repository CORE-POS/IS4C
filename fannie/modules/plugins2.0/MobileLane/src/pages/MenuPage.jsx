
import React from 'react';
import ReactDOM from 'react-dom';
import {
    Button,
    Alert
} from 'react-bootstrap';
const $ = require('jquery');
import { LOGOUT, NAVIGATE } from './../lib/State.jsx';

export default class MenuForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = { msg: false, msgStyle: 'danger' };
    }

    api(url, verb) {
        $.ajax({
            url: url,
            method: 'post',
            data: JSON.stringify({e: this.props.s.emp, r: this.props.s.reg})
        }).fail((xhr, stat, err) => {
            this.setState({msg: `Error ${verb}ing transaction`, msgStyle: 'danger'});
        }).done(resp => {
            if (resp.error) {
                this.setState({msg: resp.error, msgStyle: 'danger'});
            } else {
                this.setState({msg: `Transaction ${verb}ed`, msgStyle: 'success'});
            }
        }); 
    }

    logout() {
        $.ajax({
            url: 'api/logout/',
            method: 'post',
            data: JSON.stringify({e: this.props.s.emp})
        }).fail((xhr, stat, err) => {
            this.setState({msg: 'Failed to sign out', msgStyle: 'danger'});
        }).done(resp => {
            this.props.morph({type: LOGOUT});
        });
    }

    render() {
        return (
            <div>
                {this.state.msg ? <Alert bsStyle={this.state.msgStyle}>{this.state.msg}</Alert> : null}
                <p>
                    <Button block={true} onClick={() => this.props.morph({type: NAVIGATE, value:'items'})}>Go Back</Button>
                </p>
                <p>
                    <Button block={true} onClick={() => this.api('api/cancel/', 'cancell')} bsStyle="danger">Cancel Transaction</Button>
                </p>
                <p>
                    <Button block={true} onClick={() => this.api('api/suspend/', 'suspend')} bsStyle="warning">Suspend Transaction</Button>
                </p>
                { this.props.s.items.length == 0 ?
                <p>
                    <Button block={true} onClick={() => this.logout()} bsStyle="info">Sign Out</Button>
                </p>
                : null } 
            </div>
        );
    }
}

