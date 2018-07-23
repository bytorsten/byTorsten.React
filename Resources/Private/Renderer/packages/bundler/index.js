import path from 'path';
import { rollup } from 'rollup';
import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';
import replace from 'rollup-plugin-replace';
import alias from 'rollup-plugin-alias';
import { terser } from 'rollup-plugin-terser';
import hypothetical from 'rollup-plugin-hypothetical';
import namedExports from 'rollup-plugin-named-exports';
import { stripBundle, nodeModulesPath, isProduction } from '@bytorsten/helper';

export default class Bundler {

  constructor({ file, baseBundle, chunkPath, baseDirectory, aliases = {}, hypotheticalFiles = {} }) {
    this.file = path.basename(file);
    this.path = baseDirectory || path.dirname(file);
    this.chunkPath = chunkPath;
    this.baseBundle = baseBundle;
    this.hypotheticalFiles = hypotheticalFiles;
    this.aliases = aliases;
    this.paths = [nodeModulesPath];
  }

  updateConfig(config) {

    const bundleFiles = Object.keys(this.baseBundle).reduce((files, key) => {
      files[`./${key}`] = this.baseBundle[key];
      return files;
    }, {});

    config.plugins[1] = hypothetical({
      allowFallthrough: true,
      leaveIdsAlone: true,
      files: { ...bundleFiles, ...this.hypotheticalFiles }
    });

    return config;
  }

  buildConfig() {

    return this.updateConfig({
      input: `./${this.file}`,
      cache: null,
      experimentalCodeSplitting: true,
      onwarn: ({ message, loc }) => {
        if (loc) {
          console.log(`${loc.file} (${loc.line}:${loc.column})}: ${message}`); // eslint-disable-line no-console
        } else {
          console.log(message, ); // eslint-disable-line no-console
        }

      },
      plugins: [
        alias(this.aliases),
        {}, // hypothetical
        resolve({
          extensions: [ '.js', '.json' ],
          browser: true,
          preferBuiltins: false,
          customResolveOptions: {
            basedir: this.path,
            paths: this.paths
          }
        }),
        namedExports(),
        commonjs({
          sourceMap: !isProduction,
          extensions: [ '.js', '' ] // allow files without extensions like the one from hypothetical
        }),
        replace({
          'process.env.SSR': false,
          'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV)
        }),

        isProduction && terser({
          toplevel: true
        })
      ]
    });
  }

  async bundle({ legacy = false, cache = null } = {}) {

    let config;
    if (cache) {
      config = this.updateConfig(cache);
    } else {
      config = this.buildConfig();
    }

    const result = await rollup(config);
    config.cache = result.cache;

    const format = legacy ? 'system' : 'es';
    const bundle = stripBundle(await result.generate({
      format,
      sourcemap: !isProduction
    }));

    return {
      cache: config,
      bundle: bundle
    };
  }
}
