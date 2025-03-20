<?php

namespace Wetrocloud\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WetrocloudClient
{
    protected $client;
    protected $apiKey;
    protected $version;

    public function __construct(string $apiKey, $version)
    {
        $this->apiKey = $apiKey;
        $this->version = $version;
        $this->client = new Client([
            'base_uri' => 'https://api.wetrocloud.com/' . $version . '/',
            'headers' => ['Authorization' => "Bearer $apiKey"]
        ]);
    }

    public function request($method, $endpoint, $data = [], $options = [])
    {
        try {
            $requestOptions = [];
            
            if (!empty($data)) {
                if (isset($options['form']) && $options['form']) {
                    $requestOptions['form_params'] = $data;
                } else {
                    $requestOptions['json'] = $data;
                }
            }
            
            if (isset($options['stream']) && $options['stream']) {
                $requestOptions['stream'] = true;
            }
            
            if (isset($options['headers'])) {
                $requestOptions['headers'] = $options['headers'];
            }
            
            $response = $this->client->request($method, $endpoint, $requestOptions);
            
            if (isset($options['stream']) && $options['stream']) {
                return $response;
            }
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null
            ];
        }
    }

    /**
     * Creates a new collection in WetroCloud.
     *
     * @param ?string $collectionId The unique identifier for the collection.
     *
     * @return array{
     *     collection_id?: string|null,
     *     success?: bool,
     *     error?: string,
     *     response?: array|null
     * } The created collection details or an error response.
     *
     * @throws \GuzzleHttp\Exception\RequestException If the API request fails.
     *
     * @example
     * ```php
     * $sdk = new WetrocloudClient('apikey', 'version');
     * $response = $sdk->createCollection('my_unique_collection');
     * 
     * if (isset($response['success']) && $response['success']) {
     *     echo "Collection created successfully: " . $response['collection_id'];
     * } else {
     *     echo "Error creating collection: " . ($response['error'] ?? 'Unknown error');
     * }
     * ```
     */
    public function createCollection(?string $collectionId = null)
    {
        $data = [
            'collection_id' => $collectionId
        ];
        
        $response = $this->request('POST', 'collection/create/', $data);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return [
            'collection_id' => $response['collection_id'] ?? null,
            'success' => $response['success'] ?? false
        ];
    }

    /**
     * Retrieves a list of all collections from WetroCloud.
     *
     * @return array{
     *     count: int,
     *     next: string|null,
     *     previous: string|null,
     *     collections: array,
     *     error?: string,
     *     response?: array|null
     * } The list of collections with pagination metadata or an error response.
     *
     * @throws \GuzzleHttp\Exception\RequestException If the API request fails.
     */
    public function listCollections()
    {
        $response = $this->request('GET', 'collection/all/');
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return [
            'count' => $response['count'] ?? 0,
            'next' => $response['next'] ?? null,
            'previous' => $response['previous'] ?? null,
            'collections' => $response['results'] ?? []
        ];
    }

    /**
     * Insert a resource into a collection.
     *
     * @param string $collectionId The ID of the collection where the resource will be inserted.
     * @param string $resource The URL or content of the resource.
     * @param string $type The type of resource (e.g., 'web', 'document', etc.).
     * @return array The API response containing the resource ID, success status, and tokens used.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function insertResource(string $collectionId, string $resource, string $type)
    {
        $data = [
            'collection_id' => $collectionId,
            'resource' => $resource,
            'type' => $type
        ];
        
        $response = $this->request('POST', 'resource/insert/', $data);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return [
            'resource_id' => $response['resource_id'] ?? null,
            'success' => $response['success'] ?? false,
            'tokens' => $response['tokens'] ?? 0
        ];
    }

    /**
     * Queries a resource collection with optional JSON schema and streaming support.
     *
     * @param string $collectionId The ID of the collection to query.
     * @param string $requestQuery The query message to be sent.
     * @param string|null $model (Optional) The AI model to use for processing.
     * @param array|null $jsonSchema (Optional) JSON schema for structured output.
     * @param string|null $jsonSchemaRules (Optional) Rules for JSON schema formatting.
     * @param bool $stream (Optional) Whether to stream the response. Default is true.
     * 
     * @return JsonResponse|StreamedResponse The query response or a streaming response if enabled.
     */
    public function queryCollection(
        string $collectionId,
        string $requestQuery,
        ?string $model = null,
        ?array $jsonSchema = null,
        ?string $jsonSchemaRules = null,
        bool $stream = true
    ): array | StreamedResponse {
        $requestData = [
            'collection_id' => $collectionId,
            'request_query' => $requestQuery,
        ];

        if ($jsonSchema) {
            $requestData['json_schema'] = json_encode($jsonSchema);
        }
        if ($jsonSchemaRules) {
            $requestData['json_schema_rules'] = $jsonSchemaRules;
        }
        if ($model) {
            $requestData['model'] = $model;
        }
        
        if ($stream) {
            $response = $this->request('POST', 'collection/query/', $requestData, ['stream' => true]);
            
            return new StreamedResponse(function () use ($response) {
                $buffer = '';
                foreach ($response->getBody() as $chunk) {
                    $buffer .= $chunk;
                    $parts = explode("\n", $buffer);

                    while (count($parts) > 1) {
                        $jsonPart = array_shift($parts);
                        if (!empty(trim($jsonPart))) {
                            try {
                                echo json_encode(json_decode($jsonPart, true)) . "\n";
                                ob_flush();
                                flush();
                            } catch (\Exception $e) {
                                error_log('Error decoding JSON: ' . $e->getMessage());
                            }
                        }
                    }

                    $buffer = implode("\n", $parts);
                }

                if (!empty(trim($buffer))) {
                    try {
                        echo json_encode(json_decode($buffer, true)) . "\n";
                        ob_flush();
                        flush();
                    } catch (\Exception $e) {
                        error_log('Error decoding final JSON: ' . $e->getMessage());
                    }
                }
            });
        }
        
        return $this->request('POST', 'collection/query/', $requestData);
    }

    /**
     * Chat with a collection.
     *
     * @param string $collectionId
     * @param string $message
     * @param array<int, array{role: string, content: string}> $chatHistory
     * @return array
     */
    public function chatWithCollection(string $collectionId, string $message, array $chatHistory)
    {
        $data = [
            'collection_id' => $collectionId,
            'message' => $message,
            'chat_history' => $chatHistory
        ];
        
        $response = $this->request('POST', 'collection/query/', $data, [
            'headers' => [
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (isset($response['error'])) {
            return $response;
        }
        
        return [
            'response' => $response['response'] ?? null,
            'tokens' => $response['tokens'] ?? 0,
            'success' => $response['success'] ?? false
        ];
    }

    /**
     * Remove a resource from a collection.
     *
     * @param string $collectionId The ID of the collection.
     * @param string $resourceId The ID of the resource to remove.
     * @return array The API response.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function removeResource(string $collectionId, string $resourceId): array
    {
        $data = [
            'collection_id' => $collectionId,
            'resource_id' => $resourceId
        ];
        
        return $this->request('DELETE', 'resource/remove/', $data, [
            'form' => true,
            'headers' => [
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }

    /**
     * Delete a collection.
     *
     * @param string $collectionId The ID of the collection to delete.
     * @return array The API response containing a success status and message.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteCollection(string $collectionId): array
    {
        $data = [
            'collection_id' => $collectionId
        ];
        
        return $this->request('DELETE', 'collection/delete/', $data, [
            'headers' => [
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Categorizes a given resource based on predefined categories.
     *
     * @param string $resource The text or data to categorize.
     * @param array $categories List of possible categories.
     * @param string $type The type of resource (e.g., "text").
     * @param array $jsonSchema The expected JSON schema for categorization response.
     * @param string|null $prompt A custom prompt for better categorization.
     * @return array The categorized response.
     * @throws WetrocloudException If the API request fails.
     * 
     * @example
     * 
     * $response = $client->categorizeData(
     *      "match review: John Cena vs. The Rock are fighting",
     *      ["football", "coding", "entertainment", "basketball", "wrestling", "information"],
     *      'text',
     *      ['label' => 'string'],
     *      "Where does this fall under?"
     *  );
     */
    public function categorizeData(
        string $resource,
        array $categories,
        string $type = 'text',
        array $jsonSchema = ['label' => 'string'],
        ?string $prompt = null
    ): array {
        $requestData = [
            'resource' => $resource,
            'type' => $type,
            'json_schema' => $jsonSchema,
            'categories' => $categories,
        ];
        
        if ($prompt) {
            $requestData['prompt'] = $prompt;
        }
        
        return $this->request('POST', 'categorize/', $requestData);
    }

    /**
     * Generates text based on given messages using a specified model.
     *
     * @param array $messages An array of messages with roles (e.g., ['role' => 'user', 'content' => 'Hello'])
     * @param string $model The AI model to use for generation (e.g., "llama-3.3-70b").
     * @return array The generated response from the AI model.
     * @throws WetrocloudException If the API request fails.
     * @example
     * $response = $client->generateText(
     *      [
     *          ['role' => 'user', 'content' => 'What is a large language model?']
     *      ],
     *      'llama-3.3-70b'
     *  );
     * 
     *
     */
    public function generateText(array $messages, string $model = 'llama-3.3-70b'): array
    {
        $requestData = [
            'messages' => json_encode($messages),
            'model' => $model,
        ];
        
        return $this->request('POST', 'text-generation/', $requestData, ['form' => true]);
    }

    /**
     * Extracts text from an image or provides insights based on a query.
     *
     * @param string $imageUrl The URL of the image.
     * @param string $requestQuery The query to ask about the image.
     * @return array The extracted text or insights from the image.
     * @throws WetrocloudException If the API request fails.
     * @example
     *  $client = new WetrocloudClient('<your-api-key>');
     *  $response = $client->imageToText(
     *      'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQBQcwHfud1w3RN25Wgys6Btt_Y-4mPrD2kg&s',
     *      'What animal is this?'
     *  );
     */
    public function imageToText(string $imageUrl, string $requestQuery): array
    {
        $requestData = [
            'image_url' => $imageUrl,
            'request_query' => $requestQuery
        ];
        
        return $this->request('POST', 'image-to-text/', $requestData);
    }

    /**
     * Extracts structured data from a website using WetroCloud's data-extraction API.
     *
     * @param string $websiteUrl The URL of the website to extract data from.
     * @param array $jsonSchema The JSON schema to structure the extracted data.
     * @return array The extracted data or an error message if the request fails.
     * @throws WetrocloudException If the API request fails.
     * 
     * @example
     * $client = new WetrocloudClient('<your-api-key>');
     * $response = $client->extractDataFromWebsite(
     *      'https://example.com',
     *      ['title' => '', 'description' => '']
     *  );

     */
    public function extractDataFromWebsite(string $websiteUrl, array $jsonSchema): array
    {
        $requestData = [
            'website' => $websiteUrl,
            'json_schema' => json_encode($jsonSchema)
        ];
        
        return $this->request('POST', 'data-extraction/', $requestData);
    }
}