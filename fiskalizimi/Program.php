<?php

namespace fiskalizimi;

use GuzzleHttp\Client;

class Fiskalizimi
{
    private const PRIVATE_KEY_PEM =$_ENV['PRIVATE_KEY']; // or Path to your private key PEM file

    public static function signCitizenCoupon($citizenCoupon, ISigner $signer): string
    {
        // Serialize the citizen coupon to protobuf binary
        $citizenCouponProto = $citizenCoupon->serializeToString();

        // Convert the serialized protobuf to base64 string
        $base64EncodedProto = base64_encode($citizenCouponProto);

        // Convert the base64 string to byte array
        $base64EncodedBytes = $base64EncodedProto;

        // Digitally sign the bytes and return the signature
        $signature = $signer->signBytes($base64EncodedBytes);

        echo "Coupon   : $base64EncodedProto\n";
        echo "Signature: $signature\n";

        // Combine the encoded data and signature to create QR Code string and return it
        $qrCodeString = "$base64EncodedProto|$signature";
        echo "QR Code  : $qrCodeString\n";

        return $qrCodeString;
    }

    public static function signPosCoupon($posCoupon, ISigner $signer): array
    {
        // Serialize the pos coupon to protobuf binary
        $posCouponProto = $posCoupon->serializeToString();

        // Convert the serialized protobuf to base64 string
        $base64EncodedProto = base64_encode($posCouponProto);

        // Convert the base64 string to byte array
        $base64EncodedBytes = $base64EncodedProto;

        // Digitally sign the bytes
        $signature = $signer->signBytes($base64EncodedBytes);

        echo "Coupon   : $base64EncodedProto\n";
        echo "Signature: $signature\n";

        // Return the coupon and signature as base64 strings
        return [$base64EncodedProto, $signature];
    }

    public static function sendQrCode(): void
    {
        $url = "https://fiskalizimi.atk-ks.org/citizen/coupon";

        try {
            // Create the model builder
            $builder = new ModelBuilder();
            // Create signer using the private key defined
            $signer = new Signer(self::PRIVATE_KEY_PEM);

            // Get citizen coupon from the builder
            $citizenCoupon = $builder->getCitizenCoupon();

            // Digitally sign citizen coupon and get the QR code
            $qrCode = self::signCitizenCoupon($citizenCoupon, $signer);

            // Prepare the request
            $request = [
                'citizen_id' => 1,
                'qr_code' => $qrCode
            ];

            // POST the request to fiscalization service
            $client = new Client();
            $response = $client->post($url, [
                'json' => $request
            ]);

            if ($response->getStatusCode() === 200) {
                echo "QR code sent successfully\n";
            }
        } catch (\Exception $e) {
            // If there is an error, write it to console
            echo "Error sending the QR code\n";
            echo $e->getMessage() . "\n";
        }
    }

    public static function sendPosCoupon(): void
    {
        $url = "https://fiskalizimi.atk-ks.org/pos/coupon";

        try {
            // Create the model builder
            $builder = new ModelBuilder();
            // Create signer using the private key defined
            $signer = new Signer(self::PRIVATE_KEY_PEM);

            // Get pos coupon from the builder
            $posCoupon = $builder->getPosCoupon();

            // Digitally sign pos coupon and get the coupon and signature in base64 string
            [$coupon, $signature] = self::signPosCoupon($posCoupon, $signer);

            // Prepare the request
            $request = [
                'details' => $coupon,
                'signature' => $signature
            ];

            // POST the request to fiscalization service
            $client = new Client();
            $response = $client->post($url, [
                'json' => $request
            ]);

            if ($response->getStatusCode() === 200) {
                echo "POS coupon sent successfully\n";
            }
        } catch (\Exception $e) {
            // If there is an error, write it to console
            echo "Error sending the POS coupon\n";
            echo $e->getMessage() . "\n";
        }
    }
}

// Example usage
Fiskalizimi::sendPosCoupon();
Fiskalizimi::sendQrCode();