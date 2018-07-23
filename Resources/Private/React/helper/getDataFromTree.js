import React from 'react';
import Script from './Script';

const getProps = element => element.props || element.attributes;
const isReactElement = element => !!element.type;
const isComponentClass = Comp => Comp.prototype && (Comp.prototype.render || Comp.prototype.isReactComponent);
const providesChildContext = instance => !!instance.getChildContext;
const hasFetchDataFunction = instance => typeof instance.fetchData === 'function';
const isPromise = promise => typeof promise.then === 'function';

const walkTree = (element, context, visitor) => {

  if (Array.isArray(element)) {
    element.forEach(item => walkTree(item, context, visitor));
    return;
  }

  if (!element) {
    return;
  }

  if (isReactElement(element)) {
    if (typeof element.type === 'function') {
      const Comp = element.type;
      const props = { ...Comp.defaultProps, ...getProps(element) };

      let childContext = context;
      let child = null;

      if (isComponentClass(Comp)) {
        const instance = new Comp(props, context);
        instance.props = instance.props || props;
        instance.context = instance.context || context;
        instance.state = instance.state || null;
        instance.setState = newState => {
          if (typeof newState === 'function') {
            newState = newState(instance.state, instance.props, instance.context);
          }
          instance.state = { ...instance.state, ...newState };
        };

        if (Comp.getDerivedStateFromProps) {
          const result = Comp.getDerivedStateFromProps(instance.props, instance.state);
          if (result !== null) {
            instance.state = { ...instance.state, result };
          }
        } else if (instance.UNSAFE_componentWillMount) {
          instance.UNSAFE_componentWillMount();
        } else if (instance.componentWillMount) {
          instance.componentWillMount();
        }

        if (providesChildContext(instance)) {
          childContext = { ...context, ...instance.getChildContext() };
        }

        if (visitor(element, instance, context, childContext) === false) {
          return;
        }

        child = instance.render();
      } else {
        if (visitor(element, null, context) === false) {
          return;
        }
        child = Comp(props, context);
      }

      if (child) {
        if (Array.isArray(child)) {
          child.forEach(item => walkTree(item, childContext, visitor));
        } else {
          walkTree(child, childContext, visitor);
        }
      }

    } else if (element.type._context || element.type.Consumer) {
      if (visitor(element, null, context) === false) {
        return;
      }

      let child = null;
      if (element.type._context) {
        element.type._context._currentValue = element.props.value;
        child = element.props.children;
      } else {
        child = element.props.children(element.type._currentValue);
      }

      if (child) {
        if (Array.isArray(child)) {
          child.forEach(item => walkTree(item, context, visitor));
        } else {
          walkTree(child, context, visitor);
        }
      }
    } else {
      if (visitor(element, null, context) === false) {
        return;
      }
      if (element.props && element.props.children) {
        React.Children.forEach(element.props.children, child => child && walkTree(child, context, visitor));
      }
    }
  } else if (typeof element === 'string' || typeof element === 'number') {
    visitor(element, null, context);
  }
};

const getPromisesFromTree = tree => {
  const rootElement = tree.rootElement;
  const rootContext = tree.rootContext || {};
  const promises = [];

  walkTree(rootElement, rootContext, (_, instance, context, childContext) => {
    if (instance && hasFetchDataFunction(instance)) {
      var promise = instance.fetchData();
      if (isPromise(promise)) {
        promises.push({
          promise: promise,
          context: childContext || context,
          instance: instance
        });
        return false;
      }
    }
  });
  return promises;
};

const getDataFromTree = (rootElement, rootContext) => {
  rootContext = rootContext || {};

  const promises = getPromisesFromTree({ rootElement: rootElement, rootContext: rootContext });

  if (!promises.length) {
    return Promise.resolve();
  }

  const errors = [];
  const mappedPromises = promises.map(({ promise, context, instance }) => {
    return promise
      .then(() => getDataFromTree(instance.render(), context))
      .catch(error => errors.push(error));
  });

  return Promise.all(mappedPromises).then(() => {
    if (errors.length > 0) {
      const error = errors.length === 1 ? errors[0] : new Error(`${errors.length} errors were thrown when executing your fetchData functions.`);
      error.queryErrors = errors;
      throw error;
    }
  });
};

export default async component => {
  await getDataFromTree(component);
  await getDataFromTree(<Script />);
};
