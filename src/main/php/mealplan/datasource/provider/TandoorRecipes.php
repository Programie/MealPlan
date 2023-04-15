<?php
namespace mealplan\datasource\provider;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use mealplan\datasource\Item;

class TandoorRecipes implements Provider
{
    private Client $client;

    public function __construct(
        private readonly string $baseUrl,
        string                  $apiToken,
        private readonly int    $maxPages = 10,
        private readonly int    $pageSize = 100
    )
    {
        $this->client = new Client([
            "base_uri" => rtrim($this->baseUrl, "/") . "/",
            RequestOptions::HEADERS => [
                "Authorization" => sprintf("Bearer %s", $apiToken)
            ]
        ]);
    }

    /**
     * @return array|Item[]
     * @throws GuzzleException
     */
    public function getItems(): array
    {
        $items = [];

        for ($page = 1; $page < $this->maxPages; $page++) {
            $response = $this->client->get("api/recipe/", [
                RequestOptions::QUERY => [
                    "page" => $page,
                    "page_size" => $this->pageSize
                ]
            ]);

            $json = json_decode($response->getBody(), true);

            $results = $json["results"] ?? [];

            foreach ($results as $result) {
                $id = $result["id"] ?? null;
                $name = $result["name"] ?? null;

                if ($id === null or $name === null) {
                    continue;
                }

                $items[] = new Item($name, sprintf("%s/view/recipe/%d", $this->baseUrl, $id));
            }

            if ($json["next"] === null) {
                break;
            }
        }

        return $items;
    }
}