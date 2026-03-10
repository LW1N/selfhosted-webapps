# Pass & Play (php-mysql-demo)

PHP + MySQL demo site (Pass & Play company site). Built by Jenkins, image promoted via the [selfhosted-webapps](https://github.com/LW1N/selfhosted-webapps) GitOps repo.

## Local development

```bash
# PHP lint
find . -name "*.php" -exec php -l {} \;

# Built-in server (no MySQL; /demo.php will error)
php -S localhost:8000

# Docker build (use linux/amd64 for k3s)
docker build --platform linux/amd64 -t php-mysql-demo:local .
docker run --rm -p 8080:80 php-mysql-demo:local
```

## CI/CD

- Push to `main` triggers Jenkins (webhook from this repo).
- Jenkins builds the image, pushes `docker.io/lw1n/php-mysql-demo:sha-<shortsha>` and `:latest`, then updates the GitOps repo’s `apps/php-mysql-demo/kustomization.yaml` and pushes. Flux deploys.

## Docs

- [Contacts directory format](docs/contacts.md)
