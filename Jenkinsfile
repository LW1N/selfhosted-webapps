pipeline {
    agent {
        kubernetes {
            yaml '''
apiVersion: v1
kind: Pod
metadata:
  labels:
    jenkins: agent
spec:
  serviceAccountName: jenkins-agent
  containers:
    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      command: ["/busybox/cat"]
      tty: true
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
      resources:
        requests:
          cpu: 100m
          memory: 256Mi
        limits:
          cpu: "1"
          memory: 1Gi
    - name: test
      image: php:8.2-cli
      command: ["cat"]
      tty: true
      resources:
        requests:
          cpu: 50m
          memory: 128Mi
        limits:
          cpu: 250m
          memory: 256Mi
    - name: git
      image: bitnami/git:latest
      command: ["cat"]
      tty: true
      volumeMounts:
        - name: git-ssh
          mountPath: /etc/git-secret
          readOnly: true
      resources:
        requests:
          cpu: 50m
          memory: 64Mi
        limits:
          cpu: 250m
          memory: 128Mi
    - name: kubectl
      image: bitnami/kubectl:latest
      command: ["cat"]
      tty: true
      resources:
        requests:
          cpu: 50m
          memory: 64Mi
        limits:
          cpu: 250m
          memory: 128Mi
  volumes:
    - name: docker-config
      secret:
        secretName: dockerhub-credentials
        items:
          - key: .dockerconfigjson
            path: config.json
    - name: git-ssh
      secret:
        secretName: git-ssh-key
        defaultMode: 0400
'''
        }
    }

    environment {
        IMAGE = 'docker.io/lw1n/php-mysql-demo'
        IMAGE_TAG = 'sha-placeholder'
        KUSTOMIZATION_FILE = 'apps/php-mysql-demo/kustomization.yaml'
        APP_PATH = 'apps/php-mysql-demo'
        GIT_REPO = 'git@github.com:LW1N/selfhosted-webapps.git'
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Check commit author') {
            steps {
                script {
                    env.COMMIT_AUTHOR = sh(
                        script: 'git log -1 --format=%an',
                        returnStdout: true
                    ).trim()

                    if (env.COMMIT_AUTHOR == 'jenkins-ci') {
                        echo "Commit by jenkins-ci — skipping build."
                        env.SKIP_BUILD = 'true'
                    } else {
                        env.SKIP_BUILD = 'false'
                    }
                }
            }
        }

        stage('Prepare tags') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                script {
                    env.SHORT_SHA = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    env.IMAGE_TAG = "sha-${env.SHORT_SHA}"
                }
            }
        }

        stage('Test') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('test') {
                    sh 'echo "Running PHP lint..."'
                    sh 'find apps/php-mysql-demo/app -name "*.php" -exec php -l {} \\;'
                }
            }
        }

        stage('Build & Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kaniko') {
                    sh """
                    /kaniko/executor \
                        --context=dir://\$(pwd)/apps/php-mysql-demo/app \
                        --dockerfile=Dockerfile \
                        --destination=${IMAGE}:${IMAGE_TAG} \
                        --destination=${IMAGE}:latest \
                        --cache=true \
                        --cleanup
                    """
                }
            }
        }

        stage('Update kustomization') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                sh """
                set -e
                # Update images.newTag in kustomization.yaml (sed is universally available)
                sed -i 's|newTag: .*|newTag: ${IMAGE_TAG}|' ${KUSTOMIZATION_FILE}
                """
            }
        }

        stage('Pre-deploy Validation') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kubectl') {
                    sh """
                    set -e
                    echo "Validating manifests against cluster (server-side dry-run)..."
                    kubectl apply -k ${APP_PATH} --dry-run=server
                    echo "Validation passed."
                    """
                }
            }
        }

        stage('Commit and Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('git') {
                    sh """
                    set -euo pipefail

                    export HOME=/var/jenkins_home
                    mkdir -p "\$HOME/.ssh"
                    chmod 700 "\$HOME/.ssh"

                    cp /etc/git-secret/ssh-privatekey "\$HOME/.ssh/id_ed25519"
                    chmod 600 "\$HOME/.ssh/id_ed25519"

                    ssh-keyscan -4 -p 443 ssh.github.com > "\$HOME/.ssh/known_hosts" 2>/dev/null
                    chmod 644 "\$HOME/.ssh/known_hosts"

                    cat > "\$HOME/.ssh/config" <<'SSHEOF'
Host github.com
  HostName ssh.github.com
  Port 443
  User git
  IdentityFile /var/jenkins_home/.ssh/id_ed25519
  IdentitiesOnly yes
  AddressFamily inet
  StrictHostKeyChecking yes
  UserKnownHostsFile /var/jenkins_home/.ssh/known_hosts
SSHEOF
                    chmod 600 "\$HOME/.ssh/config"

                    export GIT_SSH_COMMAND="ssh -F \$HOME/.ssh/config -o IdentitiesOnly=yes"

                    git config user.name "jenkins-ci"
                    git config user.email "jenkins@selfhosted-webapps.local"

                    git add ${KUSTOMIZATION_FILE}
                    git diff --cached --quiet && echo "No change to commit" && exit 0

                    git commit -m "deploy: update image to ${IMAGE_TAG}"
                    git push origin main
                    """
                }
            }
        }
    }

    post {
        success {
            script {
                if (env.SKIP_BUILD == 'true') {
                    echo "Skipped (commit by ${env.COMMIT_AUTHOR})."
                } else {
                    echo "Image ${IMAGE_TAG} built, pushed, and deployment promoted via kustomization."
                }
            }
        }
        failure {
            echo "Build failed. Check logs."
        }
    }
}
