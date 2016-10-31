# PayU account plugin for OpenCart version 2.3.x

**Note: Plugin supports only OpenCart 2.3.x**

**For OpenCart 2.0.x, 2.1.x or 2.2.x please use 3.1.x plugin**

``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl**

## Table of Contents

- [Features][0]<br/>
- [Prerequisites][1] <br />
- [Installation][2]<br />
- [Configuration][3]<br />
    - [Configuration Parameters][3.1]

## Features
The PayU payments OpenCart plugin adds the PayU payment option and enables you to process the following operation in your e-shop:

* Creating a payment order (with discounts included)

## Prerequisites

**Important:** This plugin works only with checkout (**REST API**) points of sales (POS).

* PHP >= 5.3.0
* PHP extensions are required - [cURL][ext2], [hash][ext3] 

## Installation

To install the plugin, copy folders and activate it on the administration page:

1. Copy the folders from [the plugin repository][ext1] to your OpenCart root folder on the server.
1. Go to the OpenCart administration page [http://your-opencart-url/admin].
1. Go to **Extensions** > **Extensions**.
1. Set filter to **Payments** 
1. In the **PayU** section click **Install**.

## Configuration

1. Go to the OpenCart administration page [http://your-opencart-url/admin].
1. Go to **Extensions** > **Extensions**.
1. Set filter to **Payments** 
1. In the **PayU** section click **Edit**.

### Configuration Parameters

The tables below present the descriptions of the configuration form parameters.

#### Main parameters

The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Status|Enabled/Disabled|Specifies whether the module is enabled.|
|Sort Order|Positive integers|The priority that the payment method gets in the payment methods list.|
|Total|Positive integers|Minimal amount for PayU payment method to be active.|
|Geo Zone|Zone's List|Geo Zone for PayU payment method to be active.|

#### POS parameters

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Second Key|MD5 key for securing communication|
|OAuth - client_id|client_id for OAuth|
|OAuth - client_secret|client_secret for OAuth|

#### Status parameters

Defines which status is assigned to an order at a particular stage of order processing.


<!--LINKS-->

<!--topic urls:-->
[0]: https://github.com/PayU/plugin_opencart_2##features
[1]: https://github.com/PayU/plugin_opencart_2#prerequisites
[2]: https://github.com/PayU/plugin_opencart_2#installation
[3]: https://github.com/PayU/plugin_opencart_2#configuration
[3.1]: https://github.com/PayU/plugin_opencart_2#configuration-parameters


<!--external links:-->
[ext1]: https://github.com/PayU/plugin_opencart_2
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php

<!--images:-->
