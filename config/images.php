<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Watermark (AKAR-51)
    |--------------------------------------------------------------------------
    |
    | The Akar Town logo on the website's photos and plans (never on logos or the admin's thumbnails). Added when an
    | image is sent, so switching it on or off applies at once: IMAGES_WATERMARK=true / false in .env.
    |
    */

    'watermark' => (bool) env('IMAGES_WATERMARK', false),

];
