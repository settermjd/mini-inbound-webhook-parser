# Mini PHP SendGrid Inbound Webhook Parser

This is a small PHP web-based application that shows how to interact with SendGrid's Inbound Webhook Parser.

## Prerequisites/Requirements

To run the code, you will need the following:

- PHP 8.3 with the following extensions:
  - ctype
  - dom
  - json
  - libxml
  - mailparse
  - mbstring
  - phar
  - tokenizer
  - xml
  - xmlwriter
- Composer installed globally
- Docker Desktop

### How to install the mailparse extension

If the mailparse extension is not provided by your operating system's package manager, then the next best way to install it is with Pecl.
To install it that way, assuming that you have Pecl installed and available in your system path, run the following command

```bash
pecl install --alldeps mailparse
```

After the command completes, check that the extension is installed and enabled by running the following command.

```bash
php -m | grep mailparse
```

If you see `mailparse` printed to the terminal, then it is installed and enabled.

Alternatively, you could use the included Docker Compose configuration.

## How to run the project

To run the project, you can either use Composer or the included Docker Compose configuration.

```bash
# Start the project with Composer
composer serve

# Start the project with Docker Compose
docker compose up -d
```