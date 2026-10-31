# Imagen del frontend: en desarrollo corre Vite; en producción sirve el build con Nginx sin root.
# Documentado en docs/operacion/docker.md

FROM node:24.22.1-alpine AS dev
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
USER node
CMD ["npm", "run", "dev"]

FROM dev AS build
USER root
COPY frontend/ ./
RUN npm run build

FROM nginxinc/nginx-unprivileged:1.30.6-alpine AS prod
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
