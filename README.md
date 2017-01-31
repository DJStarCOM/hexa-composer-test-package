Hexa composer test package
==========================

It requires PHP 5.6 or newer and is licensed under the MIT License.
Install it via Composer:

```
php composer.phar require djstarcom/hexa-composer-test-package
```

Usage
-----
Create object and set the images storage path
```php
$imageDownloader = new ImageDownloader('/tmp');
```
Set allowed mime types
```php
$imageDownloader->setAllowedImagesMimeTypes([
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
]);
```

Download a remote image
```php
$imageDownloader->downloadRemoteImage('https://assets-cdn.github.com/images/modules/about/about-header.jpg');
```
or
```php
$imageDownloader->addRemoteImageUrl('https://assets-cdn.github.com/images/modules/about/about-header.jpg');
$imageDownloader->downloadRemoteImages();
```
Also available download multiple images.
```php
$imageDownloader->addRemoteImageUrl([
    'https://assets-cdn.github.com/images/modules/about/about-header.jpg',
    'http://cdn.shopify.com/s/files/1/0051/4802/products/sticker-large_1024x1024.jpg',
]);
$imageDownloader->downloadRemoteImages();
```

To get array of all errors during the script execution
```php
$errors = $imageDownloader->getErrors();
```
or receive array of all stored images
```php
$all_stored_images = $imageDownloader->getStoredImages();
```
