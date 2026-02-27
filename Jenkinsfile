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
        DEPLOY_FILE = 'apps/php-mysql-demo/k8s/web-deployment.yaml'
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
                    env.COMMIT_AUTHOR = sh(script: 'git log -1 --format=%an', returnStdout: true).trim()
                    if (env.COMMIT_AUTHOR == 'jenkins-ci') {
                        echo "Commit by jenkins-ci (image tag update) — skipping build."
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
                    env.SHORT_SHA = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    env.BUILD_TS  = sh(script: 'date +%s', returnStdout: true).trim()
                    env.IMAGE_TAG = "main-${env.BUILD_TS}"
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
                        --destination=${IMAGE}:sha-${SHORT_SHA} \
                        --destination=${IMAGE}:latest \
                        --cache=true \
                        --cleanup
                    """
                }
            }
        }

        stage('Update deployment manifest') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('git') {
                    sh """
                    mkdir -p ~/.ssh
                    cp /etc/git-secret/ssh-privatekey ~/.ssh/id_ed25519
                    chmod 600 ~/.ssh/id_ed25519

                    # Route GitHub SSH through port 443 (works behind VPNs/proxies)
                    cat > ~/.ssh/config <<SSHEOF
Host github.com
    HostName ssh.github.com
    Port 443
    User git
    IdentityFile ~/.ssh/id_ed25519
    StrictHostKeyChecking accept-new
SSHEOF
                    chmod 600 ~/.ssh/config

                    # Fetch known_hosts for ssh.github.com:443
                    ssh-keyscan -p 443 ssh.github.com > ~/.ssh/known_hosts 2>/dev/null

                    WORK_DIR=\$(mktemp -d)
                    git clone --depth 1 --branch main ${GIT_REPO} \$WORK_DIR

                    cd \$WORK_DIR
                    sed -i 's|image: ${IMAGE}:.*|image: ${IMAGE}:${IMAGE_TAG}|' ${DEPLOY_FILE}

                    git config user.name "jenkins-ci"
                    git config user.email "jenkins@selfhosted-webapps.local"
                    git add ${DEPLOY_FILE}
                    git diff --cached --quiet && echo "No change to commit" && exit 0
                    git commit -m "deploy: update image to ${IMAGE_TAG}"
                    git push origin main

                    rm -rf \$WORK_DIR
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
                    echo "Build ${env.IMAGE_TAG} (sha-${env.SHORT_SHA}) pushed and deployment manifest updated."
                }
            }
        }
        failure {
            echo "Build failed. Check the logs above."
        }
    }
}
