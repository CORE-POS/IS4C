
import React from 'react';
import ReactDOM from 'react-dom';
import {
    FormControl,
    FormGroup,
    ControlLabel,
    InputGroup,
    Button,
    Col,
    Row,
    Alert
} from 'react-bootstrap';
import enableScanner from './../lib/Devices.jsx';
import { NAVIGATE, ADDITEM, SETITEMS } from './../lib/State.jsx';
const $ = require('jquery');

export default class ItemList extends React.Component {
    constructor(props) {
        super(props);
        this.state = { 
            upc: '',
            errors: false 
        };
        this.addItem = this.addItem.bind(this);
    }

    renderItem(i) {
        return (
            <Row>
                <Col sm={7}>{i.description}</Col>
                <Col sm={3}>{i.total}</Col>
                <Col sm={2}>
                    <Button bsClass="danger" onClick={() => this.voidItem(i.id)}>[Void]</Button>
                </Col>
            </Row>
        );
    }

    addItem(e) {
        e.preventDefault();
        this.postItem(this.state.upc);
    }

    voidItem(id) {
        $.ajax({
            url: 'api/void/',
            method: 'post',
            data: JSON.stringify({id: id})
        }).fail((xhr, stat, err) => {
            this.setState({errors: 'Error voiding item'});
        }).done(resp => {
            if (resp.error) {
                this.setState({errors: resp.error});
            } else {
                this.props.morph({type: ADDITEM, value: resp.item});
            }
        });
    }

    postItem(upc) {
        $.ajax({
            url: 'api/item/',
            type: 'post',
            data: JSON.stringify({upc: upc, r: this.props.s.reg, e: this.props.s.emp})
        }).fail((xhr,stat,err) => {
            this.setState({errors: 'Error adding item'});
        }).done(resp => {
            if (resp.error) {
                this.setState({errors: resp.error});
            } else {
                this.props.morph({type: ADDITEM, value: resp.item});
                this.setState({errors: false, upc: ''});
            }
        });
    }

    componentDidMount() {
        ReactDOM.findDOMNode(this.refs.itemField).focus();
        enableScanner(this.postItem.bind(this));
        $.ajax({
            url: 'api/item/',
            type: 'get',
            data: `e=${this.props.s.emp}&r=${this.props.s.reg}`
        }).fail((xhr,stat,err) => {
            this.setState({errors: 'Error retreiving items'});
        }).done(resp => {
            if (resp.error) {
                this.setState(errors: resp.error);
            } else {
                this.props.morph({type: SETITEMS, value: resp.items});
                this.setState({errors: false});
            }
        });
    }

    render() {
        const ttl = this.props.s.items.reduce((c,i) => c + i.total, 0);
        return (
            <form onSubmit={this.addItem}>
                {this.props.s.items.map(this.renderItem)}
                {this.state.errors ? <Alert bsStyle="danger">{this.state.errors}</Alert> : null}
                <Row>
                    <Col sm={7}>
                        <FormControl
                            type="number" min="1" max="9999999999999" step="1"
                            onChange={ e => this.setState({upc: e.target.value}) }
                            placeholder="Scan or key item"
                            ref="itemField"
                        />
                    </Col>
                    <Col sm={3} className="h3">{ttl}</Col>
                </Row>
                <Row>
                    <Col sm={3}>
                        <Button type="submit" bsStyle="info">Add Item</Button>
                    </Col>
                    <Col sm={3}>
                        <Button 
                            onClick={() => this.props.s.member ? this.props.morph({type: NAVIGATE, value: 'tender'}) : this.props.morph({type: NAVIGATE, value: 'member'})} 
                            bsStyle="success">
                            Tender Out
                        </Button>
                    </Col>
                    <Col sm={3}>
                        <Button onClick={() => this.props.morph({type: NAVIGATE, value: 'menu'})} bsStyle="warning">Menu</Button>
                    </Col>
                </Row>
            </form>
        );
    }
}

