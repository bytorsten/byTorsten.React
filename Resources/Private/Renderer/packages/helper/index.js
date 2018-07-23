import fs from 'fs';
import path from 'path';

export const rootPath = path.resolve(__dirname, '..');
export const staticPath = path.join(rootPath, 'static');
export const nodeModulesPath = path.join(rootPath, 'node_modules');

export const isProduction = process.env.NODE_ENV === 'production';

export const stripBundle = bundle => Object.keys(bundle.output).reduce((strippedBundle, filename) => {
  const { code, map } = bundle.output[filename];

  strippedBundle[filename] = {
    code,
    map: map ? map.toString() : null
  };

  return strippedBundle;
}, {});

export const loadFile = filename => new Promise((resolve, reject) => {
  fs.readFile(filename, 'utf8', (error, content) => {
    if (error) {
      reject(error);
    } else {
      resolve(content);
    }
  });
});
