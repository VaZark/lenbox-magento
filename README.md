# Lenbox payment plugin for Magento 2.x
Use Lenbox's plugin for Magento to offer mobile payments online in your e-commerce.

## Integration
The plugin integrates Magento store with payments on Lenbox App.

## Requirements
The plugin supports the Magento (version 2.1 and higher). 

## Collaboration
We commit all our new features directly into our GitHub repository.
But you can also request or suggest new features or code changes yourself!

## Support
Open new issue [https://github.com/Lenbox/magento2/issues](https://github.com/Lenbox/magento2/issues).

## Installation

Use composer:
```
composer require lenbox/magento2
```

After:
```
php bin/magento setup:upgrade
```

## API Documentation
##### - [Lenbox E-Commerce registration page](https://ecommerce.lenbox.com/)

##### - [Lenbox E-Commerce public API documentation](https://ecommerce.lenbox.com/doc/)

## Caching / Varnish configuration
In case you are using a caching layer such as Varnish, please exclude the following URL pattern from being cached
```
/lenbox/*
```

## License
MIT license. For more information, see the LICENSE file.
