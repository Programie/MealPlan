<?php
namespace mealplan\datasource;

use GuzzleHttp\Exception\GuzzleException;
use mealplan\datasource\provider\DefaultProvider;
use mealplan\datasource\provider\Provider;
use mealplan\datasource\provider\TandoorRecipes;
use Symfony\Component\DependencyInjection\Container;
use UnexpectedValueException;

class Manager
{
    private Provider $provider;

    public function __construct(string $provider, Container $container)
    {
        switch ($provider) {
            case "default":
                $this->provider = new DefaultProvider;
                break;
            case "tandoor-recipes":
                $config = $container->getParameter("app.datasources.tandoor-recipes");
                $this->provider = new TandoorRecipes($config["base-url"], $config["api-token"], $config["max-requests"] ?? 10, $config["page-size"] ?? 100);
                break;
            default:
                throw new UnexpectedValueException(sprintf("Invalid datasource provider: %s", $provider));
        }
    }

    /**
     * @return Item[]
     * @throws GuzzleException
     */
    public function getItems(): array
    {
        $items = $this->provider->getItems();

        usort($items, function (Item $item1, Item $item2) {
            return strcasecmp($item1->getText(), $item2->getText());
        });

        return $items;
    }
}