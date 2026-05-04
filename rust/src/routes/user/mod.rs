#![allow(dead_code)] // No use for User yet

use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use axum::{Extension, RequestPartsExt as _};
use sqlx::MySqlPool;

use crate::error::*;
use crate::session::Session;

#[derive(Debug, Clone)]
pub struct User {
    pub id: i64,
    pub username: String,
    pub password_hash: String,
}

impl User {
    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Self>> {
        Ok(sqlx::query_as!(
            User,
            "SELECT id, username, password as password_hash FROM Users WHERE id = ?",
            id
        )
        .fetch_optional(db)
        .await?)
    }

    pub async fn load_by_username(username: &str, db: &MySqlPool) -> Result<Option<Self>> {
        Ok(sqlx::query_as!(
            User,
            "SELECT id, username, password as password_hash FROM Users WHERE username = ?",
            username
        )
        .fetch_optional(db)
        .await?)
    }
}

impl<S> FromRequestParts<S> for User
where
    S: Send + Sync,
{
    type Rejection = Error;

    async fn from_request_parts(req: &mut Parts, _state: &S) -> Result<Self> {
        let session = req.extract::<Session>().await?;
        let Extension(db): Extension<MySqlPool> = req.extract().await?;

        let user_id = session
            .get_integer("userid")
            .ok_or(anyhow!("Not logged in"))??;
        let user = User::load(user_id, &db)
            .await?
            .ok_or(anyhow!("User {} not logged in", user_id))?;

        Ok(user)
    }
}
