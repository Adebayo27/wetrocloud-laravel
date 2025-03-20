# Wetrocloud Laravel SDK

A Laravel SDK to interact with Wetrocloud's API for AI-powered features like text generation, data extraction, image-to-text conversion, and collection management.

---

## Installation

### Via Composer
You can install the SDK using Composer:
```sh
composer require adebayo27/wetrocloud-sdk
```

If the package is not yet published to Packagist, install it from a local path:
```sh
composer require adebayo27/wetrocloud-sdk --dev
```

### Publishing Configurations
After installation, publish the config file:
```sh
php artisan vendor:publish --tag=wetrocloud-config
```

This will create a `config/wetrocloud.php` file where you can set your API key.

### Environment Variables
Ensure you add your Wetrocloud API key to your `.env` file:
```ini
WETROCLOUD_API_KEY=your_api_key_here
```

---

## Usage

### Initialize the SDK
The SDK is automatically injected via Laravel's Service Provider. To use it in a controller or service, inject `WetrocloudClient`:

```php
use Wetrocloud\Sdk\WetrocloudClient;

class ExampleController extends Controller
{
    private WetrocloudClient $wetrocloud;

    public function __construct(WetrocloudClient $wetrocloud)
    {
        $this->wetrocloud = $wetrocloud;
    }
}
```

---

## Features

### 1. List Collections
Retrieves all available collections.

#### Usage
```php
$collections = $wetrocloud->listCollections();
```

#### Response Example
```json
{
    "count": 10,
    "next": null,
    "previous": null,
    "collections": [ ... ]
}
```

---

### 2. Create a Collection
Creates a new collection with a unique ID.

#### Usage
```php
$collection = $wetrocloud->createCollection('my_unique_collection');
```

#### Response Example
```json
{
    "collection_id": "my_unique_collection",
    "success": true
}
```

---

### 3. Query a Collection
Retrieves specific data from a collection.

#### Usage
```php
$queryResult = $wetrocloud->queryCollection('my_collection', 'Find all users with role admin');
```

#### Response Example
```json
{
    "results": [ ... ]
}
```

---

### 4. Data Categorization
Classifies text into predefined categories.

#### Usage
```php
$categorization = $wetrocloud->categorizeText(
    "match review: John Cena vs. The Rock are fighting",
    ["football", "coding", "entertainment", "basketball", "wrestling", "information"]
);
```

#### Response Example
```json
{
    "response": { "label": "wrestling" },
    "tokens": 1746,
    "success": true
}
```

---

### 5. Text Generation
Generates AI-powered text responses based on input messages.

#### Usage
```php
$response = $wetrocloud->generateText([
    ['role' => 'user', 'content' => 'What is a large language model?']
]);
```

#### Response Example
```json
{
    "response": "A large language model is a type of AI trained on vast datasets..."
}
```

---

### 6. Image-to-Text
Extracts text or answers questions about an image.

#### Usage
```php
$response = $wetrocloud->imageToText(
    'https://example.com/sample-image.jpg',
    'What is in this image?'
);
```

#### Response Example
```json
{
    "response": "A golden retriever dog playing in the park."
}
```

---

### 7. Data Extraction from a Website
Extracts structured data from a web page based on a JSON schema.

#### Usage
```php
$response = $wetrocloud->extractDataFromWebsite(
    'https://example.com',
    ['title' => '', 'description' => '']
);
```

#### Response Example
```json
{
    "data": { "title": "Example", "description": "This is an example website." }
}
```

---

## Error Handling
All API requests return errors in a standard format.

### Example Error Response
```json
{
    "error": "Client error: 401 Unauthorized",
    "response": {
        "detail": "Invalid token."
    }
}
```

To handle errors, check if `error` is present in the response:
```php
if (isset($response['error'])) {
    return response()->json($response, 400);
}
```

---

## Support & Contributions
For issues or feature requests, open an issue on GitHub or contribute via pull requests.

GitHub Repository: [https://github.com/adebayo27/wetrocloud-sdk](https://github.com/adebayo27/wetrocloud-sdk)

