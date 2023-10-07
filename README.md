# MealPlan

A webapp for managing meal plans.

[![DockerHub](https://img.shields.io/badge/download-DockerHub-blue?logo=docker)](https://hub.docker.com/r/programie/mealplan)
[![GitHub release](https://img.shields.io/github/v/release/Programie/MealPlan)](https://github.com/Programie/MealPlan/releases/latest)

## Features

* Create weekly meal plans
* Get an overview of your most or least cooked meals
* Get notified to prepare your meals before actually cooking the meal (i.e. for marinade)
* Autocomplete meal entries from previous created meal plans
  * Integrate [Tandoor Recipes](https://docs.tandoor.dev) to autocomplete meal entries from recipes in your cookbook

## Installation

Download the [latest release](https://github.com/Programie/MealPlan/releases/latest) and extract it into your webserver directory. Change the document root to the `public` directory.

Alternatively, you might want to use the [Docker image from Docker Hub](https://hub.docker.com/r/programie/mealplan).

## Configuration

The base configuration for the database as well as the application base URL can be configured in the `.env` file or by configuring environment variables in your webserver.

Variables:

```dotenv
APP_URL=https://app-domain.example.com
DATABASE_DRIVER=pdo_mysql
DATABASE_HOST=your.database.host
DATABASE_NAME=database-to-use
DATABASE_USERNAME=username-to-use
DATABASE_PASSWORD=password-to-use
DATABASE_SERVER_VERSION=mariadb-11.0.3
```

MySQL (or MariaDB to be exact) is the only supported database driver. The application might work with another database system as well.

All other configuration is done in `config.yaml` which should be placed in the `config` folder. For default values, you can take a look into the [defaults.yaml](config/defaults.yaml).

### Custom links

You might configure any links which will be shown in the "Links" dropdown.

The links can be configured in the `config.yaml` like in the following example:

```yaml
parameters:
  app.custom-links:
    # Add a link
    - title: "Some link"
      url: "https://example.com"

    # Add another link
    - title: "Another link"
      url: "https://example.org"

    # Add a separator
    - separator: true

    # Add a submenu
    - title: "Submenu"
      links:
        # Link in this submenu
        - title: "A link in a submenu"
          url: "https://example.com/foo"

        # Another submenu in this submenu
        - title: "Sub-Submenu"
          links:
            # Another link
            - title: "Link in the sub-submenu"
              url: "https://example.com/foobar"
```

### Webhooks

It is possible to send a request upon saving a meal plan or for configured notifications.

To configure a webhook which should be called after saving the meal plan, you might use the following configuration in your `config.yaml`:

```yaml
parameters:
  app.save:
    webhook-url: https://example.com/some-webhook-path
```

Requesting a specific URL for configured notifications can be configured like the following in your `config.yaml`:

```yaml
parameters:
  app.notification:
    webhook-url: https://example.com/some-notification-path
```

### Notifications

MealPlan can send notifications for specific meal plan entries. This is useful to not forget to do something important a few hours before actually cooking the meal.

The easiest way to configure notifications is by using the webfrontend while editing the meal plan. But you have to remember to actually add the notification every time you need it.

Another way is to automatically add those notifications based on specific patterns of the meal title. This can be done by using the `app.notification > pattern-times` configuration property in your `config.yaml`:

```yaml
parameters:
  app.notification:
    pattern-times:
      # Configuration which will trigger a notification 1 hour before the meal time
      - patterns:
          - '/some meal/'
          - '/another meal/'
        time: "1H"
        text: "There is something todo"
        space: 1

      # Configuration which will trigger a notification 12 hours before the meal time
      - patterns:
          - '/long preparation meal/'
        time: "12H"
        text: "Something todo"
        space: 1
```

`patterns` is a list of regular expressions which should match the meal title.

The property `time` defines the time till the meal ([ISO 8601 duration format](https://en.wikipedia.org/wiki/ISO_8601#Durations)).

The `text` is simply included in the notification (i.e. for displaying to the user).

The `space` property can be used to restrict the patterns to a specific space. If omitted, the configuration will be used in all spaces.

**Note:** As of now, a notification only requests the notification webhook URL (see the [Webhooks](#webhooks) section).

**Note:** To trigger notifications, you must configure a cronjob or something similar to regularly execute `bin/console trigger-notifications`.

### All Meals

To exclude specific parts of meals or whole meals from the "All Meals" list, you might use the following configuration in your `config.yaml`:

```yaml
parameters:
  # Remove patterns from the meal text
  # This example will list "Remaining of Bread" as "Bread"
  app.grouped-meals.remove-patterns:
    - /^Remaining of /

  # Exclude patterns from the list
  # This example will prevent "meal to exclude" to be included in the "All Meals" list
  app.grouped-meals.exclude-patterns:
    - /meal to exclude/
```

### Tandoor Recipes

To enable the Tandoor Recipes integration for autocompleting meals from your recipes, add the following configuration to your `config.yaml`:

```yaml
parameters:
  app.datasources.provider: tandoor-recipes
  app.datasources.tandoor-recipes:
    base-url: https://your-tandoor-instance.example.com
    api-token: your-api-key
```

You can get the API key in the account settings of your Tandoor Recipes instance.