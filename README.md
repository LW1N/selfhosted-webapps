# selfhosted-webapps (GitOps)

Flux reconciles `clusters/production/` from the `main` branch. Pushing or merging
changes into `main` deploys them automatically; a local commit does not.

## Layout

- `clusters/production/`: Flux configuration and application references.
- `apps/pap_app/`: Pass & Play application and MySQL manifests.
- `apps/uncannydev-home/`: Static home page manifests.
- `apps/jenkins/`: Jenkins Helm release and supporting resources.
- `apps/monitoring/`: Prometheus/Grafana Helm release and Grafana ingress.
  See the [monitoring README](apps/monitoring/README.md) before its first deployment.

## Workflow

Update manifests and Helm values in Git. For Pass & Play, build and push the
application image, then update its image tag in `apps/pap_app/kustomization.yaml`.
Avoid lasting manual changes to Flux-managed resources.

Validate from the repository root before committing:

```sh
kubectl kustomize apps/monitoring > /dev/null
kubectl kustomize clusters/production > /dev/null
git diff --check
git status --short
```

## Secrets

Keep credentials, tokens, private keys, and database backups out of Git. Supply
required Kubernetes Secrets separately or use an encrypted secrets workflow.
`apps/pap_app/k8s/secret.yaml.example` is a template, not a deployable Secret.

## Local development

Ignored development folders are not deployed unless their manifests are added
to a Flux-managed path. The former `php-mysql-demo` app is now `pap_app`.
