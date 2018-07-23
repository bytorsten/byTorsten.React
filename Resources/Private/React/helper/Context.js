import React from 'react';
import { func } from 'prop-types';

import { FlowConsumer } from './Flow';

const Context = ({ children }) => (
  <FlowConsumer>
    {({ client }) => children(client.context)}
  </FlowConsumer>
);

Context.propTypes = {
  children: func.isRequired
};

export default Context;
