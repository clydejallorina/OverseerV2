# OverseerV2

The second iteration of the Overseer games.

The database dump can be found at `database.sql`. Here, it's the master branch that contains the last official live version.

## Setup

1. Install [Docker](https://www.docker.com/) (for dev work, you can also look at installing [Docker Desktop](https://www.docker.com/products/docker-desktop/) to have the containers in an easy-to-view GUI)
2. Create a copy of `.env.dist` named `.env`, and adjust any details in there if needed
3. Run the following command in the base of this repository: `docker compose --profile dev up -d --build`
4. Wait for the build to finish. This may take a while depending on how fast your internet connection and computer are. An initial build is expected to take about 5 minutes.
5. The website should now be accessible in `https://localhost`, with the PHP side directly accessible in `http://localhost:9000`, the Rust side directly accessible in `http://localhost:8000`, the PHPMyAdmin side directly accessible in `http://localhost:8080`, and the MySQL database directly accessible via `localhost:3306`.
