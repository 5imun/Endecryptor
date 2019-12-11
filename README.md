# Endecryptor

Simple object for encryption and decryption of text messages in php and typescript.
**You must use same encryption secret and method when you encrypt message and when you decrypt that message.**

# Basic usage
### Encryption
- Encrypt any string by passing it to object method `encrypt` or `encryptWithTS`.
- Result of encryption will be stored in object property `temp_encrypted` and hash-based message authentication code will be stored in object property `temp_hmac`.

If you are sending request with encrypted message i suggest putting `temp_hmac` in `Authentication` header so that i can be decrypted and verified lather.
### Decryption
- Decrypt text with hmac signature check by pasing encrypted string and hmac string to object method `decrypt` or `decryptAndValidateTS`.
- If decryption was successful resulting decrypted text will be stored in property `temp_decrypted`.

After successful decryption you can use `temp_decrypted` property value and parse it to json or whatever...

# Examples
### Encrypt and then decrypt in php:
```sh
#Include Endecryptor before using it
$secret = 'hxXxVEVNa3S6OQdgltNoDkbZ10b0MkQV';
$method = 'AES-256-CBC';
$valid_request_TS_interval = 100; # in seconds
$endecryptor = new Endecryptor($secret, $method, $valid_request_TS_interval );

$original_message = '{"test":"Hello, World!"}';
$endecryptor->encryptWithTS($original_message);

echo "Encrypted message: $endecryptor->temp_encrypted\n";
echo "Encrypted message hmac: $endecryptor->temp_hmac\n";

if ( $endecryptor->decryptAndValidateTS( $endecryptor->temp_encrypted, $endecryptor->temp_hmac ) ) {
  echo "Original message:  $original_message\n";
  echo "Decrypted message: $endecryptor->temp_decrypted\n";
} else {
  echo 'Description was not successful';
}
```
##### Result:
```
Encrypted message: MjliMmM5NzljYWQ0YjA4Mw==ULxsH1juCOrieEkiRpHY1CMkKtvSvB5X+b8E9cOcQ7yYt+SUKj+I6FjaGvYjEldt
Encrypted message hmac: 5aa8f1b268dfef0dc2f48f1a25204e82
Original message:  {"test":"Hello, World!"}
Decrypted message: {"test":"Hello, World!"}
```
### Send encrypted message from wordpress to node web application:
#### Encrypt in php (file in some worpdress plugin):
```sh
#Include Endecryptor before using it
$secret = 'hxXxVEVNa3S6OQdgltNoDkbZ10b0MkQV';
$method = 'AES-256-CBC';
$valid_request_TS_interval = 100; # in seconds
$endecryptor = new Endecryptor($secret, $method, $valid_request_TS_interval );

$original_message = '{"test":"Hello, World!"}';
$endecryptor->encryptWithTS($original_message);
$request = array(
  'headers' => array(
    'Authorization' => $endecryptor->temp_hmac,
    'Content-Type'  => 'text/plain',
  ),
  'body' => $endecryptor->temp_encrypted,
);
#wp_remote_post is wordpress function
wp_remote_post( 'https://yourApp.com/message_endpoint/', $request );
```
#### Decrypt in typescript:
```js
//Import Endecryptor module before using it
const secret = 'hxXxVEVNa3S6OQdgltNoDkbZ10b0MkQV';
const method = 'AES-256-CBC';
const valid_request_TS_interval = 100; // in seconds
const endecryptor = new Endecryptor(secret, method, valid_request_TS_interval);

// listen for encrypted request and store encrypted message and hmac stirngs
// example in express server middleware:
const decryptReqBody: RequestHandler = async (req, _res, next) => {
    const hmac = req.header('Authorization');
    endecryptor.decryptAndValidateTS(req.body, hmac);
    if (endecryptor.decryptAndValidateTS(req.body, hmac)) {
        req.body = endecryptor.temp_decrypted; // decrypted body
        next();
    } else {
        console.log('Decryption failed.')
    }
};
```
#### License
`null`