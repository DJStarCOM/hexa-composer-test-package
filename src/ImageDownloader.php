<?php
/**
 * Created by StarCOM.
 * Author: Stanislav Tsepenuk Aleksandrovich
 * GitHub: https://github.com/DJStarCOM
 * E-mail: s.tsepeniuk@gmail.com
 */

namespace DJStarCOM\HexaComposerTestPackage;


class ImageDownloader
{
    private $errors = [];
    private $allowedImagesMimeTypes = [];
    private $remoteImagesUrls = [];
    private $imagesStoragePath;

    public static $supportedImagesMimeTypes = [
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
    ];

    /**
     * ImageDownloader constructor.
     * @param string $imagesStoragePath
     */
    public function __construct($imagesStoragePath)
    {
        if ($this->validateImageStoragePath($imagesStoragePath)) {
            $this->setImageStoragePath($imagesStoragePath);
        }
    }

    /**
     * getErrors
     * List of all excepted errors during the script execution
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * setAllowedImagesMimeTypes
     * Setting the list of allowed images mime types
     * @param array $allowedImagesMimeTypes
     * @throws ImageDownloaderException
     * @return void
     */
    public function setAllowedImagesMimeTypes(array $allowedImagesMimeTypes)
    {
        if (empty($allowedImagesMimeTypes)) {
            throw new ImageDownloaderException('Allowed images mime types can\'t be empty!');
        }

        foreach ($allowedImagesMimeTypes as $allowedImageMimeType) {
            $allowedImageMimeType = strtolower($allowedImageMimeType);
            if (!in_array($allowedImageMimeType, self::$supportedImagesMimeTypes)) {
                continue;
            }

            $this->allowedImagesMimeTypes[] = $allowedImageMimeType;
        }
    }

    /**
     * getAllowedImagesMimeTypes
     * Getting the list of all allowed images mime types
     * @return array
     */
    public function getAllowedImagesMimeTypes()
    {
        return $this->allowedImagesMimeTypes;
    }

    /**
     * addRemoteImageUrl
     * Adding the remote image url to download list
     * @param string|array $imageUrl
     * @throws ImageDownloaderException
     * @return false on failure
     */
    public function addRemoteImageUrl($imageUrl)
    {
        if (is_array($imageUrl)) {
            foreach ($imageUrl as $url) {
                try {
                    $this->addRemoteImageUrl($url);
                } catch (ImageDownloaderException $e) {
                    $this->errors[] = $e->getMessage();
                    continue;
                }
            }

            if (!empty($this->errors)) {
                return false;
            }
        } else {
            if ($this->validateRemoteImageUrl($imageUrl)) {
                $this->remoteImagesUrls[] = $imageUrl;
            }
        }
    }

    /**
     * getRemoteImagesUrls
     * Getting the list of remote image urls
     * @return array
     */
    public function getRemoteImagesUrls()
    {
        return $this->remoteImagesUrls;
    }

    /**
     * validateRemoteImageUrl
     * Validating the remote image url
     * @param string $imageUrl
     * @throws ImageDownloaderException
     * @return bool
     */
    public function validateRemoteImageUrl($imageUrl)
    {
        if (empty($imageUrl)) {
            throw new ImageDownloaderException('Image remote URL can\'t be empty!');
        }

        if (!is_string($imageUrl)) {
            throw new ImageDownloaderException('Image remote URL can\'t be "' . gettype($imageUrl) . '", string needed!');
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new ImageDownloaderException('Wrong format of image remote URL, please check the input data.');
        }

        return true;
    }

    /**
     * setImageStoragePath
     * Setting the path to image storage folder
     * @param string $path
     * @return void
     */
    public function setImageStoragePath($path)
    {
        $this->imagesStoragePath = $path;
    }

    /**
     * validateImageStoragePath
     * @param string $path
     * @throws ImageDownloaderException
     * @return bool
     */
    public function validateImageStoragePath($path)
    {
        if (empty($path)) {
            throw new ImageDownloaderException('Images storage path can\'t be empty!');
        }

        if (!file_exists($path)) {
            throw new ImageDownloaderException("Images storage specified path '{$path}' does not exist.");
        }

        if (!is_dir($path)) {
            throw new ImageDownloaderException('The path to images storage must be a folder.');
        }

        if (!is_writable($path)) {
            throw new ImageDownloaderException('Images storage must be a writable.');
        }

        return true;
    }

    /**
     * downloadRemoteImage
     * Downloading remote image into local image storage.
     * On success return local path of image.
     * @param string $imagePath
     * @throws ImageDownloaderException
     * @return string
     */
    public function downloadRemoteImage($imagePath)
    {
        if ($this->validateRemoteImageUrl($imagePath)) {
            $remote_file_content = file_get_contents($imagePath);
            if (!$remote_file_content) {
                throw new ImageDownloaderException("Failed to get the the remote file '{$imagePath}'.");
            }

            if ($this->validateImageStoragePath($this->imagesStoragePath)) {
                $new_file_path = $this->imagesStoragePath . DIRECTORY_SEPARATOR . 'image_' . time();
                $tmp_file_name = $new_file_path . '.tmp';
                if (file_put_contents($tmp_file_name, $remote_file_content)) {
                    $file_type = mime_content_type($tmp_file_name);
                    if (!in_array($file_type, $this->allowedImagesMimeTypes)) {
                        unlink($tmp_file_name);
                        throw new ImageDownloaderException('Remote image file is forbidden to download format.');
                    }

                    $file_path = $new_file_path . '.' . array_search($file_type, self::$supportedImagesMimeTypes);
                    if (rename($tmp_file_name, $file_path)) {
                        return $file_path;
                    } else {
                        unlink($tmp_file_name);
                        throw new ImageDownloaderException('Unable to rename image file. Please check the right of the image repository.');
                    }
                } else {
                    throw new ImageDownloaderException('Unable to save the remote image file.');
                }
            }
        }
    }

    /**
     * downloadRemoteImages
     * Downloading remote images into local image storage.
     * @throws ImageDownloaderException
     */
    public function downloadRemoteImages()
    {
        if (empty($this->remoteImagesUrls)) {
            throw new ImageDownloaderException('Remote images list is empty, please add at least one remote image url!');
        }

        foreach ($this->remoteImagesUrls as $imagesUrl) {
            try {
                $this->downloadRemoteImage($imagesUrl);
            } catch (ImageDownloaderException $e) {
                $this->errors[] = $e->getMessage();
                continue;
            }
        }
    }

    /**
     * getStoredImages
     * Getting the list of successful stored images
     * @throws ImageDownloaderException
     * @return array
     */
    public function getStoredImages()
    {
        $images = [];
        if ($this->validateImageStoragePath($this->imagesStoragePath)) {
            $images_storage_contents = scandir($this->imagesStoragePath);
            foreach ($images_storage_contents as $content) {
                $file_type = mime_content_type($content);
                if (in_array($file_type, self::$supportedImagesMimeTypes)) {
                    $images[] = $content;
                }
            }
        }

        return $images;
    }
}
