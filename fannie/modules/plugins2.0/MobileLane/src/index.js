import React from 'react';
import ReactDOM from 'react-dom';
import Bootstrap from 'bootstrap/dist/css/bootstrap.css';
import { Button } from 'react-bootstrap';

const buttonsInstance = (
  <Button>Click me!</Button>
);

ReactDOM.render(buttonsInstance, document.getElementById('app-main'));
