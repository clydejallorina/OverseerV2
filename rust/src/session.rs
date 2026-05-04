use std::collections::HashMap;
use std::fmt::Display;
use std::hash::Hash;
use std::path::Path;

use axum::RequestPartsExt;
use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use sqlx::types::JsonValue;
use tower_cookies::Cookies;

use crate::error::*;

pub struct Session(HashMap<String, PhpValue>);

impl Session {
    pub fn get_string(&self, key: &str) -> Option<Result<&str>> {
        self.0.get(key).map(|value| match value {
            PhpValue::String(string) => Ok(string.as_ref()),
            _ => Err(anyhow!("{key} is not string"))?,
        })
    }

    pub fn get_integer(&self, key: &str) -> Option<Result<i64>> {
        self.0.get(key).map(|value| match value {
            PhpValue::Integer(integer) => Ok(*integer),
            _ => Err(anyhow!("{key} is not integer"))?,
        })
    }
}

impl<S> FromRequestParts<S> for Session
where
    S: Send + Sync,
{
    type Rejection = Error;

    async fn from_request_parts(parts: &mut Parts, _: &S) -> Result<Self> {
        let cookies: Cookies = parts.extract().await.map_err(|(status, msg)| {
            anyhow!("Failed to extract cookies, status {status}: {msg}")
        })?;
        let cookie = cookies
            .get("PHPSESSID")
            .ok_or(anyhow!("No PHP session cookie"))?;
        let session =
            load_session(cookie.value().to_string())?.ok_or(anyhow!("No PHP session file"))?;
        Ok(Session(session))
    }
}

#[derive(Debug, Clone)]
enum PhpValue {
    String(String),
    Integer(i64),
    Float(f64),
    Boolean(bool),
    Array(HashMap<PhpValue, PhpValue>),
    Null,
}
impl PartialEq for PhpValue {
    fn eq(&self, other: &Self) -> bool {
        match (self, other) {
            (PhpValue::String(a), PhpValue::String(b)) => a == b,
            (PhpValue::Integer(a), PhpValue::Integer(b)) => a == b,
            (PhpValue::Float(a), PhpValue::Float(b)) => a == b,
            (PhpValue::Boolean(a), PhpValue::Boolean(b)) => a == b,
            (PhpValue::Array(a), PhpValue::Array(b)) => {
                a.len() == b.len() && a.iter().all(|(k, v)| b.get(k) == Some(v))
            }
            (PhpValue::Null, PhpValue::Null) => true,
            _ => false,
        }
    }
}
impl Eq for PhpValue {}
impl Hash for PhpValue {
    fn hash<H: std::hash::Hasher>(&self, state: &mut H) {
        match self {
            PhpValue::String(s) => s.hash(state),
            PhpValue::Integer(i) => i.hash(state),
            PhpValue::Float(f) => f.to_bits().hash(state),
            PhpValue::Boolean(b) => b.hash(state),
            PhpValue::Array(map) => {
                for (k, v) in map {
                    k.hash(state);
                    v.hash(state);
                }
            }
            PhpValue::Null => 0_u8.hash(state),
        }
    }
}
impl Display for PhpValue {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PhpValue::String(s) => write!(f, "{}", s),
            PhpValue::Integer(i) => write!(f, "{}", i),
            PhpValue::Float(fl) => write!(f, "{}", fl),
            PhpValue::Boolean(b) => write!(f, "{}", b),
            PhpValue::Array(map) => {
                let mut entries: Vec<String> =
                    map.iter().map(|(k, v)| format!("{} => {}", k, v)).collect();
                entries.sort();
                write!(f, "[{}]", entries.join(", "))
            }
            PhpValue::Null => write!(f, "null"),
        }
    }
}
impl From<JsonValue> for PhpValue {
    fn from(value: JsonValue) -> Self {
        match value {
            JsonValue::String(s) => PhpValue::String(s),
            JsonValue::Number(n) if n.is_i64() => PhpValue::Integer(n.as_i64().unwrap()),
            JsonValue::Number(n) => PhpValue::Float(n.as_f64().unwrap()),
            JsonValue::Bool(b) => PhpValue::Boolean(b),
            JsonValue::Object(map) => {
                let mut php_map = HashMap::new();
                for (k, v) in map {
                    php_map.insert(PhpValue::String(k), v.into());
                }
                PhpValue::Array(php_map)
            }
            JsonValue::Null => PhpValue::Null,
            JsonValue::Array(arr) => {
                let mut php_array = HashMap::new();
                for (i, v) in arr.into_iter().enumerate() {
                    php_array.insert(PhpValue::Integer(i as i64), v.into());
                }
                PhpValue::Array(php_array)
            }
        }
    }
}
impl From<PhpValue> for JsonValue {
    fn from(value: PhpValue) -> Self {
        match value {
            PhpValue::String(s) => JsonValue::String(s),
            PhpValue::Integer(i) => JsonValue::Number(serde_json::Number::from(i)),
            PhpValue::Float(f) => JsonValue::Number(serde_json::Number::from_f64(f).unwrap()),
            PhpValue::Boolean(b) => JsonValue::Bool(b),
            PhpValue::Array(map) => {
                let mut json_map = serde_json::Map::new();
                for (k, v) in map {
                    json_map.insert(k.to_string(), v.into());
                }
                JsonValue::Object(json_map)
            }
            PhpValue::Null => JsonValue::Null,
        }
    }
}

#[derive(Debug)]
enum PhpDeserializeError {
    ExpectedPipe,
    ExpectedColon,
    ExpectedSemicolon,
    ExpectedQuote,
    ExpectedOpenBrace,
    ExpectedCloseBrace,
    UnknownDatatype(String),
}
impl Display for PhpDeserializeError {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PhpDeserializeError::ExpectedPipe => write!(f, "expected pipe character '|'"),
            PhpDeserializeError::ExpectedColon => write!(f, "expected colon character ':'"),
            PhpDeserializeError::ExpectedSemicolon => write!(f, "expected semicolon character ';'"),
            PhpDeserializeError::ExpectedQuote => write!(f, "expected quote character '\"'"),
            PhpDeserializeError::ExpectedOpenBrace => {
                write!(f, "expected open brace character '{{'")
            }
            PhpDeserializeError::ExpectedCloseBrace => {
                write!(f, "expected close brace character '}}'")
            }
            PhpDeserializeError::UnknownDatatype(datatype) => {
                write!(f, "unknown datatype '{}'", datatype)
            }
        }
    }
}
impl std::error::Error for PhpDeserializeError {}

fn load_session(session_id: String) -> Result<Option<HashMap<String, PhpValue>>> {
    let session_path = get_session_path(&session_id)?;
    tracing::debug!("Loading session at {}", session_path);
    if !Path::new(&session_path).exists() {
        tracing::debug!("Session does not exist");
        return Ok(None);
    }
    let session_data = std::fs::read_to_string(session_path)?;
    deserialize_session(session_data.as_str()).map(Some)
}

fn deserialize_session(session_data: &str) -> Result<HashMap<String, PhpValue>> {
    let mut session_data = session_data;
    let mut session_map = HashMap::new();
    while !session_data.is_empty() {
        let (name, value, session_data_) = deserialize_key_value(session_data)?;
        session_map.insert(name.to_string(), value);
        session_data = session_data_;
    }
    Ok(session_map)
}

fn deserialize_key_value(session_data: &str) -> Result<(String, PhpValue, &str)> {
    let (name, session_data) = session_data
        .split_once('|')
        .ok_or(PhpDeserializeError::ExpectedPipe)?;
    let (value, session_data) = deserialize_value(session_data)?;
    Ok((name.to_string(), value, session_data))
}

fn deserialize_value(session_data: &str) -> Result<(PhpValue, &str)> {
    let (datatype, session_data) = session_data
        .split_once(':')
        .ok_or(PhpDeserializeError::ExpectedColon)?;
    match datatype {
        "i" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            let value = value.parse::<i64>()?;
            Ok((PhpValue::Integer(value), session_data))
        }
        "d" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            let value = value.parse::<f64>()?;
            Ok((PhpValue::Float(value), session_data))
        }
        "b" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            let value = value.parse::<i64>()? != 0;
            Ok((PhpValue::Boolean(value), session_data))
        }
        "s" => {
            let (length, session_data) = session_data
                .split_once(':')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            let length: usize = length.parse()?;
            let (_, session_data) = session_data
                .split_once('"')
                .ok_or(PhpDeserializeError::ExpectedQuote)?;
            let (value, session_data) = session_data.split_at(length);
            let (_, session_data) = session_data
                .split_once('"')
                .ok_or(PhpDeserializeError::ExpectedQuote)?;
            let (_, session_data) = session_data
                .split_once(';')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            Ok((PhpValue::String(value.to_string()), session_data))
        }
        "a" => {
            let mut map = HashMap::new();
            let (length, session_data) = session_data
                .split_once(':')
                .ok_or(PhpDeserializeError::ExpectedSemicolon)?;
            let length: usize = length.parse()?;
            let (_, mut session_data) = session_data
                .split_once('{')
                .ok_or(PhpDeserializeError::ExpectedOpenBrace)?;
            for _ in 0..length {
                let (key, session_data_) = deserialize_value(session_data)?;
                let (value, session_data_) = deserialize_value(session_data_)?;
                map.insert(key, value);
                session_data = session_data_;
            }
            let (_, session_data) = session_data
                .split_once('}')
                .ok_or(PhpDeserializeError::ExpectedCloseBrace)?;
            Ok((PhpValue::Array(map), session_data))
        }
        "n" => Ok((PhpValue::Null, session_data)),
        _ => Err(PhpDeserializeError::UnknownDatatype(datatype.to_string()).into()),
    }
}

fn get_session_path(session_id: &str) -> Result<String> {
    tracing::debug!("id {}", session_id);
    let root = std::env::var("OVERSEER_PHP_SESSIONS_ROOT")?;
    tracing::debug!("root {}", root);
    Ok(format!("{}/sess_{}", root, session_id))
}

#[allow(dead_code)]
fn save_session(session_id: &str, session_data: HashMap<String, PhpValue>) -> Result<()> {
    let session_path = get_session_path(session_id)?;
    tracing::debug!("Saving session at {}", session_path);
    let session_data = serialize_session(session_data)?;
    std::fs::write(session_path, session_data).map_err(|e| e.into())
}

fn serialize_session(session_data: HashMap<String, PhpValue>) -> Result<String> {
    let mut session_str = String::new();
    for (name, value) in session_data {
        session_str.push_str(&serialize_key_value(&name, &value)?);
    }
    Ok(session_str)
}

fn serialize_key_value(name: &str, value: &PhpValue) -> Result<String> {
    Ok(format!("{}|{}", name, serialize_value(value)?))
}

fn serialize_value(value: &PhpValue) -> Result<String> {
    match value {
        PhpValue::String(s) => Ok(format!("s:{}:\"{}\";", s.len(), s)),
        PhpValue::Integer(i) => Ok(format!("i:{};", i)),
        PhpValue::Float(f) => Ok(format!("d:{};", f)),
        PhpValue::Boolean(b) => Ok(format!("b:{};", if *b { 1 } else { 0 })),
        PhpValue::Array(map) => {
            let mut entries = String::new();
            for (k, v) in map {
                entries.push_str(&format!("{}{}", serialize_value(k)?, serialize_value(v)?));
            }
            Ok(format!("a:{}:{{{}}};", map.len(), entries))
        }
        PhpValue::Null => Ok("n;".to_string()),
    }
}

#[allow(dead_code)]
fn delete_session(session_id: &str) -> Result<bool> {
    let session_path = get_session_path(session_id)?;
    tracing::debug!("Deleting session at {}", session_path);
    if Path::new(&session_path).exists() {
        std::fs::remove_file(session_path)?;
        Ok(true)
    } else {
        Ok(false)
    }
}
