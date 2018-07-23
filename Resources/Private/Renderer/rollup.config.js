import resolve from 'rollup-plugin-node-resolve';

const input = 'packages/main/index.js';

export default {
  input,
  external: id => id !== input && !id.startsWith('@bytorsten') && id[0] !== '.' && id[0] !== '/',
  output: {
    file: 'build/index.js',
    sourcemap: true,
    banner: '#!/usr/bin/env node --no-warnings --experimental-vm-modules',
    intro: `process.env.NODE_ENV = ~process.argv.indexOf('--production') ? 'production' : 'development';`,
    format: 'cjs'
  },

  plugins: [
    resolve({
      only: [/^@bytorsten\//]
    })
  ],

  watch: {
    chokidar: true,
    exclude: ['node_modules/**']
  }
};
