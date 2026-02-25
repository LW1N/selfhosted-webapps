# selfhosted-webapps

A monorepo of self-hosted web applications deployed to a **k3s** cluster via **Flux CD**.

## Repository layout

```
.
├── apps/                          # Application source + manifests
│   └── php-mysql-demo/
│       ├── app/
│       │   ├── index.php          # Pass & Play home
│       │   ├── demo.php            # Messages demo (MySQL)
│       │   ├── about.php, products.php, news.php, contacts.php
│       │   ├── includes/           # header, footer, nav, contacts_loader
│       │   ├── css/site.css
│       │   ├── data/news.json, data/contacts/*.csv
│       │   └── Dockerfile
│       ├── docs/contacts.md       # Contact directory format & how to edit
│       ├── k8s/
│       │   ├── namespace.yaml
│       │   ├── secret.yaml        # DB credentials (stringData)
│       │   ├── mysql-pvc.yaml     # 1 Gi PVC (local-path)
│       │   ├── mysql-deployment.yaml
│       │   ├── mysql-service.yaml
│       │   ├── web-deployment.yaml
│       │   ├── web-service.yaml
│       │   ├── ingress.yaml       # Traefik → web.demo.local
│       │   └── ghcr-pull-secret.yaml  # (optional) private registry
│       └── kustomization.yaml
└── clusters/
    └── production/
        ├── flux-system/           # Flux bootstrap (do not edit)
        ├── apps/
        │   └── php-mysql-demo.yaml  # Flux Kustomization
        └── kustomization.yaml
```

### Pass & Play company site (php-mysql-demo)

The app serves a small company site for **Pass & Play** (Discord-like community platform) plus the original messages demo:

- **/** — Home  
- **/about** — About  
- **/products** (or **/services**) — Products & pricing  
- **/news** — News (from `app/data/news.json`)  
- **/contacts** — Contacts (from `app/data/contacts/*.csv`; see [docs/contacts.md](apps/php-mysql-demo/docs/contacts.md))  
- **/demo.php** — Messages demo (MySQL)

Contacts are stored in CSV files under `app/data/contacts/`; no database is used for contacts. See `apps/php-mysql-demo/docs/contacts.md` for the file format and how to add or edit contacts.

---

## Prerequisites

| Tool | Purpose |
|------|---------|
| Docker (or Podman) | Build & push the container image |
| A k3s cluster | Target environment |
| Flux CD bootstrapped | Pointing at `clusters/production` on the `main` branch |
| `kubectl` configured | Talking to your k3s cluster |
| `/etc/hosts` entry | Map `web.demo.local` to your node IP |

---

## 1. Build & push the Docker image

### Option A — Public image (no pull secret needed)

```bash
# Variables
export IMAGE=ghcr.io/<YOUR_GITHUB_USER>/php-mysql-demo:latest

# Build
docker build -t "$IMAGE" apps/php-mysql-demo/app/

# Log in to GitHub Container Registry
echo "$GITHUB_PAT" | docker login ghcr.io -u <YOUR_GITHUB_USER> --password-stdin

# Push
docker push "$IMAGE"

# Make the GHCR package public:
#   https://github.com/users/<YOUR_GITHUB_USER>/packages/container/php-mysql-demo/settings
#   → Change visibility to Public
```

### Option B — Private image (pull secret required)

```bash
# Build & push (same as above)
export IMAGE=ghcr.io/<YOUR_GITHUB_USER>/php-mysql-demo:latest
docker build -t "$IMAGE" apps/php-mysql-demo/app/
echo "$GITHUB_PAT" | docker login ghcr.io -u <YOUR_GITHUB_USER> --password-stdin
docker push "$IMAGE"

# Generate the pull-secret value
kubectl create secret docker-registry ghcr-pull-secret \
  --namespace=demo \
  --docker-server=ghcr.io \
  --docker-username=<YOUR_GITHUB_USER> \
  --docker-password="$GITHUB_PAT" \
  --dry-run=client -o jsonpath='{.data.\.dockerconfigjson}'

# Paste the base64 output into k8s/ghcr-pull-secret.yaml (replacing REPLACE_WITH_BASE64_DOCKER_CONFIG)
```

Then enable the pull secret in two places:

1. **`apps/php-mysql-demo/kustomization.yaml`** — uncomment `- k8s/ghcr-pull-secret.yaml`
2. **`apps/php-mysql-demo/k8s/web-deployment.yaml`** — uncomment the `imagePullSecrets` block

---

## 2. Set the image reference

Edit `apps/php-mysql-demo/k8s/web-deployment.yaml` and replace the placeholder:

```yaml
image: REPLACE_WITH_YOUR_IMAGE   # ← change this
```

with your actual image, for example:

```yaml
image: ghcr.io/myuser/php-mysql-demo:latest
```

---

## 3. Commit & push

```bash
git add -A
git commit -m "feat: add php-mysql-demo app"
git push origin main
```

Flux watches the `main` branch and will reconcile within ~1 minute.

---

## 4. Verify the deployment

```bash
# Force an immediate reconciliation (optional)
flux reconcile kustomization flux-system --with-source

# Check Flux Kustomization status
flux get kustomizations

# Watch resources in the demo namespace
kubectl -n demo get pods -w
kubectl -n demo get svc
kubectl -n demo get ingress

# Check logs
kubectl -n demo logs -l app=web --tail=50
kubectl -n demo logs -l app=mysql --tail=50
```

Expected output when healthy:

```
NAME                     READY   STATUS    RESTARTS   AGE
mysql-xxxxxxxxx-xxxxx    1/1     Running   0          2m
web-xxxxxxxxx-xxxxx      1/1     Running   0          2m
web-xxxxxxxxx-xxxxx      1/1     Running   0          2m
```

---

## 5. Access the application

Add an entry to `/etc/hosts` on the machine where you'll open a browser:

```
<NODE_IP>   web.demo.local
```

Replace `<NODE_IP>` with the WireGuard-accessible IP of one of your k3s nodes.

Then open **http://web.demo.local** in your browser. You should see the Pass & Play home page. Use **Try the demo** in the nav for the message form (MySQL).

---

## 6. Updating the app (Flux CD)

Code changes **do not** take effect just by pushing to Git. The app runs from a **Docker image**; Flux applies what’s in the repo (manifests), and Kubernetes runs the image you built. To deploy updates without breaking the app:

1. **Rebuild and push the image** (from repo root):

```bash
cd apps/php-mysql-demo/app
docker buildx build --platform linux/amd64 -t lw1n/php-mysql-demo:latest --push .
```

2. **Bump the rollout annotation** in `apps/php-mysql-demo/k8s/web-deployment.yaml` so Flux triggers a new rollout (pods will pull the updated image):

```yaml
annotations:
  kubectl.kubernetes.io/restartedAt: "2026-02-24T00:00:00Z"   # ← change to a new timestamp
```

3. **Commit and push** (including the annotation change):

```bash
git add -A
git commit -m "Update app"
git push origin main
```

Flux reconciles within ~1 minute. The new annotation causes a rolling rollout: new pods pull `imagePullPolicy: Always` with the `:latest` image, so they run your new code. The old pods are only replaced after the new ones are ready, so the app stays up.

**If you only push Git without rebuilding the image:** the running pods keep the previous image; your latest code will not run until you rebuild, push the image, and bump the annotation again.

---

## Credentials (demo defaults)

| Key | Value |
|-----|-------|
| `MYSQL_ROOT_PASSWORD` | `rootpass123` |
| `MYSQL_DATABASE` | `demo` |
| `MYSQL_USER` | `demo` |
| `MYSQL_PASSWORD` | `demopass456` |

These are stored in `apps/php-mysql-demo/k8s/secret.yaml`. For production, use Sealed Secrets or SOPS.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Pods stuck in `ImagePullBackOff` | Image doesn't exist or registry is private — check image name and pull secret |
| Web pods in `CrashLoopBackOff` | MySQL not ready yet — wait for the mysql pod to be `Running` first |
| `502 Bad Gateway` from Traefik | Web pods not ready — check readiness probe and pod logs |
| Flux not reconciling | Run `flux get kustomizations` and `flux get sources git` to check status |
| PVC stuck in `Pending` | Verify `local-path` storage class exists: `kubectl get sc` |
