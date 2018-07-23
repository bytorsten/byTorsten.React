import React from 'react';
import { node, string, instanceOf } from 'prop-types';

const { Provider, Consumer } = React.createContext(null);

export { Consumer as FlowConsumer };

export class FlowClient {

  constructor({ context = {} } = {}) {
    this.context = context;
    this.cache = {};
  }

  hydrate({ cache, context }) {
    this.cache = cache;
    this.context = context;
  }

  getCacheKey({ helper, variables }) {
    return JSON.stringify({ helper, variables });
  }

  extract() {
    return {
      cache: Object.keys(this.cache).reduce((cache, key) => {
        const { data, ssrOnly } = this.cache[key];

        if (!ssrOnly) {
          cache[key] = data;
        }

        return cache;
      }, {}),
      context: this.context
    };
  }

  get({ helper, variables }) {
    const entry = this.cache[this.getCacheKey({ helper, variables })];
    return entry ? entry.data : null;
  }

  fetchInternal(call) {
    if (process.env.SSR) {
      return __rpc(call); // eslint-disable-line no-undef
    }

    const { helper, ...data } = call;

    return fetch(this.endpoints.rpc, {
      method: 'POST',
      body: JSON.stringify({ helper, data }),
      headers: {
        'content-type': 'application/json'
      }
    }).then(response => response.json());
  }

  fetch({ helper, variables, cache = true, ssrOnly = false }) {
    const call = { helper, ...variables };
    const key = this.getCacheKey({ helper, variables });

    if (cache && this.cache[key]) {
      return Promise.resolve(this.cache[key].data);
    }

    return this.fetchInternal(call).then(response => {

      if (cache) {
        this.cache[key] = { data: response, ssrOnly };
      }

      return response;
    });
  }
}

export const FlowProvider = ({ internalDataKey, client, children }) => {



  let internalData;
  if (process.env.SSR) {
    internalData = __internalData; // eslint-disable-line no-undef
  } else {
    internalData = window[internalDataKey];
    client.endpoints = internalData.endpoints;
  }

  const value = {
    client,
    ...internalData
  };

  return (
    <Provider value={value}>
      {children}
    </Provider>
  );
};

FlowProvider.propTypes = {
  client: instanceOf(FlowClient).isRequired,
  children: node.isRequired,
  internalDataKey: string
};

FlowProvider.defaultProps = {
  internalDataKey: '__FLOW_HELPER__'
};
