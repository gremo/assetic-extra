# assetic-extra
[![Latest stable](https://img.shields.io/packagist/v/gremo/assetic-extra.svg?style=flat-square)](https://packagist.org/packages/gremo/assetic-extra) [![Downloads total](https://img.shields.io/packagist/dt/gremo/assetic-extra.svg?style=flat-square)](https://packagist.org/packages/gremo/assetic-extra) [![GitHub issues](https://img.shields.io/github/issues/gremo/assetic-extra.svg?style=flat-square)](https://github.com/gremo/assetic-extra/issues)

A collection of extra [assetic](https://github.com/kriswallsmith/assetic) resources to use with Symfony [AsseticBundle](https://github.com/symfony/assetic-bundle).

## Installation
Add the bundle in your `composer.json` file:

```json
{
    "require": {
        "gremo/assetic-extra": "^1.0"
    }
}
```

## Filters
The following extra filters can be configured and used in your templates.

### `nodesass`
Parses SASS/SCSS into CSS using the LibSass bindings for node.js. 

**Configurable options**:

- `bin`: path to your `node-sass` binary (default: `/usr/bin/node-sass`)
- `import_paths`
- `indent_type`
- `indent_width`
- `linefeed`
- `output_style`
- `precision`
- `source_comments`

See [sass/node-sass](https://github.com/sass/node-sass#options) for options description.

**Usage**:

Add the following configuration in the `assetic` section of your `config.yml`:

```yml
assetic:
    # ...
    nodesass:
        resource: '%kernel.root_dir%/../vendor/gremo/assetic-extra/Resources/filter/nodesass.xml'
        # options here
```

Then in your template (Twig example):

```twig
{% stylesheets '../app/Resources/scss/style.scss' filter='nodesass' %}
    <link rel="stylesheet" href="{{ asset_url }}" />
{% endstylesheets %}
```
