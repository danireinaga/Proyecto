<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        abort_unless(
            preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_-]*\.(?:jpg|jpeg|png|webp)\z/i', $filename) === 1,
            404
        );

        $path = Storage::disk('public')->path('productos/'.$filename);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Type' => match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
            },
        ]);
    }
}
