/* eslint-disable react/no-danger */
import React, { Fragment } from 'react';
import { Uri } from '@bytorsten/react';
import { string } from 'prop-types';

const Script = ({ internalDataKey }) => {
  const { identifier, clientChunkName: chunkname, ...rest } = __internalData; // eslint-disable-line no-undef

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

      <Uri forceFetch action="index" controller="chunk" package="bytorsten.react" arguments={{ identifier, chunkname }}>
        {({ data }) => (
          <script defer src={data} />
        )}
      </Uri>
    </Fragment>
  );
};

Script.propTypes = {
  internalDataKey: string
};

Script.defaultProps = {
  internalDataKey: '__FLOW_HELPER__'
};

export default Script;
