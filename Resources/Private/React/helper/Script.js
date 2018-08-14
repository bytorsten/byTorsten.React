/* eslint-disable react/no-danger */
import React, { Fragment } from 'react';
import { Uri } from '@bytorsten/react';
import { string, node } from 'prop-types';
import bundleNotification from 'resource://byTorsten.React/Public/bundleNotification.js'; // eslint-disable-line

const Script = ({ internalDataKey, beforeScript }) => {
  const { identifier, clientFile, clientFileReady, ...rest } = __internalData; // eslint-disable-line no-undef

  return (
    <Fragment>
      <Uri forceFetch action="index" controller="rpc" package="bytorsten.react">
        {({ data }) => (
          <script dangerouslySetInnerHTML={{ __html: `
            window.${internalDataKey} = ${JSON.stringify({
            endpoints: {
              rpc: data
            },
            ...rest
          })};
          ` }}
          />
        )}
      </Uri>

      {beforeScript}

      {clientFile && (
        <Uri forceFetch action="index" controller="chunk" package="bytorsten.react" arguments={{ identifier, chunkname: clientFile }}>
          {({ data }) => (
            <script defer src={data} />
          )}
        </Uri>
      )}

      {clientFile && !clientFileReady && (
        <script src={bundleNotification} data-identifier={identifier} />
      )}
    </Fragment>
  );
};

Script.propTypes = {
  internalDataKey: string,
  beforeScript: node
};

Script.defaultProps = {
  internalDataKey: '__FLOW_HELPER__',
  beforeScript: null
};

export default Script;
