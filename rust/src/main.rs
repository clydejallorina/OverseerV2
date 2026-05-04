use axum::routing::{get, post};
use axum::{Extension, Router};
use sqlx::MySqlPool;
use tokio::net::TcpListener;
use tower_cookies::CookieManagerLayer;
use tower_http::services::ServeDir;

use crate::broadcast::BroadcastMessage;
use crate::error::Result;
use crate::routes::character::colour::character_colour_post;
use crate::routes::character::dreamer::character_dreamer_post;
use crate::routes::character::gates::debug_clear;
use crate::routes::character::symbol::character_symbol_post;
use crate::routes::overview::overview_get;
use crate::routes::sse::sse_get;
use crate::routes::waste_time::waste_time;

mod achievement;
mod broadcast;
mod error;
mod routes;
mod session;

#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt()
        .with_env_filter(
            "debug,overseer_reboot=trace,sqlx::query=warn,sqlx_mysql::connection::tls=warn",
        )
        .init();

    tracing::debug!(
        "Sessions are located at {}",
        std::env::var("OVERSEER_PHP_SESSIONS_ROOT")?
    );
    let db_url = std::env::var("DATABASE_URL")?;
    let db = MySqlPool::connect(db_url.as_str()).await?;

    let (sse, _) = tokio::sync::broadcast::channel::<BroadcastMessage>(100);

    let app = Router::new()
        .route("/", get(async || "hey, you're on the wrong index page!"))
        .route("/sse", get(sse_get))
        .route("/overview", get(overview_get))
        .route("/character/colour", post(character_colour_post))
        .route("/character/dreamer", post(character_dreamer_post))
        .route("/character/symbol", post(character_symbol_post))
        .route("/character/debug-clear", post(debug_clear))
        .route("/waste-time", post(waste_time))
        .nest_service("/static", ServeDir::new("static"))
        .layer(CookieManagerLayer::new())
        .route_layer(Extension(db))
        .route_layer(Extension(sse));

    let listener = TcpListener::bind("0.0.0.0:80").await?;
    axum::serve(listener, app).await?;

    Ok(())
}
