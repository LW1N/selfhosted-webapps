# Monitoring

Flux manages the `prometheus` release using `kube-prometheus-stack` `92.10.0`.
Keep `releaseName: prometheus` and both `targetNamespace` and `storageNamespace`
set to `monitoring` to preserve its existing Helm history.

## Before pushing to main

- Before enabling persistence, back up Grafana's database or export custom
  dashboards, and save any other data you need. Existing temporary data is not
  copied into new volumes. Expect pod replacements and a brief interruption.
- Save admin credentials privately. The pinned chart reuses Grafana's existing
  password Secret during upgrades; no credentials need to be committed.
- Keep the existing Helm release installed; Flux upgrades it in place.

## Verify after deployment

Flux deploys automatically after a push to `main`. To reconcile immediately:

```sh
flux reconcile kustomization flux-system --with-source
flux reconcile kustomization monitoring --timeout=15m
flux get helmreleases -A
kubectl -n monitoring get pods,pvc
```

Confirm the Flux Kustomization and HelmRelease are Ready, then check Grafana
login, dashboards, and Prometheus targets. Confirm all three claims are Bound.
Use Git for subsequent Helm changes.

## Persistent storage

`helmrelease.yaml` enables `ReadWriteOnce` claims using `local-path`:

| Component | Requested size | Retention |
| --- | --- | --- |
| Grafana | 5 GiB | Application data |
| Prometheus | 20 GiB | 10 days or 16 GB of retained blocks, whichever is reached first |
| Alertmanager | 1 GiB | Notification state and silences |

The available `local-path` class keeps data on one node, surviving pod recreation
but not providing replication or automatic recovery from node loss. Its reclaim
policy is `Delete`: deleting a claim can delete its data. Volume expansion is
disabled. Keep separate backups and monitor node disk usage; use replicated or
shared storage if node failover is required.

## Lifecycle

The namespace is excluded from pruning. Removing the HelmRelease from Git still
uninstalls the chart. The ingress currently uses HTTP; TLS is a separate change.
