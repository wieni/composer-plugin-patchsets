# Composer Plugin Patchsets

## Introduction

The `wieni/composer-plugin-patchsets` is a PHP Composer plugin designed to address the complexities in managing and applying patches within Drupal projects.  
This plugin aims to centralize patch management, enabling a consistent approach to applying patches across multiple projects.

## Problem Statement

Current practices utilizing `cweagans/composer-patches` for patch management in projects have introduced several challenges:

- Lack of uniformity in patch application across different projects leads to inconsistencies in behaviors.
- Difficulty in identifying and applying the same patches when upgrading versions due to variations in patch creation among different projects.

## Objective

The primary goal of the `wieni/composer-plugin-patchsets` is to enable dependency patch resolution. A feature thas has [been deprecated](https://www.cweagans.net/2023/07/dependency-patch-resolution/) in `cweagans/composer-patches`.

## Functionality

The plugin leverages the new plugin capabilities released by `cweagans/composer-patches`, allowing it to add patches by registering [a custom Resolver](https://docs.cweagans.net/composer-patches/api/capabilities/). 

## Usage

- Create a repository (eg `your-org/drupal-patches`) that contains a `composer.json` with patches defined [as documented by `cweagans/composer-patches`](https://docs.cweagans.net/composer-patches/usage/defining-patches/).
- Require the `wieni/composer-plugin-patchsets` plugin and your new patch repository (`your-org/drupal-patches`) in your Drupal project.
- Define the patch repository (`your-org/drupal-patches`) as a patch set in your `composer.json`:

```json
{
  "extra": {
    "patchRepositories": [
      "your-org/drupal-patches"
    ]
  }
}
```

This configuration instructs `wieni/composer-plugin-patchsets` to apply the patches defined in the `your-org/drupal-patches` repository.

## Advanced Configuration

The plugin supports an advanced configuration allowing the exclusion of specific packages and patches.  
This next example demonstrates the exclusion of the "`#2907810`" patch for `drupal/core` and all patches for `drupal/field_group`.

```json
{
  "extra": {
    "patchRepositories": [
      {
        "name": "your-org/drupal-patches",
        "excludePatches": {
          "drupal/core": [
            "#2907810"
          ],
          "drupal/field_group": ["*"]
        }
      }
    ]
  }
}
```

## Contributing

Contributions, suggestions, and issue reports are welcome. Feel free to fork this repository and submit pull requests.

## License

This project is licensed under the MIT License.

## Acknowledgments

- Acknowledgment of `cweagans/composer-patches` for its pioneering efforts in patch management within Composer.
