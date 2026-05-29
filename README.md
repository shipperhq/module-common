# ShipperHQ Common for Magento 2

ShipperHQ Common is a shared library module that provides the core models, interfaces, and utilities used across ShipperHQ's suite of Magento 2 modules. It is a required dependency of other ShipperHQ modules and is not intended to be used standalone.

---

## Features

- **Shared Models**: Common data models for pickup, calendar, and shipping option features shared across ShipperHQ modules.
- **Configuration Interfaces**: Abstract configuration classes and interfaces that provide a consistent configuration contract across the ShipperHQ module suite.
- **Checkout Integration**: Plugin support for Magento's shipping method management to enable ShipperHQ's extended checkout capabilities.
- **Service Abstractions**: Shared service classes consumed by ShipperHQ feature modules to avoid duplication and ensure consistency.

---

## Installation

This module is installed automatically as a dependency when you install any ShipperHQ module that requires it. You do not need to install it directly.

Full installation instructions for the ShipperHQ extension are available in the [ShipperHQ documentation](https://docs.shipperhq.com/installing-magento-2-shipperhq-extension/).

---

## Requirements

- Magento 2.4.4+
  - Compatibility with earlier editions is possible but not maintained
  - Supports both Magento Opensource (Community) and Magento Commerce (Enterprise)
- A valid ShipperHQ account. [Sign up here](https://shipperhq.com/) for your 15-day free trial!

---

## Configuration

This module has no configuration of its own. All ShipperHQ configuration is managed through the main ShipperHQ module under `Stores > Configuration > Sales > Shipping Methods > ShipperHQ`.

You can find full configuration instructions in the [ShipperHQ documentation](https://docs.shipperhq.com/).

---

## Frequently Asked Questions

### 1. Do I need to install this module manually?
No. This module is pulled in automatically as a composer dependency when you install any ShipperHQ module that requires it.

### 2. Is there a free trial available for ShipperHQ?
Yes, ShipperHQ offers a free trial for new users. You can sign up for the trial on the [ShipperHQ website](https://shipperhq.com/) and explore the platform's features before committing to a subscription.

### 3. How do I troubleshoot issues related to this module?
- Verify that all ShipperHQ modules are on compatible versions by checking the `require` constraints in each module's `composer.json`.
- Check our extensive [documentation](https://docs.shipperhq.com/category/troubleshooting/) for troubleshooting tips.

### 4. Can I use this module without installing the main ShipperHQ module?
No, this library is not intended for standalone use. It is designed to be consumed by other ShipperHQ modules that provide the actual shipping features.

---

## Support

For assistance, please visit our [Help Center](https://docs.shipperhq.com/) or contact ShipperHQ support at [support@shipperhq.com](mailto:support@shipperhq.com).

For alternative contact methods, please visit our [Contact Us](https://shipperhq.com/contact/) page.

---

## Contributing

Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

---

## License

See license files

---

## Copyright
Copyright (c) 2015 Zowta LLC (http://www.ShipperHQ.com)
