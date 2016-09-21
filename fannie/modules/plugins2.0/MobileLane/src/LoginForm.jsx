
import React from 'react';
import {
    FormControl,
    FormGroup,
    ControlLabel,
    InputGroup,
    Button
} from 'react-bootstrap';

export default class LoginForm extends React.Component {
    constructor(props) {
        super(props);
        this.state = { passwd: "" };
    }

    submitForm(e) {
        e.preventDefault();
        console.log(this.state.passwd);
    }

    render() {
        return (
            <form onSubmit={this.submitForm}>
                <FormGroup>
                    <ControlLabel>Enter password</ControlLabel>
                    <FormControl 
                        type="text" 
                        value={this.state.passwd}
                        onChange={ e => this.setState({passwd: e.target.value}) }
                    />
                </FormGroup>
                <FormGroup>
                    <Button type="submit" block={true}>Log In</Button>
                </FormGroup>
            </form>
        );
    }
}

