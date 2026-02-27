# selfhosted-webapps

A monorepo of self-hosted web applications deployed to a **k3s** cluster via **Flux CD**, with automated CI via **Jenkins** (running in-cluster).

## Repository layout

```
.
├── Jenkinsfile                    # CI pipeline (build, test, push, update manifest)
├── apps/
│   ├── php-mysql-demo/
│   │   ├── app/                   # Application source
│   │   │   ├── index.php          # Pass & Play home
│   │   │   ├── demo.php           # Messages demo (MySQL)
│   │   │   ├── about.php, products.php, news.php, contacts.php
│   │   │   ├── includes/          # header, footer, nav, contacts_loader
│   │   │   ├── css/site.css
│   │   │   ├── data/news.json, data/contacts/*.csv
│   │   │   └── Dockerfile
│   │   ├── docs/contacts.md       # Contact directory format & how to edit
│   │   ├── k8s/                   # Kubernetes manifests
│   │   └── kustomization.yaml
│   └── jenkins/                   # Jenkins Helm install (Flux-managed)
│       ├── helmrepository.yaml
│       ├── helmrelease.yaml
│       ├── ingress.yaml           # Traefik → jenkins.uncannydev.com
│       ├── rbac.yaml              # Agent ServiceAccount + Role
│       └── kustomization.yaml
└── clusters/production/
    ├── flux-system/               # Flux bootstrap (do not edit)
    └── apps/
        ├── php-mysql-demo.yaml
        └── jenkins.yaml
```

### Pass & Play company site (php-mysql-demo)

| Route | Page |
|-------|------|
| `/` | Home |
| `/about` | About |
| `/products` (or `/services`) | Products & pricing |
| `/news` | News (from `app/data/news.json`) |
| `/contacts` | Contacts (from `app/data/contacts/*.csv`; see [docs/contacts.md](apps/php-mysql-demo/docs/contacts.md)) |
| `/demo.php` | Messages demo (MySQL) |

---

## Local testing

Test your changes **before** pushing to the repo. No cluster or Docker Hub access needed.

### Option A — PHP built-in server (no MySQL)

Works for all pages **except** `/demo.php` (which requires MySQL).

```bash
cd apps/php-mysql-demo/app
php -S localhost:8000
```

Open **http://localhost:8000** in your browser. Clean URLs like `/about` won't work (no Apache rewrite), so use the `.php` extension directly:

| URL | Page |
|-----|------|
| `http://localhost:8000/` | Home |
| `http://localhost:8000/about.php` | About |
| `http://localhost:8000/products.php` | Products |
| `http://localhost:8000/news.php` | News |
| `http://localhost:8000/contacts.php` | Contacts |

### Option B — Docker build + run (no MySQL)

Builds the same image that ships to production. Clean URLs work (Apache + `.htaccess`).

```bash
cd apps/php-mysql-demo/app
docker build --platform linux/amd64 -t php-mysql-demo:local .
docker run --rm -p 8080:80 php-mysql-demo:local
```

Open **http://localhost:8080**. All pages work (`/about`, `/products`, etc.). The demo page will show a database error (no MySQL), which is expected.

> **Note:** Always use `--platform linux/amd64` when building locally on Apple Silicon. The k3s cluster runs amd64 nodes.

### Option C — Docker Compose (full stack with MySQL)

Create a temporary `docker-compose.yaml` at the repo root (it's gitignored):

```yaml
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: <your-root-password>
      MYSQL_DATABASE: demo
      MYSQL_USER: demo
      MYSQL_PASSWORD: <your-password>
    ports:
      - "3306:3306"
  web:
    build:
      context: apps/php-mysql-demo/app
      platforms:
        - linux/amd64
    ports:
      - "8080:80"
    environment:
      DB_HOST: mysql
      DB_NAME: demo
      DB_USER: demo
      DB_PASS: <your-password>  # must match MYSQL_PASSWORD above
    depends_on:
      - mysql
```

```bash
docker compose up --build
```

Open **http://localhost:8080**. Everything works, including `/demo.php` (messages are stored in the local MySQL).

Stop with `Ctrl+C` then `docker compose down -v` to clean up.

### Option D — PHP lint only (no browser)

Quick syntax check without running anything:

```bash
find apps/php-mysql-demo/app -name "*.php" -exec php -l {} \;
```

---

## How deployment works

Deployment is **fully automated**. When you push to `main`:

```
git push origin main
      │
      ▼
Jenkins detects new commit (webhook or poll)
      │
      ▼
Pipeline runs in k3s (Jenkinsfile):
  1. Checks commit author — skips if "jenkins-ci"
  2. PHP lint (all .php files)
  3. Kaniko builds Docker image (linux/amd64)
  4. Pushes to Docker Hub:
     - lw1n/php-mysql-demo:main-<timestamp>
     - lw1n/php-mysql-demo:sha-<short-sha>
     - lw1n/php-mysql-demo:latest
  5. Clones repo via SSH, updates image tag
     in web-deployment.yaml, commits & pushes
      │
      ▼
Flux detects git change (polls every 1 min)
  → Applies updated deployment
  → Kubernetes rolling update (zero downtime)
      │
      ▼
Jenkins sees its own commit (author: "jenkins-ci")
  → Skips build — no feedback loop
```

**You do not need to manually build images or edit deployment files.** Just push code.

---

## Prerequisites

| Tool | Purpose |
|------|---------|
| Docker | Local testing (Options B/C) |
| PHP 8.x | Local testing (Options A/D) |
| A k3s cluster | Production environment |
| Flux CD bootstrapped | GitOps deployment |
| `kubectl` configured | Talking to your k3s cluster |

---

## Initial cluster setup (already done)

These steps only need to be performed once.

### Jenkins

Jenkins is installed via Flux HelmRelease. Access it at **https://jenkins.uncannydev.com**.

```bash
# Get admin password
kubectl -n jenkins get secret jenkins \
  -o jsonpath='{.data.jenkins-admin-password}' | base64 -d; echo
```

The pipeline job (`php-mysql-demo`) runs the `Jenkinsfile` from the repo root.

### Git SSH key for Jenkins

Jenkins uses the same SSH deploy key as Flux to push image tag updates. The key is copied into the `jenkins` namespace as a secret (never stored in Git):

```bash
# Already done — this is how it was created:
kubectl -n flux-system get secret flux-system -o json \
  | python3 -c "
import json, sys
s = json.load(sys.stdin)
print(json.dumps({
    'apiVersion': 'v1', 'kind': 'Secret',
    'metadata': {'name': 'git-ssh-key', 'namespace': 'jenkins'},
    'type': 'Opaque',
    'data': {
        'ssh-privatekey': s['data']['identity'],
        'known_hosts': s['data']['known_hosts']
    }
}))" | kubectl apply -f -
```

> **Security:** The deploy key has write access to the GitHub repo only. It cannot access other repos, and it lives only in the cluster — never in Git.

---

## Verify the deployment

```bash
# Flux status
flux get kustomizations
flux get helmreleases -n flux-system

# App pods
kubectl -n demo get pods
kubectl -n jenkins get pods

# Current image tag
kubectl -n demo get deployment web \
  -o jsonpath='{.spec.template.spec.containers[0].image}'; echo

# Logs
kubectl -n demo logs -l app=web --tail=50
```

---

## Access the application

| URL | What |
|-----|------|
| `https://uncannydev.com` | Pass & Play site |
| `https://jenkins.uncannydev.com` | Jenkins CI dashboard |

---

## Secrets management

Secrets are **not stored in Git**. They must be created manually via `kubectl`.

### MySQL secret

```bash
kubectl create secret generic mysql-secret \
  --namespace=demo \
  --from-literal=MYSQL_ROOT_PASSWORD='<strong-random-password>' \
  --from-literal=MYSQL_DATABASE='demo' \
  --from-literal=MYSQL_USER='demo' \
  --from-literal=MYSQL_PASSWORD='<strong-random-password>'
```

See `apps/php-mysql-demo/k8s/secret.yaml.example` for the template.

### Docker Hub secret (for Jenkins/Kaniko)

```bash
kubectl create secret docker-registry dockerhub-credentials \
  --namespace=jenkins \
  --docker-server=https://index.docker.io/v1/ \
  --docker-username=YOUR_USERNAME \
  --docker-password=YOUR_ACCESS_TOKEN
```

### Git SSH key (for Jenkins → GitHub push)

```bash
# Copies Flux's deploy key into the jenkins namespace.
# See "Git SSH key for Jenkins" section above.
```

For a more GitOps approach, consider [Mozilla SOPS with Flux](https://fluxcd.io/flux/guides/mozilla-sops/) or [Sealed Secrets](https://github.com/bitnami-labs/sealed-secrets) to encrypt secrets in Git.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Pods stuck in `ImagePullBackOff` | Image doesn't exist or registry is private — check image name and pull secret |
| Web pods in `CrashLoopBackOff` | MySQL not ready yet — wait for the mysql pod to be `Running` first |
| `502 Bad Gateway` from Traefik | Web pods not ready — check readiness probe and pod logs |
| Flux not reconciling | Run `flux get kustomizations` and `flux get sources git` to check status |
| PVC stuck in `Pending` | Verify `local-path` storage class exists: `kubectl get sc` |
| Jenkins build fails (401 Unauthorized) | Docker Hub secret has wrong credentials — recreate it |
| Jenkins can't push to GitHub | `git-ssh-key` secret missing or wrong — recreate from Flux's deploy key |
| Jenkins agent pod stuck in `Pending` | Check node resources: `kubectl describe pod -n jenkins <pod>` |
| Platform mismatch (`ErrImagePull`) | Ensure Kaniko or local Docker build targets `linux/amd64` |
