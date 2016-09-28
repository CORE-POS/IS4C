
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

export default class MemberPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = { search: "", results: [], noResults: false }
        this.search = this.search.bind(this);
    }

    componentDidMount() {
        ReactDOM.findDOMNode(this.refs.mainInput).focus();
    }

    setMember(card, person) {
        $.ajax({
            url: 'api/member/',
            method: 'post',
            data: JSON.stringify({cardNo: card, personNum: person, e: this.props.s.emp, r: this.props.s.reg})
        }).done(resp => {
            this.props.morph({type: MEMBER, value: card});
            this.props.morph({type: NAVIGATE, value: 'tender'});
        });
    }

    search(ev) {
        ev.preventDefault();
        $.ajax({
            url: 'api/member/',
            method: 'get',
            data: 'term='+this.state.search
        }).done(resp => {
            const empty = resp.members.length == 0 ? true : false;
            this.setState({results: resp.members, noResults: empty});
        });
    }

    render() {
        return (
            <form onsubmit={this.search}>
                {results.map(i => {
                    <p>
                        <a className="h3" 
                            onClick={() => this.setMember(i.cardNo, i.personNum)}>
                            {i.cardNo} {i.name}
                        </a>
                    </p>
                })}
                {noResults ? <Alert bsStyle="danger">No matches</Alert> : null} 
                <FormGroup>
                    <ControlLabel>Member # or name</ControlLabel>
                    <FormControl type="text" ref="mainInput"
                        placeholder="Enter last name or member number"
                        value={this.state.search}
                        onChange={(e) => this.setState({amount: e.target.value})} />
                </FormGroup>
                <FormGroup>
                    <Button bsStyle="success" block={true} type="submit">Search</Button>
                </FormGroup>
                <FormGroup>
                    <Button block={true} onClick={() => this.props.morph({type: NAVIGATE, value: 'items'})}>Go Back </Button>
                </FormGroup>
            </form>
        );
    }
}

