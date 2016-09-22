
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
import enableScanner from './Devices.jsx';
var $ = require('jquery');

export default class ItemList extends React.Component {
    constructor(props) {
        super(props);
        this.state = { 
            items: [],
            upc: "",
            errors: false 
        };
    }

    renderItem(i) {
        return (
            <Row>
                <Col sm={7}>{i.description}</Col>
                <Col sm={3}>{i.total}</Col>
                <Col sm={2}>[Void]</Col>
            </Row>
        );
    }

    addItem(e) {
        e.preventDefault();
        this.postItem(this.state.upc);
    }

    postItem(upc) {
        $.ajax({
            url: 'api/item/',
            type: 'post',
            data: JSON.stringify({upc: upc, r: this.props.registerNo, e: this.props.empNo})
        }).fail((xhr,stat,err) => {
            this.setState({errors: "Error adding item"});
        }).done(resp => {
            if (resp.error) {
                this.setState({errors: resp.error});
            } else {
                var newlist = this.state.items;
                newlist.push(resp.item);
                this.setState({items: newlist, errors: false, upc: ""});
            }
        });
    }

    componentDidMount() {
        ReactDOM.findDOMNode(this.refs.itemField).focus();
        enableScanner(this.postItem.bind(this));
        $.ajax({
            url: 'api/item/',
            type: 'get',
            data: 'e='+this.props.empNo+'&r='+this.props.registerNo
        }).fail((xhr,stat,err) => {
            this.setState({errors: 'Error retreiving items'});
        }).done(resp => {
            if (resp.error) {
                this.setState(errors: resp.error);
            } else {
                this.setState({items: resp.items, errors: false});
            }
        });
    }

    render() {
        var ttl = this.state.items.reduce((c,i) => c + i.total, 0);
        return (
            <form onSubmit={this.addItem.bind(this)}>
                {this.state.items.map(this.renderItem)}
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
                            onclick={() => this.props.memNo ? this.props.nav('tender') : this.props.nav('member')} 
                            bsStyle="success">
                            Tender Out
                        </Button>
                    </Col>
                    <Col sm={3}>
                        <Button onClick={() => this.props.nav('menu')} bsStyle="warning">Menu</Button>
                    </Col>
                </Row>
            </form>
        );
    }
}

