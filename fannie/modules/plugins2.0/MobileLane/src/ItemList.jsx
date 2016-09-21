
import React from 'react';
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

export default class ItemList extends React.Component {
    constructor(props) {
        super(props);
        this.state = { 
            items: [],
            upc: "",
            errors: ""
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
        console.log(this.state.upc);
    }

    render() {
        var ttl = this.state.items.reduce((c,i) => c + i.total);
        return (
            <form onSubmit={this.addItem}>
                {this.state.items.map(this.renderItem)}
                <Alert bsStyle="danger">{this.state.errors}</Alert>
                <Row>
                    <Col sm={7}>
                        <FormControl
                            type="number" min="1" max="9999999999999" step="1"
                            onChange={ e => this.setState({upc: e.target.value}) }
                            placeholder="Scan or key item"
                        />
                    </Col>
                    <Col sm={3} className="h3">{ttl}</Col>
                </Row>
                <Row>
                    <Col sm={3}>
                        <Button bsStyle="info">Add Item</Button>
                    </Col>
                    <Col sm={3}>
                        <Button bsStyle="success">Tender Out</Button>
                    </Col>
                    <Col sm={3}>
                        <Button bsStyle="warning">Menu</Button>
                    </Col>
                </Row>
            </form>
        );
    }
}

