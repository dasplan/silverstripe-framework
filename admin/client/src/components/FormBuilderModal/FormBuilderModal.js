import React from 'react';
import { Modal } from 'react-bootstrap-ss';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import FormBuilder from 'components/FormBuilder/FormBuilder';

class FormBuilderModal extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleSubmit = this.handleSubmit.bind(this);
    this.handleHide = this.handleHide.bind(this);
    this.clearResponse = this.clearResponse.bind(this);
  }

  getForm() {
    return (
      <FormBuilder
        schemaUrl={this.props.schemaUrl}
        handleSubmit={this.handleSubmit}
        handleAction={this.props.handleAction}
      />
    );
  }

  getResponse() {
    if (!this.state || !this.state.response) {
      return null;
    }

    let className = '';

    if (this.state.error) {
      className = this.props.responseClassBad || 'response error';
    } else {
      className = this.props.responseClassGood || 'response good';
    }

    return (
      <div className={className}>
        <div className="response-wrapper">
          <span>{this.state.response}</span>
        </div>
      </div>
    );
  }

  clearResponse() {
    this.setState({
      response: null,
    });
  }

  handleHide() {
    this.clearResponse();
    if (typeof this.props.handleHide === 'function') {
      this.props.handleHide();
    }
  }

  handleSubmit(event, fieldValues, submitFn) {
    let promise = null;
    if (typeof this.props.handleSubmit === 'function') {
      promise = this.props.handleSubmit(event, fieldValues, submitFn);
    } else {
      event.preventDefault();
      promise = submitFn();
    }

    if (promise) {
      promise
        .then((response) => {
          this.setState({
            response: response.message,
            error: false,
          });
          return response;
        })
        .catch((errorPromise) => {
          errorPromise.then((errorText) => {
            this.setState({
              response: errorText,
              error: true,
            });
          });
        });
    }
    return promise;
  }

  render() {
    const form = this.getForm();
    const response = this.getResponse();

    return (
      <Modal
        show={this.props.show}
        onHide={this.handleHide}
        className={this.props.className}
      >
        {this.props.title !== false &&
          <Modal.Header closeButton><Modal.Title>{this.props.title}</Modal.Title></Modal.Header>
        }
        <Modal.Body className={this.props.bodyClassName}>
          {response}
          {form}
          {this.props.children}
        </Modal.Body>
      </Modal>
    );
  }
}

FormBuilderModal.propTypes = {
  show: React.PropTypes.bool,
  title: React.PropTypes.string,
  className: React.PropTypes.string,
  bodyClassName: React.PropTypes.string,
  handleHide: React.PropTypes.func,
  schemaUrl: React.PropTypes.string,
  handleSubmit: React.PropTypes.func,
  handleAction: React.PropTypes.func,
  responseClassGood: React.PropTypes.string,
  responseClassBad: React.PropTypes.string,
};

FormBuilderModal.defaultProps = {
  show: false,
  title: null,
};

export default FormBuilderModal;
