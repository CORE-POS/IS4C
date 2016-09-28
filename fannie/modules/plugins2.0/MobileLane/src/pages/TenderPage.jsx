
import React from 'react';
import ReactDOM from 'react-dom';
import {
    FormGroup,
    FormControl,
    InputGroup,
    ControlLabel,
    Button,
    Alert
} from 'react-bootstrap';
const $ = require('jquery');
import { MEMBER, NAVIGATE } from './../lib/State.jsx';

export default class TenderPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = { tenders: [], total: false, amount: false, type: false }
        this.doTender = this.doTender.bind(this);
    }

    componentDidMount() {
        const ttl = this.props.s.items.reduce((c,i) => c + i.total, 0);
        this.setState({total: ttl});
        $.ajax({
            url: 'api/tenders/',
            method: 'get',
        }).done(resp => this.setState({tenders: resp.tenders}));
    }

    doTender() {
        $.ajax({
            url: 'api/tender/',
            type: 'post',
            data: JSON.stringify({type: this.state.type, amt: this.state.amt, e: this.props.s.emp, r: this.props.s.reg})
        }).done(resp => {
            if (resp.ended) {
                this.props.morph({type: MEMBER, value: false});
            }
            this.props.morph({type: NAVIGATE, value: 'items'}); 
        });
    }

    render() {
        return (
            <form onSubmit={this.doTender}>
                <h3>Amount due: ${this.state.total}</h3>
                <FormGroup>
                    <ControlLabel>Tender as</ControlLabel>
                    <FormControl componentClass="select" onChange={e=>this.setState({type: e.target.value})}>
                        {tenders.map(t => <option value={t.code}>t.name</option>)}
                    </FormControl>
                </FormGroup>
                <FormGroup>
                    <InputGroup>
                        <InputGroup.Addon>$</InputGroup.Addon>
                        <FormControl type="number" min="0.01" max={this.state.total} step="0.01"
                            value={this.state.amount}
                            onChange={(e) => this.setState({amount: e.target.value})} />
                    </InputGroup>
                </FormGroup>
                <FormGroup>
                    <Button bsStyle="success" block={true} type="submit">Enter Tender</Button>
                </FormGroup>
                <FormGroup>
                    <Button block={true} onClick={() => this.props.morph({type: NAVIGATE, value: 'items'})}>Go Back </Button>
                </FormGroup>
            </form>
        );
    }
}

