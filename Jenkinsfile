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

        stage('Prepare tags') {
            steps {
                script {
                    env.SHORT_SHA = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    env.BUILD_TS  = sh(script: 'date +%s', returnStdout: true).trim()
                }
            }
        }

        stage('Test') {
            steps {
                container('test') {
                    sh 'echo "Running PHP lint..."'
                    sh 'find apps/php-mysql-demo/app -name "*.php" -exec php -l {} \\;'
                }
            }
        }

        stage('Build & Push') {
            when {
                branch 'main'
                changeset 'apps/php-mysql-demo/**'
            }
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
    }

    post {
        success {
            echo "Build ${env.BUILD_TS} (sha-${env.SHORT_SHA}) pushed successfully."
        }
        failure {
            echo "Build failed. Check the logs above."
        }
    }
}
