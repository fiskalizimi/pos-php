# Fiscalization Integration with C# using Protobuf #

This repository provides a C# implementation for integrating with a fiscalization system using classes generated by Protobuf. The process includes constructing fiscal receipts (Citizen and POS Coupons), digitally signing them, and submitting them to the fiscalization service. This guide walks you through the steps necessary to integrate and execute the solution.

## Table of Contents ##

- [Project Overview](#project-overview)
- [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
- [Generating PROTOBUF models](#generating-protobuf-models)
    - [Manually generating Models](#manually-generating-models)
    - [Let .NET generate models automatically](#let-net-generate-models-automatically)
- [Model Explanation](#model-explanation)
    - [Citizen Coupon](#citizen-coupon)
    - [POS Coupon](#pos-coupon)
- [PKI Key Generation](#key-generation)
- [Digital Signing](#digital-signing)
    - [Steps to generate digital signature](#steps-to-generate-digital-signature)
    - [Using provided DLL to digitally sign strings](#using-provided-dll-to-digitally-sign-strings)
    - [QR Code generation](#qr-code)
- [Sending Data to Fiscalization Service](#sending-data-to-fiscalization-service)
    - [Sending Citizen Coupons](#sending-citizen-coupons)
    - [Sending POS Coupons](#sending-pos-coupons)
- [Running the Application](#running-the-application)

## Project Overview ##

This project provides a set of C# classes to interact with a fiscalization system. The key components include:

1. **Models**: This is the models generated by Protobuf
2. **ModelBuilder**: Constructs the Citizen and POS coupons (receipts) using predefined tax groups, items, and payment methods.
3. **Signer**: Signs the receipts using a digital signature created with an ECDSA private key.
4. **Fiskalizimi**: Contains methods for constructing, signing, and sending fiscal coupons to the fiscalization service.

### Key Technologies: ###
- **Protobuf**: Used for serializing the data models (CitizenCoupon, PosCoupon) to binary.
- **ECDSA**: Elliptic curve algorithm used for digital signatures.
- **HttpClient**: For sending data to the fiscalization service.

## Getting Started

### Prerequisites

Before integrating the system, ensure you have the following installed:

- [.NET SDK](https://dotnet.microsoft.com/download)
- [Protobuf Compiler](https://developers.google.com/protocol-buffers)
- A valid [ECDSA private key](#key-generation) for signing the data.

### Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/your-repo/fiskalizimi-integration.git
   cd fiskalizimi-integration
   ```

## Generating PROTOBUF models ##

### Manually generating Models ###

To manually generate C# classes from a ```.proto``` file using ```protoc```, the Protobuf compiler, you will first need to have the Protobuf tools
installed on your system. The ```protoc``` compiler is responsible for compiling ```.proto``` files into language-specific classes, including C#. Follow these steps to generate C# classes manually:

1. **Install the Protobuf compiler:** If you don't have ```protoc``` installed, download and install it from the [official Protobuf releases](https://github.com/protocolbuffers/protobuf/releases). Ensure the executable is in your system's PATH.
2. **Download the C# plugin:** You need to download the Protobuf C# plugin if it's not bundled with ```protoc```. It can be found in the official releases or by installing the ```Grpc.Tools``` package in a .NET project.
3. **Run the protoc command:** Use the following command in your terminal to generate the C# classes from the ```models.proto``` file. Replace paths accordingly for your setup:
   ```
   protoc --proto_path=./path/to/protos --csharp_out=./path/to/output ./path/to/protos/models.proto
   ```
    *  ```--proto_path=./path/to/protos``` : Specifies the directory where your .proto files are located.
    * ```--csharp_out=./path/to/output``` : Specifies the directory where the generated C# files should be saved.
    * ```models.proto``` : The ```.proto``` file you are compiling.
4. **Generated C# classes:** After running the ```protoc``` command, it will generate C# classes corresponding to the Protobuf messages and enums defined in the ```models.proto``` file. The classes will contain methods like ```ParseFrom()```, ```ToByteArray()```, and properties representing each field in the messages.
5. **Include the generated classes in your project:** After generating the C# files, you can manually add them to your .NET project by copying them into your solution’s directory or directly referencing them in your project’s code.

### Let .NET generate models automatically

To generate Protobuf models in .NET 8.0 from the ```models.proto``` file, you'll first need to install the **Google.Protobuf** package
and the **Grpc.Tools** package, which provides the necessary tooling to compile ```.proto``` files into C# classes. This process leverages the Protobuf compiler (protoc),
which takes the ```.proto``` definition and generates C# model classes for use in your .NET project.

Here are the steps to generate Protobuf models:

1. **Install the required NuGet packages:**

    * Install Google.Protobuf to use Protobuf runtime classes.
    * Install Grpc.Tools to compile .proto files.

   Run the following command in your project:

    ```
    dotnet add package Google.Protobuf
    dotnet add package Grpc.Tools
    ```

2. **Add the .proto file to your project:** Place your ```.proto``` file (in our case ```models.proto```) inside your project directory, usually in a ```Protos``` folder for organization. In the ```.csproj``` file, reference the ```.proto``` file to instruct the compiler to generate the necessary C# classes.
3. **Modify your .csproj file** to include Protobuf file generation instructions:
   ```
   <ItemGroup>
       <Protobuf Include="Protos/models.proto" GrpcServices="None" />
   </ItemGroup>
   ```
   Setting ```GrpcServices="None"``` ensures that only data models are generated, without gRPC service code, since we are only interested in the models (e.g., ```PosCoupon```, ```CitizenCoupon```, ```Payment```, etc.).
4. **Build the project:** Run the following command to compile the ```.proto``` file and generate the C# classes:
   ```
   dotnet build
   ``` 
   This will automatically generate C# classes that correspond to the Protobuf messages (like ```PosCoupon```, ```CitizenCoupon```, ```CouponItem```, etc.) in your ```.proto``` file.

## Model Explanation ##

### Citizen Coupon ###

The ```CitizenCoupon``` represents a simplified receipt that will be the part of QR Code. Below is the example structure created by the [```ModelBuilder``` class](fiskalizimi/ModelBuilder.cs):

```
public CitizenCoupon GetCitizenCoupon()
{
    var citizenCoupon = new CitizenCoupon
    {
        BusinessId = 1,
        PosId = 1,
        CouponId = 1234,
        Type = CouponType.Sale,
        Time = new DateTimeOffset(2024, 10, 1, 15,30, 20, TimeSpan.Zero).ToUnixTimeSeconds(),
        Total = 1820,
        TaxGroups =
        {
            new TaxGroup { TaxRate = "C", TotalForTax = 450, TotalTax = 0 },
            new TaxGroup { TaxRate = "D", TotalForTax = 320, TotalTax = 26 },
            new TaxGroup { TaxRate = "E", TotalForTax = 1850, TotalTax = 189 }
        },
        TotalTax = 215
    };
    
    return citizenCoupon;
}
```

The Citizen Coupon includes:

* **BusinessId** which is NUI of the business (received from ATK)
* **PosId** is the unique id of the POS. POS is the computer/till that has the POS system installed. Each POS unit must have a unique ID.
* **CouponId** is the unique identifier of the fiscal coupon generated by POS system
* **Type** this is the type of the coupon. It is an enum value and can be ```SALE```, ```RETURN``` or ```CANCEL```
* **Time** the time fiscal coupon is issued. The value is Unix timestamp
* **Total** that represents the total value to be paid by customer
* **TaxGroups** is an array of ```TaxGroup``` objects. Each ```TaxGroup``` object represents the details about tax category
* **TotalTax** is the amount of the tax in total that customer will have to pay

**NOTE:** These details must match the [POS Coupon](#pos-coupon) details, otherwise the coupon will be marked as ```FAILED VERIFICATION``` !


### POS Coupon ###

The PosCoupon includes all details of the POS Coupon that will be printed and given to the customer located in [```ModelBuilder``` class](fiskalizimi/ModelBuilder.cs)

```
public PosCoupon GetPosCoupon()
{
    var posCoupon = new PosCoupon
    {
        BusinessId = 1,
        PosId = 1,
        CouponId = 1234,
        BranchId = 3,
        Location = "Prishtine",
        OperatorId = "Kushtrimi",
        ApplicationId = 1,
        VerificationNo = "1234567890123456",
        Type = CouponType.Sale,
        Time = new DateTimeOffset(2024, 10, 1, 15,30, 20, TimeSpan.Zero).ToUnixTimeSeconds(),
        Items =
        {
            new CouponItem { Name = "uje rugove", Price = 150, Unit = "cope", Quantity = 3, Total = 450, TaxRate = "C", Type = "TT" },
            new CouponItem { Name = "sendviq", Price = 300, Unit = "cope", Quantity = 2, Total = 600, TaxRate = "E", Type = "TT" },
            new CouponItem { Name = "buke", Price = 80, Unit = "cope", Quantity = 4, Total = 320, TaxRate = "D", Type = "TT" },
            new CouponItem { Name = "machiato e madhe", Unit = "cope", Price = 150, Quantity = 3, Total = 450, TaxRate = "E", Type = "TT" }
        },
        Payments =
        {
            new Payment { Type = PaymentType.Cash, Amount = 500 },
            new Payment { Type = PaymentType.CreditCard, Amount = 1000 },
            new Payment { Type = PaymentType.Voucher, Amount = 320 }
        },
        Total = 1820,
        TaxGroups =
        {
            new TaxGroup { TaxRate = "C", TotalForTax = 450, TotalTax = 0 },
            new TaxGroup { TaxRate = "D", TotalForTax = 320, TotalTax = 26 },
            new TaxGroup { TaxRate = "E", TotalForTax = 1850, TotalTax = 189 }
        },
        TotalTax = 215,
        TotalNoTax = 1605
    };

    return posCoupon;
}
```

The POS Coupon includes:

* **BusinessId** which is NUI of the business (received from ATK)
* **PosId** is the unique id of the POS. POS is the computer/till that has the POS system installed. Each POS unit must have a unique ID.
* **CouponId** is the unique identifier of the fiscal coupon generated by POS system. CouponId has to be unique for Business (across all branches)
* **BranchId** is the branch id.
* **Location** is the location/city of the Sale Point
* **OperatorId** is the ID/Name of the operator/server
* **ApplicationId** is the unique ID of the Application/POS System used. This code is provided by the Software provider that has implemented the POS Solution.
* **VerificationNo** is a unique value for each coupon, and it is 16 characters long max. Verification Number is used to check if the Coupon has been verified by the citizen.
* **Type** this is the type of the coupon. It is an enum value and can be ```SALE```, ```RETURN``` or ```CANCEL```
* **Time** the time fiscal coupon is issued. The value is Unix timestamp
* **Items** is an array of ```CouponItem``` objects. Each ```CouponItem``` represents an item sold to the customer.
* **Payments** is an array of ```Payment``` that represent the types of the payment methods and the amoun used by customer to pay for the goods. The valid types are: ```Cash```, ```CreditCard```, ```Voucher```, ```Cheque```, ```CryptoCurrency```, and ```Other```.
* **Total** that represents the total value to be paid by customer
* **TaxGroups** is an array of ```TaxGroup``` objects. Each ```TaxGroup``` object represents the details about tax category
* **TotalTax** is the amount of the tax in total that customer will have to pay
* **TotalNoTax** is the total amount without tax that customer will have to pay

**NOTE:** These details must match the [Citizen Coupon](#citizen-coupon) details, otherwise the coupon will be marked as ```FAILED VERIFICATION``` !

## Key Generation ##

There are different ways to generate a PKI key pair, depending on the operating system.

**WARNING!** Each POS system (PC/till) needs to have a unique ID and its own PKI key pair. The private key should never leave the machine that it is generated on !!!

We have provided a tool that simplifies the process a lot by creating the key pair, generating a CSR and sending the CSR to ATK Certificate Authority to be digitally signed and verified.

If you have cloned this repository the tool for different operating systems is located under the folder ```onbarding``` or, alternatively to download the tool on you machine, click on one of the links below (depending on the operating system you are using):

* [onboarder for windows](https://github.com/fiskalizimi/pos-golang/raw/refs/heads/main/onboarder/onboarder-windows.zip)
* [onboarder for MacOS](https://github.com/fiskalizimi/pos-golang/raw/refs/heads/main/onboarder/onboarder-macos.zip)
* [onboarder for Linux](https://github.com/fiskalizimi/pos-golang/raw/refs/heads/main/onboarder/onboarder-linux.zip)

To onboard your business, you need the following information:

1. NUI of the business
2. Fiscalization Number - (this is obtained from EDI)
3. Pos ID - each POS should have a unique ID which is a numeric value
4. Branch ID

Once you have downloaded the onboarder tool, and extracted/unzipped it to a folder, then you need to run the application.
You need to provide an environment flag as an argument to the executable. For testing purposes the environment value should be ```TEST```, and for production the environment value should be ```PROD```

For example

On Windows Platform you need to open a command prompt then execute the application like the example below:
```shell
onboarder.exe -env=TEST
```

On linux/macos you need to open a terminal and then exeucte the application like the example below:
```shell
./onboarder -env=PROD
```

![Onboarder1](onboarder1.png)

if everything went Ok, then you will get a success message:

![Onboarder2](onboarder2.png)


To view certificate and private key in PEM format, on the **Certificate** tab, first tick the **Show private key** checkbox, then click on the **Show Certificate** button:

![Onboarder3](onboarder3.png)

To extract certificate and private key in PEM format, on the **Certificate** tab, first tick the **Show private key** checkbox, then click on the **Export Certificate** button.
This action will create another two files in the folder ```private-key.pem``` and ```signed-certificate.pem```

**WARNING !** Make sure to keep private key safe.

## Digital Signing ##

Before the data is sent to the Fiscalization System, the POS Coupon details need to be digitally signed using the private key to ensure the authenticity and integrity of the data transmitted to the fiscalization system.

#### Why Digital Signing? ####

The fiscalization system requires each coupon to be signed digitally before submission to ensure:

1. **Data Integrity:** Ensures that the data sent to the fiscalization service has not been tampered with during transmission.
3. **Authentication:** Confirms that the coupon is issued by a legitimate entity (in this case, your business), preventing fraudulent submissions.
3. **Non-Repudiation:** Guarantees that the sender cannot deny sending the data once it has been signed and submitted.

The digital signature is generated using a private key, and the fiscalization service verifies the signature using a corresponding public key. If the signature is valid, the coupon is considered authentic.

### Steps to generate digital signature ###

The steps to provide a valid signature are:

1. **Serialization:** First, the coupon (either a Citizen or POS coupon) is serialized into a Protobuf binary format. This format ensures that the data can be transmitted efficiently and consistently.
   ```
   byte[] posCouponProto = posCoupon.ToByteArray();
   ```
2. **Base64 Encoding:** The serialized Protobuf binary data is then encoded into a Base64 string. Base64 is a binary-to-text encoding scheme that makes it easy to transfer data as a string format.
   ```
   var base64EncodedProto = Convert.ToBase64String(posCouponProto);
   ```
3. **Hashing:** Before signing, the data is hashed using a SHA-256 cryptographic hash function. Hashing converts the coupon data into a fixed-length string of bytes, ensuring that even a small change in the original data will produce a completely different hash value.
   ```
   var sha256 = SHA256.Create();
   var hash = sha256.ComputeHash(base64EncodedProto);
   ```
4. **Signature Creation:** The hash is then signed using the ECDSA private key. This generates a digital signature, which is unique to the data and the private key. The fiscalization system can later verify this signature using the corresponding public key.
   ```
   var ecdsa = ECDsa.Create();
   ecdsa.ImportFromPem(_key);  // Load the private key
   var signature = ecdsa.SignHash(hash);
   ```
5. **Base64 Signature:** The generated signature is then encoded into a Base64 string, which makes it easy to include in the final request to the fiscalization service.
   ```
   var encodedSignature = Convert.ToBase64String(signature);
   ```

The [Signer class](fiskalizimi/Signer.cs) digitally signs both Citizen and POS coupons using the **ECDSA** algorithm. A private key is loaded and used to create a signature over the serialized coupon data.

```
public string SignBytes(byte[] data)
{
    var ecdsa = ECDsa.Create();
    ecdsa.ImportFromPem(_key);
    var hash = SHA256.Create().ComputeHash(data);
    var signature = ecdsa.SignHash(hash);
    return Convert.ToBase64String(signature);
}
```

The return value is a **base64-encoded** signature.

### Using provided DLL to digitally sign strings ###

We have also provided a DLL that is used to digitally sign a string and return a Base64 string of the signature. You can utilize it in a C# application using the ```DllImport```
attribute to call the external method from the DLL. The DLL exposes a function called ```DigitallySign```, which takes a message and a private key as inputs and returns a digitally signed Base64 string.

The class [```DllImporter```](fiskalizimi/DllImporter.cs) shows the interaction with provided external DLL to perform digital signing of a message which can be a base64 string encoding of protobuf representation of
either [PosCoupon](#pos-coupon) or [CitizenCoupon](#citizen-coupon) that is used in [QR Code](#qr-code).

This is done by importing the ```DigitallySign``` function from a native DLL (named "```signer```") using the ```[DllImport]``` attribute,
which allows unmanaged functions to be called in C#. The external DLL performs the actual cryptographic operation, digitally signing the input message using a private key.

1. **Initialization:** The class is initialized with a private key, provided through the constructor and stored in the ```_key``` field. This private key, in PEM format or another appropriate format, will be used by the external DLL to sign messages.
2. **Importing the DLL Function:** The DllImport attribute is used to declare the ```DigitallySign``` method. This method is marked as ```extern```, meaning it is defined externally (in the DLL). It takes two byte arrays as parameters: the first is the message to be signed, and the second is the private key. The method returns an ```IntPtr```, which points to the memory location of the resulting digital signature.
   ```
   [DllImport("signer", EntryPoint = "DigitallySign")]
   static extern IntPtr DigitallySign(byte[] msg, byte[] key);
   ```
3. **Signing a Message:** The ```SignMessage``` method converts the input message (string) into a byte array using ```Encoding.ASCII.GetBytes()``` and does the same for the private key. These byte arrays are passed to the ```DigitallySign``` function from the DLL. The result from this call is a pointer ```(IntPtr)``` to the digital signature, which is then converted into a Base64-encoded string using ```Marshal.PtrToStringAnsi()```. The method returns this Base64 string, which is the digital signature of the input message.
   ```
   public string SignMessage(string msg)
   {
       var msgBytes = Encoding.ASCII.GetBytes(msg); // Convert message to bytes
       var result = DigitallySign(msgBytes, Encoding.ASCII.GetBytes(_key)); // Call DLL method
       var signature = Marshal.PtrToStringAnsi(result); // Convert result to string
       return signature; // Return the signature
   }
   ```


### QR Code ###

Printed fiscal coupon needs to also have a QR Code that can be scanned by citizens to verify the authenticity of the receipt.

In the Fiscalization System, QR codes are generated based on the serialized and signed data of a Citizen Coupon. The data, once encoded into a QR code, is typically printed on the customer receipt.

#### QR Code Data Structure ####

In this implementation, the QR code contains:

1. The **Base64-encoded** serialized data of the [Citizen Coupon](#citizen-coupon).
2. The **Base64-encoded** digital signature of that data.

These two parts are combined into a single string, separated by a pipe | symbol, which forms the data to be encoded in the QR code.

#### QR Code Generation in Code ####

The following steps show how the QR code data is generated in the ```Fiskalizimi``` class using the ```CitizenCoupon``` model.

1. **Serialize the CitizenCoupon to Protobuf binary:** This ensures that the receipt data is in a compact binary format.
   ```
   byte[] citizenCouponProto = citizenCoupon.ToByteArray();
   ```
2. **Base64 encode the Protobuf data:** This converts the binary data into a Base64-encoded string, making it suitable for use in the QR code.
   ```
   var base64EncodedProto = Convert.ToBase64String(citizenCouponProto);
   ```
3. **Generate a digital signature:** Using the ECDSA private key, sign the Base64-encoded Protobuf data to ensure its authenticity and integrity.
   ```
   var base64EncodedBytes = Encoding.UTF8.GetBytes(base64EncodedProto);
   string signature = signer.SignBytes(base64EncodedBytes);
   ```
4. **Combine the data and signature:** The Base64-encoded coupon data and the Base64-encoded signature are concatenated with a pipe | symbol to form the final string, which will be encoded into a QR code.
   ```
   string qrCodeString = $"{base64EncodedProto}|{signature}";
   ```
5. **Print or display the QR code:** The resulting qrCodeString can now be encoded into a QR code and printed on the receipt or displayed on a screen.

![QR Code](qr.png)

Below is the method in the [Fiskalizimi class](fiskalizimi/Program.cs) that generates the QR code string for a Coupon:

```
public static string SignCitizenCoupon(CitizenCoupon citizenCoupon, ISigner signer)
{
    // Serialize the citizen coupon message to protobuf binary
    byte[] citizenCouponProto = citizenCoupon.ToByteArray();

    // convert the serialized protobuf of citizen coupon to base64 string
    var base64EncodedProto = Convert.ToBase64String(citizenCouponProto);

    // convert the base64 string of the citizen coupon to byte array
    var base64EncodedBytes = Encoding.UTF8.GetBytes(base64EncodedProto);
    
    // digitally sign the bytes and return the signature
    string signature = signer.SignBytes(base64EncodedBytes);
    
    Console.WriteLine($"Coupon   : {base64EncodedProto}");
    Console.WriteLine($"signature: {signature}");
    
    // Combine the encoded data and signature to create QR Code string and return it
    string qrCodeString = $"{base64EncodedProto}|{signature}";
    Console.WriteLine($"qr code  : {qrCodeString}");
    return qrCodeString;
}
```


## Sending Data to Fiscalization Service ##

### Sending Citizen Coupons ###

QR Code will be scanned by the Citizen Mobile App, which in turn will send the data to the Fiscalization System for verification.
This method mimics the Citizen Mobile App, and is used for testing purposes. The [SendQrCode method](fiskalizimi/Program.cs) sends the serialized and signed citizen coupon to the fiscalization service.

This is how you prepare and submit the request:

```
public static async Task SendQrCode()
{
    var builder = new ModelBuilder();
    var signer = new Signer(PrivateKeyPem);
    var citizenCoupon = builder.GetCitizenCoupon();
    var qrCode = SignCitizenCoupon(citizenCoupon, signer);

    var request = new { citizen_id = 1, qr_code = qrCode };
    var response = await new HttpClient().PostAsJsonAsync(url, request);
    response.EnsureSuccessStatusCode();
}
```

### Sending POS Coupons ###

Similar to citizen coupons, you can send POS coupons with the [SendPosCoupon method](fiskalizimi/Program.cs):

```
public static async Task SendPosCoupon()
{
    var builder = new ModelBuilder();
    var signer = new Signer(PrivateKeyPem);
    var posCoupon = builder.GetPosCoupon();
    var (coupon, signature) = SignPosCoupon(posCoupon, signer);

    var request = new { details = coupon, signature = signature };
    var response = await new HttpClient().PostAsJsonAsync(url, request);
    response.EnsureSuccessStatusCode();
}
```

## Running the Application ##

Before you can run the sample application provided, you first need to be onboarded. 
Once you have been [onboarded](#key-generation), then you need to copy the private key and replace 
the existing private key in the [Program.cs](fiskalizimi/Program.cs) file

To execute the program and send the coupons:
```
dotnet run
```

Make sure to configure the correct URL of the fiscalization service and have a valid ECDSA private key to sign the coupons.

test test
