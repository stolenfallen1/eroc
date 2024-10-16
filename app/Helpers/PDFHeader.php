<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PDFHeader{
    
    public function imageSRC(){
        $imagePath = public_path('images/logo1.png'); // Replace with the actual path to your image
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;
        return $imageSrc;
    }

    public function QrPath($path){
        $qrCode = QrCode::size(200)->generate(config('app.url') . $path);
        $qrData = base64_encode($qrCode);
        $qrSrc = 'data:image/jpeg;base64,' . $qrData;
        return $qrSrc;
    }
}