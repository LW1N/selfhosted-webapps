# selfhosted-webapps (GitOps)

This repository is the GitOps source of truth for a Flux-managed Kubernetes cluster.
Flux reconciles the cluster and application manifests in this repo and keeps runtime state aligned with Git.

## Repository layout

- `clusters/production/`: cluster entrypoint reconciled by Flux.
  - `clusters/production/kustomization.yaml` includes:
    - `flux-system` components/sync objects
    - app Kustomizations in `clusters/production/apps/`
- `clusters/production/apps/php-mysql-demo.yaml`: Flux Kustomization pointing to `./apps/php-mysql-demo`.
- `clusters/production/apps/jenkins.yaml`: Flux Kustomization pointing to `./apps/jenkins`.
- `apps/php-mysql-demo/`: Kustomize app manifests for namespace, MySQL, web app, service, and ingress.
- `apps/jenkins/`: Jenkins HelmRelease and supporting manifests (namespace, ingress, RBAC, HelmRepository).

## Deploy flow

1. The application image for `php-mysql-demo` is built and pushed with tags such as `sha-<shortsha>` and `latest`.
2. The image tag in `apps/php-mysql-demo/kustomization.yaml` is updated.
3. Flux detects the Git change and reconciles `clusters/production/`.
4. Kubernetes is updated to the new desired state.

## Secrets and credentials

- Do not commit real credentials to Git.
- `apps/php-mysql-demo/k8s/secret.yaml.example` is a template only.
- Create the real MySQL secret in the `demo` namespace, for example:

```bash
kubectl create secret generic mysql-secret \
  --namespace=demo \
  --from-literal=MYSQL_ROOT_PASSWORD='<strong-random-password>' \
  --from-literal=MYSQL_DATABASE='demo' \
  --from-literal=MYSQL_USER='demo' \
  --from-literal=MYSQL_PASSWORD='<strong-random-password>'
```

## Notes on local-only folders

- The root `.gitignore` excludes local app-development folders such as `cmpe272` and `cmpe272-app`.
- These folders can exist in a local workspace but are not used by Flux reconciliation unless explicitly copied into tracked paths under `apps/` and committed.
