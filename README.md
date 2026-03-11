# selfhosted-webapps (GitOps)

This repo is the **GitOps source of truth** for a Kubernetes cluster managed by **Flux**. It contains the **cluster configuration** and **application manifests** (Kustomize) used to deploy/update workloads.

## Repo layout

- **`clusters/`**: cluster entrypoints (e.g. `clusters/production/`) that tell Flux what to reconcile.
- **`apps/`**: per-app manifests:
  - **`apps/php-mysql-demo/`**: “Pass & Play” PHP + MySQL demo app (namespace, MySQL, web deployment, ingress). Image tag is set in `apps/php-mysql-demo/kustomization.yaml`.
  - **`apps/jenkins/`**: Jenkins deployment (HelmRelease + ingress + RBAC).
  - **`apps/flux-image-automation/`**: placeholder directory (currently empty).

## What Flux reconciles

Start here:

- **`clusters/production/kustomization.yaml`**: includes Flux system manifests and app Kustomizations (e.g. Jenkins, php-mysql-demo).

## App source code (optional, local-only)

You may also see `cmpe272/` and `cmpe272-app/` in a local workspace. Those are **ignored by `.gitignore`** and are not part of the GitOps manifests:

- `cmpe272/` contains the PHP app source, `Dockerfile`, and a Jenkins pipeline that can build/push `docker.io/lw1n/php-mysql-demo`.
- `cmpe272-app/` appears to be another variant/export of the same pipeline.

If you have those directories locally, use their READMEs for dev/test instructions (including the contacts CSV format docs under `cmpe272/docs/contacts.md`).
