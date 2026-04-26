# selfhosted-webapps (GitOps)

This repository is the GitOps source of truth for a Flux-managed Kubernetes cluster.
Flux reconciles the cluster and application manifests in this repo and keeps runtime state aligned with Git.

## Repository layout

- `clusters/production/`: cluster entrypoint reconciled by Flux.
  - `clusters/production/kustomization.yaml` includes:
    - `flux-system` components/sync objects
    - app Kustomizations in `clusters/production/apps/`
- `clusters/production/apps/pap_app.yaml`: Flux Kustomization pointing to `./apps/pap_app`.
- `clusters/production/apps/uncannydev-home.yaml`: Flux Kustomization pointing to `./apps/uncannydev-home`.
- `clusters/production/apps/jenkins.yaml`: Flux Kustomization pointing to `./apps/jenkins`.
- `apps/pap_app/`: Kustomize app manifests for namespace, MySQL, web app, service, ingress, and first-run database seed scripts.
- `apps/uncannydev-home/`: Static temporary landing page for `uncannydev.com`.
- `apps/jenkins/`: Jenkins HelmRelease and supporting manifests (namespace, ingress, RBAC, HelmRepository).

## Deploy flow

1. The application image for Pass & Play is built and pushed with tags such as `sha-<shortsha>` and `latest`.
2. The image tag in `apps/pap_app/kustomization.yaml` is updated.
3. Flux detects the Git change and reconciles `clusters/production/`.
4. Kubernetes is updated to the new desired state.

## Secrets and credentials

- Do not commit real credentials to Git.
- `apps/pap_app/k8s/secret.yaml.example` is a template only.
- Create the real MySQL secret in the `demo` namespace, for example:

```bash
kubectl create secret generic mysql-secret \
  --namespace=demo \
  --from-literal=MYSQL_ROOT_PASSWORD='<strong-random-password>' \
  --from-literal=MYSQL_DATABASE='demo' \
  --from-literal=MYSQL_USER='demo' \
  --from-literal=MYSQL_PASSWORD='<strong-random-password>' \
  --from-literal=ADMIN_PASSWORD_HASH='<password-hash>' \
  --from-literal=STANDARD_USERS_JSON='{"user":"<password-hash>"}'
```

## Notes on local-only folders

- The root `.gitignore` excludes local app-development folders such as `cmpe272` and `cmpe272-app`.
- These folders can exist in a local workspace but are not used by Flux reconciliation unless explicitly copied into tracked paths under `apps/` and committed.

## Rename note

- The GitOps app path has been renamed from `php-mysql-demo` to `pap_app`.
- The container image repository is now `docker.io/lw1n/pap_app`.
