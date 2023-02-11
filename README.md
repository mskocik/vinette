# Vinette - Vite + Nette

Easy to use vite connector to nette apps. Based on [lubomirblazekcz/nette-vite](https://github.com/lubomirblazekcz/nette-vite) and transformed into extension.


## Install

Download extension.

```
composer require mskocik/vinette
```

Register it into your Nette application. It automatically adds `asset` filter and `$vite` property to your templates.


```neon
extensions:
	vite: Mskocik\Vinette\Bridges\NetteDI\ViteExtension

vite:
	manifest: assets/build/manifest.json    # public path local from real www dir
	wwwDir: <string>                        # can be omitted if it's 'www'
	devServer: http://localhost:5174        # can be omitted if your are using default 'http://localhost:5174'
```

## Usage in templates

You can use filter `asset` to set path to asset properly.

```latte
<link rel="stylesheet" href="{='src/css/style.css'|asset}">
<script src="{='src/js/bundle.js'|asset}" type="module">
```

Or in case you have JS files which imports some CSS files, you should use `printTags()` method. `$vite` variable is 
automatically injected into your templates.

```latte
{$vite->printTags('src/js/components.js')|noescape}

// which can result into:

// DEV
<script type="module" src="http://localhost:5174/src/js/components.js"></script>

// PRODUCTION
<link rel="stylesheet" href="/assets/build/components-1b96c47d.css">
<script type="module" src="/assets/build/components-8b95eb03.js"></script>
```

## Dev mode toggle

Dev mode is enabled by clicking on the Vite icon in the tracy bar. By default it's disabled, using production build assets.

In dev mode it signal if dev server is running or not (green or red dot).

---

### Reference Vite config

Reference `vite.config.js` file I am using very often (includes svelte for custom elements and tailwind)


```js
import FastGlob from 'fast-glob'
import { resolve } from 'path';
import { defineConfig, loadEnv } from 'vite';

import tailwindcss from 'tailwindcss'
import tailwindcssNesting from 'tailwindcss/nesting/index.js'
import autoprefixer from 'autoprefixer'
import postcssImport from 'postcss-import';
import postcssNested from 'postcss-nested';
import postcssCustomMedia from 'postcss-custom-media';
import { svelte } from '@sveltejs/vite-plugin-svelte'

const reload = {
  name: 'reload',
  handleHotUpdate({ file, server }) {
    if (!file.includes('temp') && file.endsWith(".php") || file.endsWith(".latte")) {
      server.ws.send({
        type: 'full-reload',
        path: '*',
      });
    }
  }
}

/** @type {import('vite').UserConfig} */
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'DEV');

  return {
    plugins: [svelte(), reload],
    css: {
      postcss: {
        plugins: [postcssImport, tailwindcssNesting(postcssNested), postcssCustomMedia, tailwindcss, autoprefixer]
      }
    },
    server: {
      port: env.DEV_PORT || 5174,
      strictPort: true,
      watch: {
        ignored: ['!^src', '!**/app/**/*.latte']
      },
      hmr: {
        host: 'localhost',
        port: 5137,
        protocol: 'ws'
      }
    },
    build: {
      manifest: true,
      outDir: "www/assets/build",
      emptyOutDir: true,
      rollupOptions: {
        input: FastGlob.sync(['./src/js/*.js', './src/css/*.css']).map(entry => resolve(process.cwd(), entry)),
      },
      assetsDir: '',
    }
  }
})

```


## Licence

MIT
