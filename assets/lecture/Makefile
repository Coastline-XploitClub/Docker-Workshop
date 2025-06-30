DATABASE_URL:=postgres://postgres:ccdc-password@localhost:5432/postgres

.PHONY: run-postgres
run-postgres:
	@echo Starting postgres container
	-docker run \
		-d \
		-e POSTGRES_PASSWORD=ccdc-password \
		-v pgdata:/var/lib/postgresql/data \
		-p 5432:5432 \
		postgres:15.1-alpine

.PHONY: run-api-node
run-api-node:
	@echo Starting node api
	cd api-node && \
		DATABASE_URL=${DATABASE_URL} \
		npm run dev

.PHONY: run-client-react
run-client-react:
	@echo Starting react client
	cd client-react && \
		npm run dev
