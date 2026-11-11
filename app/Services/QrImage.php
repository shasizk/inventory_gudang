<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrImage
{
    public static function png(string $text): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64'    => false,   // kembalikan PNG mentah, bukan data URI
            'scale'           => 10,
        ]);

        return (new QRCode($options))->render($text);
    }
}