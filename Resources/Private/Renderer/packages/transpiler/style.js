import MagicString, { Bundle } from 'magic-string';

export default function style(cb) {

  const styles = {};

  return {
    name: 'style',

    transform: (code, id) => {
      if (!id.endsWith('.css')) {
        return null;
      }

      styles[id] = code;
      return '';
    },

    generateBundle: () => {
      const bundle = new Bundle();

      for (const id in styles) {
        bundle.addSource({
          filename: id,
          content: new MagicString(styles[id])
        });
      }

      cb({ code: bundle.toString(), map: bundle.generateMap({
        hires: true
      }).toString() });
    }
  };

}
