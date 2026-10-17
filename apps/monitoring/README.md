# Monitoring

Flux adopts the existing `prometheus` release using `kube-prometheus-stack`
`92.10.0` with default values. Keep `releaseName: prometheus` and both
`targetNamespace` and `storageNamespace` set to `monitoring` during the handoff.

## Before pushing to main

- Back up custom Grafana dashboards and any data you need. Grafana, Prometheus,
  and Alertmanager currently use temporary storage; pod replacement can lose data.
- Save admin credentials privately. The pinned chart reuses Grafana's existing
  password Secret during upgrades; no credentials need to be committed.
- Keep the existing Helm release installed. First reconciliation may perform an
  upgrade even with the same chart version.

## Verify after deployment

Flux deploys automatically after a push to `main`. To reconcile immediately:

```sh
flux reconcile kustomization flux-system --with-source
flux reconcile kustomization monitoring --timeout=15m
flux get helmreleases -A
kubectl -n monitoring get pods
```

Confirm the Flux Kustomization and HelmRelease are Ready, then check Grafana
login, dashboards, and Prometheus targets. Use Git for subsequent Helm changes.

## Persistent storage

Persistence is not enabled in these manifests. Configure the following Helm
values in `helmrelease.yaml` as a separate change after backing up existing data:

| Component | Values path |
| --- | --- |
| Grafana | `grafana.persistence` |
| Prometheus | `prometheus.prometheusSpec.storageSpec.volumeClaimTemplate` |
| Alertmanager | `alertmanager.alertmanagerSpec.storage.volumeClaimTemplate` |

The available `local-path` class keeps data on one node, surviving pod recreation
but not providing replication or automatic recovery from node loss. Its reclaim
policy is `Delete`: deleting a claim can delete its data. New claims do not copy
data from existing temporary volumes. Plan backup/restore and disk capacity before
enabling persistence; use replicated or shared storage if node failover is required.

## Lifecycle

The namespace is excluded from pruning. Removing the HelmRelease from Git still
uninstalls the chart. The ingress currently uses HTTP; TLS is a separate change.
