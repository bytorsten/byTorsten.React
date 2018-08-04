import React, { Component } from 'react';
import { bool, func, string, object, instanceOf } from 'prop-types';

import { FlowConsumer, FlowClient } from './Flow';

const formatResponse = response => {
  const { data, error: rawError } = response;
  let error = null;
  if (rawError) {
    error = new Error(rawError.message);
    error.stack = rawError.stack;
  }

  return { data, error };
};

class Rpc extends Component {

  static propTypes = {
    helper: string.isRequired,
    children: func.isRequired,
    client: instanceOf(FlowClient).isRequired,
    variables: object, //eslint-disable-line react/forbid-prop-types
    forceFetch: bool
  }

  static defaultProps = {
    variables: {},
    forceFetch: false
  }

  static getDerivedStateFromProps({ client, helper, variables }, prevState) {

    if (process.env.SSR) {
      return {
        shouldFetch: false
      };
    }

    const nextKey = client.getCacheKey({ helper, variables });
    if (nextKey !== prevState.key) {
      return {
        data: null,
        error: null,
        key: nextKey,
        loading: true,
        shouldFetch: true
      };
    }

    return null;
  }

  state = {
    key: null,
    loading: false,
    shouldFetch: false,
    data: null,
    error: null
  }

  componentDidMount() {
    this.fetchEndpoint();
  }

  componentDidUpdate() {
    this.fetchEndpoint();
  }

  async fetchEndpoint() {
    const { shouldFetch } = this.state;

    if (!shouldFetch) {
      return;
    }

    const { helper, variables, client, forceFetch } = this.props;
    this.setState({ shouldFetch: false });

    const response = await client.fetch({ helper, variables, cache: !forceFetch });
    this.setState({ ...formatResponse(response), loading: false });
  }

  fetchData() {
    const { client, helper, variables, forceFetch } = this.props;
    return client.fetch({ helper, variables, ssrOnly: forceFetch });
  }

  render() {
    const { children, helper, variables, client } = this.props;

    if (process.env.SSR) {
      const response = client.get({ helper, variables });
      if (!response) {
        return children({ data: null, error: null, loading: true });
      }

      return children({ ...formatResponse(response), loading: false });
    }

    const { data, error, loading } = this.state;
    return children({ data, error, loading });
  }
}

const RpcWithClient = props => (
  <FlowConsumer>
    {({ client }) => (
      <Rpc client={client} {...props} />
    )}
  </FlowConsumer>
);

export default RpcWithClient;
