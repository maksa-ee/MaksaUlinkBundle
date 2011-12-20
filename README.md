MaksaUlinkBundle
======================

This bundle integrates the standalone PHP "[Ulink library](/ribozz/ulink-php)".

## Installation

To install this bundle, you'll need both the [Ulink library](/ribozz/ulink-php)
and this bundle. Installation depends on how your project is setup:

### Step 1: Installation using the `bin/vendors` method

If you're using the `bin/vendors` method to manage your vendor libraries,
add the following entries to the `deps` in the root of your project file:

```
[Ulink]
    git=http://github.com/ribozz/ulink-php.git
    target=ulink

[UlinkBundle]
    git=http://github.com/cravler/MaksaUlinkBundle.git
    target=/bundles/Maksa/Bundle/UlinkBundle
```

**NOTE**: This location and syntax of the `deps` file changed after BETA4. If you're
using an older version of the deps system, you may need to swap the order of the items
in the `deps` file.

Next, update your vendors by running:

``` bash
$ php bin/vendors install
```

Great! Now skip down to *Step 2*.

### Step 1 (alternative): Installation with submodules

If you're managing your vendor libraries with submodules, first create the
`vendor/bundles/Maksa/Bundle` directory:

``` bash
$ mkdir -pv vendor/bundles/Maksa/Bundle
```

Next, add the two necessary submodules:

``` bash
$ git submodule add git://github.com/ribozz/ulink-php.git vendor/ulink
$ git submodule add git://github.com/cravler/MaksaUlinkBundle.git vendor/bundles/Maksa/Bundle/UlinkBundle
```

### Step2: Configure the autoloader

Add the following entries to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...

    'Maksa' => __DIR__.'/../vendor/bundles',
));

$loader->registerPrefixes(array(
    // ...

    'Ulink_' => __DIR__.'/../vendor/ulink/src',
));
```

### Step3: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...

        new Maksa\Bundle\UlinkBundle\MaksaUlinkBundle(),
    );
}
```

## Basic Usage

Payment Request

``` php
$ulink   = $this->get('maksa_ulink.service');
$rawData = $ulink->encrypt(
    array(
        'clientTransactionId' => '',
        'amount'              => '0',
        'order'               => array(),
        'currency'            => null,
        'goBackUrl'           => null,
        'responseUrl'         => null,
    )
);
```
Payment Response

``` php
$ulink   = $this->get('maksa_ulink.service');
$ulink->decrypt($rawData);
```

## Configuration

The default configuration for the bundle looks like this:

``` yaml
maksa_ulink:
    client_id: 0
    key_path: %kernel.root_dir%/Resources/keys
    public_key: maksa.public
    private_key: your.private
    default_currency: EUR
    default_go_back_url: 'http://localhost/goback'
    default_response_url: 'http://localhost/response'
```

There are several configuration options available:

 - `client_id` - client id from maksa.ee.

 - `key_path` - must be the absolute path to your RSA keys directory.

    default: `%kernel.root_dir%/Resources/keys`

 - `public_key` - maksa.ee public RSA key name.

    default: `maksa.public`

 - `private_key` - your private RSA key name.

    default: `your.private`

 - `default_currency` - default currency.

    default: `EUR`

 - `default_go_back_url` - default go back url.

 - `default_response_url` - default response url.

