import React, { Component } from 'react';
import { bool, func, string, object, instanceOf } from 'prop-types';

import { FlowConsumer, FlowClient } from './Flow';

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
    data: null
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

    const data = await client.fetch({ helper, variables, cache: !forceFetch });
    this.setState({ data, loading: false });
  }

  fetchData() {
    const { client, helper, variables, forceFetch } = this.props;
    return client.fetch({ helper, variables, ssrOnly: forceFetch });
  }

  render() {
    const { children, helper, variables, client } = this.props;

    if (process.env.SSR) {
      const data = client.get({ helper, variables });
      return children({ data, loading: !data });
    }

    const { data, loading } = this.state;
    return children({ data, loading });
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
