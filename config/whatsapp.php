<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | Número internacional (con código de país, sin + ni espacios) al que se
    | envían los pedidos del menú digital. Ej: 59170000000
    |
    */

    'phone' => env('WHATSAPP_PHONE', '59170000000'),

    /*
    |--------------------------------------------------------------------------
    | Negocio
    |--------------------------------------------------------------------------
    */

    'negocio' => [
        'nombre' => env('NEGOCIO_NOMBRE', 'Azúcar y Yo'),
        'direccion' => env('NEGOCIO_DIRECCION', ''),
        'telefono' => env('NEGOCIO_TELEFONO', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Impresión de fichas
    |--------------------------------------------------------------------------
    |
    | Tamaño de la hoja de la ficha en centímetros (ancho x alto). 5 x 7
    | corresponde a la impresora térmica de 58 mm; usa 8 x 12 para A6 o
    | 21 x 29.7 para A4.
    |
    | La ficha se imprime siempre en una sola hoja: si el contenido no cabe
    | se compacta y se reduce proporcionalmente. Cuanto más alta la hoja,
    | menos se reduce el texto.
    |
    */

    'ficha' => [
        'ancho_cm' => env('FICHA_ANCHO_CM', 5),
        'alto_cm' => env('FICHA_ALTO_CM', 7),
    ],

];
