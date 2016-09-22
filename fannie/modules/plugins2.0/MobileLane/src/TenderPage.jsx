
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
var $ = require('jquery');

export default class TenderPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = { tenders: [], total: false, amount: false, type: false }
    }

    componentDidMount() {
        $.ajax({
            url: 'api/tenders/',
            method: 'get',
        }).done(resp => this.setState({tenders: resp.tenders}));
        $.ajax({
            url: 'api/item/',
            type: 'get',
            data: 'e='+this.props.empNo+'&r='+this.props.registerNo
        }).done(resp => {
            var ttl = resp.items.reduce((c,i) => c + i.total, 0);
            this.setState({total: ttl});
        });
    }

    doTender() {
        $.ajax({
            url: 'api/tender/',
            type: 'post',
            data: JSON.stringify({type: this.state.type, amt: this.state.amt, e: this.props.empNo, r: this.props.registerNo})
        }).done(resp => {
            if (resp.ended) {
                this.props.mem(false);
            }
            this.props.nav('items'); 
        });
    }

    render() {
        return (
            <form onSubmit={this.doTender.bind(this)}>
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
                    <Button block={true} onClick={() => this.props.nav('items')}>Go Back </Button>
                </FormGroup>
            </form>
        );
    }
}

