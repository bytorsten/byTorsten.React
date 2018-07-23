import React, { Component } from 'react';
import { string, func, node, shape } from 'prop-types';
import { FlowConsumer } from './Flow';

const { Provider: RouterContextProvider, Consumer: RouterContextConsumer } = React.createContext();

class Router extends Component {

  static propTypes = {
    children: node.isRequired,
    controllerContext: shape({
      actionName: string.isRequired,
      controllerName: string.isRequired,
      packageKey: string.isRequired,
      subpackageKey: string
    }).isRequired
  }

  constructor(props) {
    super(props);

    this.state = {
      ...props.controllerContext
    };
  }

  render() {
    const { children } = this.props;
    return (
      <RouterContextProvider value={this.state}>
        {children}
      </RouterContextProvider>
    );
  }
}

const RouterWithContext = props => (
  <FlowConsumer>
    {({ controllerContext }) => (
      <Router controllerContext={controllerContext} {...props} />
    )}
  </FlowConsumer>
);

export { RouterWithContext as Router };

export const Route = ({ action, controller, packageKey, subpackageKey, children, component: Component, loader, ...rest }) => {

  const shouldRender = (controllerContext, routerContext) => {
    if (action !== routerContext.actionName) {
      return false;
    }

    if (controller === null) {
      controller = controllerContext.controllerName;
    }

    if (controller !== routerContext.controllerName) {
      return false;
    }

    if (packageKey === null) {
      packageKey = controllerContext.packageKey;
    }

    if (packageKey !== routerContext.packageKey) {
      return false;
    }

    if (subpackageKey === null) {
      subpackageKey = controllerContext.subpackageKey;
    }

    if (subpackageKey !== routerContext.subpackageKey) {
      return false;
    }

    return true;
  };

  return (
    <RouterContextConsumer>
      {routerContext => (
        <FlowConsumer>
          {({ controllerContext }) => {

            if (shouldRender(controllerContext, routerContext)) {
              if (children) {
                return children;
              }

              if (Component) {
                return <Component {...rest} />;
              }
            }

            return null;
          }}
        </FlowConsumer>
      )}
    </RouterContextConsumer>
  );
};

Route.propTypes = {
  action: string.isRequired,
  controller: string,
  packageKey: string,
  subpackageKey: string,
  children: node,
  component: func,
  loader: func
};

Route.defaultProps = {
  controller: null,
  packageKey: null,
  subpackageKey: null,
  children: null,
  component: null,
  loader: null
};
