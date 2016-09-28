
import React from 'react';
import ReactDOM from 'react-dom';
import {
    FormControl,
    FormGroup,
    ControlLabel,
    InputGroup,
    Button,
    Alert
} from 'react-bootstrap';
const $ = require('jquery');
import LOGIN from './../lib/State.jsx';

export default class LoginForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = { passwd: '', error: false };
        this.submitForm = this.submitForm.bind(this);
    }

    submitForm(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/login/',
            method: 'post',
            data: JSON.stringify({ passwd: this.state.passwd })
        }).done(resp => {
            if (resp.emp && resp.reg) {
                this.props.morph({type: LOGIN, e: resp.emp, r: resp.reg});
            } else {
                this.setState({ error: 'Invalid login' });
            }
        }).fail((xhr, stat, err) => {
            this.setState({ error: 'Login error: ' + err });
        });
    }

    componentDidMount() {
        ReactDOM.findDOMNode(this.refs.loginField).focus();
    }

    render() {
        return (
            <form onSubmit={this.submitForm}>
                {this.state.error ? <Alert bsStyle="danger">{this.state.error}</Alert> : null}
                <FormGroup>
                    <ControlLabel>Enter password</ControlLabel>
                    <FormControl 
                        type="password" 
                        value={this.state.passwd}
                        onChange={ e => this.setState({passwd: e.target.value}) }
                        ref="loginField"
                    />
                </FormGroup>
                <FormGroup>
                    <Button type="submit" block={true}>Log In</Button>
                </FormGroup>
            </form>
        );
    }
}

