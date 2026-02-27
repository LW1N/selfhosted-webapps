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
'''
        }
    }

    environment {
        IMAGE = 'docker.io/lw1n/php-mysql-demo'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Skip Flux commits') {
            steps {
                script {
                    def author = sh(script: 'git log -1 --format=%an', returnStdout: true).trim()
                    if (author == 'flux-image-automation') {
                        echo "Commit by Flux image automation — nothing to build."
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
                        --destination=${IMAGE}:main-${BUILD_TS} \
                        --destination=${IMAGE}:sha-${SHORT_SHA} \
                        --destination=${IMAGE}:latest \
                        --cache=true \
                        --cleanup
                    """
                }
            }
        }

        stage('Trigger Flux') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kubectl') {
                    sh """
                    echo "Triggering Flux image scan..."
                    kubectl -n flux-system annotate --overwrite \
                        imagerepository/php-mysql-demo \
                        reconcile.fluxcd.io/requestedAt=\$(date +%s)

                    sleep 10

                    echo "Triggering Flux image update automation..."
                    kubectl -n flux-system annotate --overwrite \
                        imageupdateautomation/selfhosted-webapps \
                        reconcile.fluxcd.io/requestedAt=\$(date +%s)
                    """
                }
            }
        }
    }

    post {
        success {
            echo "Build ${env.BUILD_TS} (sha-${env.SHORT_SHA}) pushed and Flux notified."
        }
        failure {
            echo "Build failed. Check the logs above."
        }
    }
}
