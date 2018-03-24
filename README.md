# assetic-extra
[![Latest stable](https://img.shields.io/packagist/v/gremo/assetic-extra.svg?style=flat-square)](https://packagist.org/packages/gremo/assetic-extra) [![Downloads total](https://img.shields.io/packagist/dt/gremo/assetic-extra.svg?style=flat-square)](https://packagist.org/packages/gremo/assetic-extra) [![GitHub issues](https://img.shields.io/github/issues/gremo/assetic-extra.svg?style=flat-square)](https://github.com/gremo/assetic-extra/issues)

A collection of extra [assetic](https://github.com/kriswallsmith/assetic) resources to use with Symfony [AsseticBundle](https://github.com/symfony/assetic-bundle).

## Table of Contents
- [Filters](#filters)
  - [Babel](#babel)
  - [Browserify](#browserify)
  - [CSSO](#csso)
  - [Node-sass](#node-sass)
- [Recipes](#recipes)

## Installation
Install this library using Composer:

```bash
composer require gremo/assetic-extra
```

## Filters
> **Note**: with Symfony 3.3 you can replace `%kernel.root_dir%/..` with `%kernel.project_dir%` for filters configuration.

The following extra filters can be configured and used in your templates.

### Babel
JavaScript transpiler for node.js (https://babeljs.io).

**Configuration**

```yml
assetic:
    # ...
    babeljs:
        resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/babeljs.xml'
        # options here
```

**Options ([reference](https://babeljs.io/docs/usage/api/#options))**

- `bin`: path to your `babel` binary (default: `/usr/bin/babel`)
- `retain_lines`
- `presets`: a `string` or `array` of presets to use (preset name if installed globally, path otherwise)
- `plugins`: a `string` or `array` of plugins to use (plugin name if installed globally, path otherwise)
- `compact`
- `minified`
- `no_babel_rc`
- `auxiliary_comment_before`
- `auxiliary_comment_after`
- `parser_opts`
- `generator_opts`

> **Note**: Babel will look for `.babelrc` file in current asset directory and will travel up the directory tree (see [Lookup behavior](https://babeljs.io/docs/usage/babelrc/#lookup-behavior)), unless you specify the `no_babel_rc` option. This means you can put your `.babelrc` file in your project root without cluttering your `config.yml`.

**Usage**

```twig
{% javascripts '../app/Resources/js/*.js' filter='babeljs' %}
    <script src="{{ asset_url }}"></script>
{% endjavascripts %}
```

### Browserify
Lets you `require('modules')` in the browser (http://browserify.org).

> Credits goes to the original author (https://github.com/kriswallsmith/assetic/pull/669), I changed it a bit and added trasforms support.

**Configuration**

```yml
assetic:
    # ...
    browserify:
        resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/browserify.xml'
        # options here
```

**Options**

- `bin`: path to your `browserify` binary (default: `/usr/bin/browserify`)
- `node`: path to your `node` binary (default: `%assetic.node.bin%`, set to `null` to use `browserify` binary instead)
- `node_paths`: paths to add to Node.js environment when using `node` option (default: `%assetic.node.paths%`)
- `transforms` a `string` or `array` of Browserify transform to apply

**Usage**

> **Note**: there is no need to combine assets (`modules/module1.js` in the example) as long as you require your `module`. Browserify filter will take care of combining them in the output file.

```twig
{% javascripts '../app/Resources/js/main.js' filter='browserify' %}
    <script src="{{ asset_url }}"></script>
{% endjavascripts %}
```

> **Note**: the `assetic:watch` command will regenerate the assets only if you change the "main" script. Modules changes will not be monitored as they are not included in the `javascripts` tag. 

Example of `main.js`:

```js
// main.js
require('./modules/module1.js');
console.log('main.js');
```

Example of `modules/module1.js`:

```js
// modules/module1.js
console.log('modules/module1.js');
```

### CSSO
CSSO (CSS Optimizer) is a CSS minifier (https://github.com/css/csso).

**Configuration**

```yml
assetic:
    # ...
    csso:
        resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/csso.xml'
        # options here
```

**Options ([reference](https://github.com/css/csso-cli))**

- `bin`: path to your `csso` binary (default: `/usr/bin/csso`)
- `comments`
- `force_media_merge`
- `restructure_off`
- `usage`

**Usage**

```twig
{% stylesheets '../app/Resources/css/*' filter='csso' %}
    <link rel="stylesheet" href="{{ asset_url }}" />
{% endstylesheets %}
```

**Tip: fast and safe options**

```yml
csso:
    # ...
    comments: none
    restructure_off: true
```

### Node-sass
Parses SASS/SCSS into CSS using the LibSass bindings for node.js (https://github.com/sass/node-sass).

**Configuration**

```yml
assetic:
    # ...
    nodesass:
        resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/nodesass.xml'
        # options here
```

**Options ([reference](https://github.com/sass/node-sass#options))**

- `bin`: path to your `node-sass` binary (default: `/usr/bin/node-sass`)
- `import_paths`
- `indent_type`
- `indent_width`
- `linefeed`
- `output_style`
- `precision`
- `source_comments`
- `source_map_location`
- `source_map_public_dir`

**Usage**

```twig
{% stylesheets '../app/Resources/scss/style.scss' filter='nodesass' %}
    <link rel="stylesheet" href="{{ asset_url }}" />
{% endstylesheets %}
```

**Tip: Boostrap 4**

Use this options for Bootstrap 4 (see [package.json](https://github.com/twbs/bootstrap/blob/v4.0.0/package.json#L24)):

```yml
nodesass:
    # ...
    precision: 6
```

**Tip: source maps**

In order to generate the source maps, you'll need to specify a public accessible directory where the `.map` files can be placed (`source_map_location`) together with the base path (`source_map_public_dir`) which will be used when generating the urls to the mapping files:

```yml
nodesass:
    # ...
    source_map_location: '%kernel.root_dir%/../web/assets/maps'
    source_map_public_dir: '/assets/maps'
```

## Recipes

### ES6 modules with Browserify
Write ES6 JavaScript modules using import/export style and make it work [in the browser](http://caniuse.com/#feat=es6-module).

**Problem**: Browserify filter transform your source file and not your transpiled one, so it would fail at the first `import` or `export` keyword.
**Solution**: only use the `browserify` filter with [babelify](https://github.com/babel/babelify) transform filter configuration:

> **Note** if Browserify cannot find the babelify module, try installing it locally in your project folder and use the absolute path.

```yml
browserify:
    resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/browserify.xml'
    transforms:
        - '[ babelify --presets env ]'
```
