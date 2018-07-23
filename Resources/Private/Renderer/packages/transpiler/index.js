import { rollup } from 'rollup';
import babel from 'rollup-plugin-babel';
import hypothetical from 'rollup-plugin-hypothetical';
import alias from 'rollup-plugin-alias';

import { stripBundle, isProduction } from '@bytorsten/helper';

import BabelPresetReact from '@babel/preset-react';
import BabelRuntime from '@babel/plugin-transform-runtime';
import BabelObjectRestSpread from '@babel/plugin-proposal-object-rest-spread';
import BabelClassProperties from '@babel/plugin-proposal-class-properties';
import BabelReactInlineElement from '@babel/plugin-transform-react-inline-elements';
import BabelReactContantElements from '@babel/plugin-transform-react-constant-elements';
import BabelSyntaxDynamicImport from '@babel/plugin-syntax-dynamic-import';
import BabelSyntaxImportMeta from '@babel/plugin-syntax-import-meta';

import buildReactHelpers from './helpers';

export default class Transpiler {

  constructor({ serverFile, clientFile, helpers, aliases = {}, hypotheticalFiles = {} }) {
    this.serverFile = serverFile;
    this.clientFile = clientFile;
    this.helpers = helpers;
    this.hypotheticalFiles = hypotheticalFiles;
    this.aliases = aliases;
    this.resolvedPaths = {};
    this.dependencies = [];
  }

  async transpile() {
    this.result = await rollup({
      input: [this.serverFile, this.clientFile].filter(Boolean),
      onwarn: ({ code, source, message, importer }) => {

        if (code === 'UNRESOLVED_IMPORT' && source[0] !== '.' && source[0] !== '/') {
          return;
        }

        if (code === 'CIRCULAR_DEPENDENCY' && importer === '@bytorsten/react') {
          return;
        }

        console.warn(message);
      },
      external: id => ~Object.keys(this.resolvedPaths).indexOf(id),
      experimentalCodeSplitting: true,
      plugins: [
        buildReactHelpers({
          helpers: this.helpers,
          addResolvedPath: (name, path) => this.resolvedPaths[name] = path,
          addDependency: path => this.dependencies.push(path)
        }),
        alias(this.aliases),
        hypothetical({
          allowFallthrough: true,
          leaveIdsAlone: true,
          files: this.hypotheticalFiles
        }),
        babel({
          runtimeHelpers: true,
          exclude: 'node_modules/**',

          presets: [
            BabelPresetReact
          ],

          plugins: [
            BabelRuntime,
            BabelSyntaxDynamicImport,
            BabelSyntaxImportMeta,
            BabelObjectRestSpread,
            BabelClassProperties,
            isProduction && BabelReactInlineElement,
            isProduction && BabelReactContantElements
          ].filter(Boolean)
        })
      ]
    });

    const bundle = stripBundle(await this.result.generate({ format: 'es', sourcemap: !isProduction }));

    return {
      bundle,
      resolvedPaths: this.resolvedPaths
    };
  }

  getDependencies() {
    return [
      ...this.dependencies,
      ...this.result.cache.modules.map(({ id }) => id).filter(id => id[0] === '/')
    ];
  }
}
